<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use App\Domain\Operations\CommandRejection;

/**
 * A disbursement's state, folded from its append-only event log (C3 v2 §2e). The revision is the
 * number of recorded events. `on_hold` keeps the state it interrupted, so a release returns there
 * and neither approves nor pays. From `queued` onwards the money is in flight: nothing may hold,
 * reject or re-authorize it, and only a reconciled provider outcome or a failed worker recheck
 * ends it.
 */
final readonly class DisbursementState
{
    public const array STATES = ['ready', 'awaiting_second_approver', 'on_hold', 'queued', 'dispatched', 'succeeded', 'failed_closing'];

    public const array EVENTS = ['authorized', 'rejected', 'held', 'hold_released', 'intent_recorded', 'dispatched', 'succeeded', 'failed_closing'];

    public const array COMMANDS = ['authorize', 'approve', 'reject', 'hold', 'release_hold', 'requery'];

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
            $kind === 'dispatched' && $this->state === 'queued' => new self('dispatched', $next, $this->makerUserId, $this->checkerUserId, null, null),
            $kind === 'succeeded' && $this->state === 'dispatched' => new self('succeeded', $next, $this->makerUserId, $this->checkerUserId, null, null),
            $kind === 'failed_closing' && in_array($this->state, ['awaiting_second_approver', 'queued', 'dispatched'], true) => new self('failed_closing', $next, $this->makerUserId, $this->checkerUserId, null, null),
            default => throw new DisbursementViolation('DISBURSEMENT_TRANSITION_INVALID'),
        };
    }

    /** Whether the intent is recorded: from here nothing may hold, reject or re-authorize it. */
    public function inFlight(): bool
    {
        return in_array($this->state, self::IN_FLIGHT, true);
    }

    /**
     * Why this staff member may not run this command now, or null. Segregation is checked before
     * state, so a maker learns they may not approve their own work rather than a state detail.
     * `$makerCurrent` is whether the recorded maker still holds current authorize authority: a
     * lapsed maker's authorization can no longer be approved, and a fresh one may replace it.
     *
     * @return array{code: string, status: int}|null
     */
    public function refusal(string $command, int $actorUserId, bool $makerCurrent = true): ?array
    {
        if (! in_array($command, self::COMMANDS, true)) {
            throw new DisbursementViolation('DISBURSEMENT_COMMAND_INVALID');
        }
        $awaiting = $this->state === 'awaiting_second_approver';
        if (in_array($command, ['approve', 'reject'], true) && $awaiting && $actorUserId === $this->makerUserId) {
            return ['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403];
        }
        if ($command === 'release_hold' && $this->state === 'on_hold' && $actorUserId === $this->holdPlacerUserId) {
            return ['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403];
        }
        $allowed = match ($command) {
            'authorize' => $this->state === 'ready' || ($awaiting && ! $makerCurrent),
            'approve', 'reject' => $awaiting,
            'hold' => in_array($this->state, ['ready', 'awaiting_second_approver'], true),
            'release_hold' => $this->state === 'on_hold',
            'requery' => $this->state === 'dispatched',
        };
        if ($allowed && $command === 'approve' && ! $makerCurrent) {
            return ['code' => 'MAKER_AUTHORIZATION_VOID', 'status' => 409];
        }
        if ($allowed) {
            return null;
        }
        if ($command !== 'requery' && $this->inFlight()) {
            return ['code' => 'DISBURSEMENT_IN_FLIGHT', 'status' => 409];
        }

        return ['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409];
    }

    /** Refuses a command this state or segregation does not allow, carrying the current revision. */
    public function assertAllows(string $command, int $actorUserId, bool $makerCurrent = true): void
    {
        $refusal = $this->refusal($command, $actorUserId, $makerCurrent);
        if ($refusal !== null) {
            throw new CommandRejection($refusal['code'], $refusal['status'], $this->revision);
        }
    }

    public function allows(string $command, int $actorUserId, bool $makerCurrent = true): bool
    {
        return $this->refusal($command, $actorUserId, $makerCurrent) === null;
    }
}
