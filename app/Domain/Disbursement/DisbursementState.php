<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use App\Domain\Operations\CommandRejection;

/**
 * A disbursement's state, folded from its append-only event log (C3 v2 §2e). The revision is the
 * number of recorded events. Since BRS v2.0 SVC-R01 a funded disbursement is paid automatically:
 * the system records `auto_intent_recorded` and the money is queued, with no approval screen. A
 * disbursement stays `ready` only while something blocks automatic payment, and one authorised
 * staff member may hold it there and release it, with a reason (ADM-R02). `on_hold` keeps the state
 * it interrupted, so a release returns there. From `queued` onwards the money is in flight: nothing
 * may hold it, and only a reconciled provider outcome or a failed recheck ends it.
 *
 * `authorized`, `rejected`, `intent_recorded` and `awaiting_second_approver` belong to the retired
 * maker/checker release; they still fold so disbursements recorded under it keep their history.
 */
final readonly class DisbursementState
{
    public const array STATES = ['ready', 'awaiting_second_approver', 'on_hold', 'queued', 'dispatched', 'succeeded', 'failed_closing'];

    public const array EVENTS = ['authorized', 'rejected', 'held', 'hold_released', 'intent_recorded', 'auto_intent_recorded', 'dispatched', 'succeeded', 'failed_closing'];

    public const array COMMANDS = ['hold', 'release_hold', 'requery'];

    /** The rule an automatic intent is recorded under (BRS v2.0 SVC-R01). */
    public const string AUTO_RULE_VERSION = 'brs-2.0-svc-r01';

    private const array IN_FLIGHT = ['queued', 'dispatched', 'succeeded', 'failed_closing'];

    private function __construct(
        public string $state,
        public int $revision,
        public ?int $makerUserId,
        public ?int $checkerUserId,
        public ?int $holdPlacerUserId,
        public ?string $heldFrom,
    ) {}

    public static function initial(): self
    {
        return new self('ready', 0, null, null, null, null);
    }

    /** @param list<array{kind: string, actor_user_id: int|null}> $events in revision order */
    public static function fold(array $events): self
    {
        $state = self::initial();
        foreach ($events as $event) {
            $state = $state->apply($event['kind'], $event['actor_user_id']);
        }

        return $state;
    }

    /** The state after one more recorded event. An event the state cannot accept is an integrity failure. */
    public function apply(string $kind, ?int $actorUserId): self
    {
        $next = $this->revision + 1;
        $staffEvent = in_array($kind, ['authorized', 'rejected', 'held', 'hold_released', 'intent_recorded'], true);
        if ($staffEvent && ($actorUserId === null || $actorUserId < 1)) {
            throw new DisbursementViolation('DISBURSEMENT_EVENT_ACTOR_REQUIRED');
        }

        return match (true) {
            $kind === 'authorized' && in_array($this->state, ['ready', 'awaiting_second_approver'], true) => new self('awaiting_second_approver', $next, $actorUserId, null, null, null),
            $kind === 'rejected' && $this->state === 'awaiting_second_approver' => new self('ready', $next, null, null, null, null),
            $kind === 'held' && in_array($this->state, ['ready', 'awaiting_second_approver'], true) => new self('on_hold', $next, $this->makerUserId, null, $actorUserId, $this->state),
            $kind === 'hold_released' && $this->state === 'on_hold' => new self((string) $this->heldFrom, $next, $this->makerUserId, null, null, null),
            $kind === 'intent_recorded' && $this->state === 'awaiting_second_approver' => new self('queued', $next, $this->makerUserId, $actorUserId, null, null),
            $kind === 'auto_intent_recorded' && $this->awaitsPayment() && $actorUserId === null => new self('queued', $next, null, null, null, null),
            $kind === 'dispatched' && $this->state === 'queued' => new self('dispatched', $next, $this->makerUserId, $this->checkerUserId, null, null),
            $kind === 'succeeded' && $this->state === 'dispatched' => new self('succeeded', $next, $this->makerUserId, $this->checkerUserId, null, null),
            $kind === 'failed_closing' && (in_array($this->state, ['awaiting_second_approver', 'queued', 'dispatched'], true) || ($this->state === 'ready' && $actorUserId === null)) => new self('failed_closing', $next, $this->makerUserId, $this->checkerUserId, null, null),
            default => throw new DisbursementViolation('DISBURSEMENT_TRANSITION_INVALID'),
        };
    }

    /** Whether automatic payment may still record its intent: not held and not yet in flight. */
    public function awaitsPayment(): bool
    {
        return in_array($this->state, ['ready', 'awaiting_second_approver'], true);
    }

    /** Whether the intent is recorded: from here nothing may hold, reject or re-authorize it. */
    public function inFlight(): bool
    {
        return in_array($this->state, self::IN_FLIGHT, true);
    }

    /**
     * Why this staff member may not run this command now, or null. One authorised staff member
     * holds and releases (ADM-R02): whoever placed a hold may release it.
     *
     * @return array{code: string, status: int}|null
     */
    public function refusal(string $command, int $actorUserId): ?array
    {
        if (! in_array($command, self::COMMANDS, true)) {
            throw new DisbursementViolation('DISBURSEMENT_COMMAND_INVALID');
        }
        $allowed = match ($command) {
            'hold' => $this->awaitsPayment(),
            'release_hold' => $this->state === 'on_hold',
            'requery' => $this->state === 'dispatched',
        };
        if ($allowed) {
            return null;
        }
        if ($command !== 'requery' && $this->inFlight()) {
            return ['code' => 'DISBURSEMENT_IN_FLIGHT', 'status' => 409];
        }

        return ['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409];
    }

    /** Refuses a command this state does not allow, carrying the current revision. */
    public function assertAllows(string $command, int $actorUserId): void
    {
        $refusal = $this->refusal($command, $actorUserId);
        if ($refusal !== null) {
            throw new CommandRejection($refusal['code'], $refusal['status'], $this->revision);
        }
    }

    public function allows(string $command, int $actorUserId): bool
    {
        return $this->refusal($command, $actorUserId) === null;
    }
}
