<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;

/**
 * The beacon read (S4-E). Every read authorizes afresh: each requested topic keeps only the
 * audiences the caller's *current* authority reaches, derived on the server from the caller alone,
 * never from anything the client names. A topic the caller cannot read is left out, so neither
 * another audience's subject ids nor their revisions can leak.
 *
 * - `wallet`: the caller's own Party, while the Investor role is active (AuthorizeActiveRole).
 * - `campaign`: every Business the caller's Party can currently view under an active mandate.
 * - `staff_queue`: each queue the caller's current staff permission covers.
 *
 * A reset (a full reload) answers a cursor that is unknown, expired, issued under another identity
 * context, or too far behind to deliver in one read.
 *
 * @phpstan-import-type Audience from ChangeFeed
 * @phpstan-import-type Change from ChangeFeed
 */
final class ReadChanges
{
    /** How long a cursor stays valid, and the minimum retention of the feed behind it. */
    public const int CURSOR_TTL_SECONDS = 86_400;

    /** The most changes one read delivers; more resets the reader instead. */
    public const int LIMIT = 500;

    /** How long the client waits before its next read, in milliseconds. */
    public const int POLL_AFTER_MS = 10_000;

    public const array TOPICS = ['wallet', 'campaign', 'staff_queue'];

    /** The staff permission each queue needs. */
    private const array QUEUE_PERMISSIONS = ['applications' => 'applications.review'];

    public function __construct(private ChangeFeed $feed, private AuthorizeActiveRole $roles, private AuthorizeStaffPermission $staff,
        private BusinessAuthorityStore $businesses, private IdentityRepository $identities) {}

    /** A cursor for a page rendered now: every change it could not yet reflect is still ahead. */
    public function cursor(int $userId): string
    {
        return $this->fresh($this->identities->forUser($userId)['context_revision'])->encode();
    }

    /**
     * @param  list<string>  $topics
     * @return array{changes: list<Change>, next_cursor: string, reset: bool, server_time: string, poll_after_ms: int}
     */
    public function handle(int $userId, array $topics, ?string $after): array
    {
        $context = $this->identities->forUser($userId)['context_revision'];
        $cursor = $after === null ? null : ChangeCursor::parse($after);
        if ($after === null || $cursor === null || $cursor->context !== $context || $cursor->expired(now()->getTimestamp(), self::CURSOR_TTL_SECONDS)) {
            return $this->answer([], $this->fresh($context), $after !== null);
        }

        $read = $this->feed->read($this->audiences($userId, $context, $topics), $cursor, self::LIMIT);
        if ($read['overflow']) {
            return $this->answer([], $this->fresh($context), true);
        }

        return $this->answer($read['changes'], new ChangeCursor($read['xmin'], $read['after_id'], now()->getTimestamp(), $context), false);
    }

    /**
     * @param  list<string>  $topics
     * @return list<Audience>
     */
    private function audiences(int $userId, int $context, array $topics): array
    {
        $audiences = [];
        if (in_array('wallet', $topics, true)) {
            $party = $this->authorized(fn (): string => $this->roles->handle($userId, 'investor', null, $context,
                fn (array $identity): string => (string) $identity['party']['id']));
            if ($party !== null) {
                $audiences[] = ['scope' => ChangeScope::party($party), 'topic' => 'wallet'];
            }
        }
        if (in_array('campaign', $topics, true)) {
            foreach ($this->authorized(fn (): array => $this->viewableBusinesses($userId, $context)) ?? [] as $business) {
                $audiences[] = ['scope' => ChangeScope::business($business), 'topic' => 'campaign'];
            }
        }
        if (in_array('staff_queue', $topics, true)) {
            foreach (self::QUEUE_PERMISSIONS as $queue => $permission) {
                if ($this->authorized(function () use ($userId, $permission): bool {
                    $this->staff->check($userId, $permission);

                    return true;
                }) === true) {
                    $audiences[] = ['scope' => ChangeScope::staffQueue($queue), 'topic' => 'staff_queue'];
                }
            }
        }

        return $audiences;
    }

    /** @return list<string> */
    private function viewableBusinesses(int $userId, int $context): array
    {
        $ids = [];
        $before = null;
        do {
            $page = $this->businesses->discover($userId, $context, $before, 50);
            array_push($ids, ...$page['ids']);
            $before = $page['next_cursor'];
        } while ($before !== null);

        return $ids;
    }

    /**
     * The value when current authority allows it, else null.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $check
     * @return TValue|null
     */
    private function authorized(callable $check): mixed
    {
        try {
            return $check();
        } catch (IdentityViolation|CommandRejection) {
            return null;
        }
    }

    private function fresh(int $context): ChangeCursor
    {
        $horizon = $this->feed->horizon();

        return new ChangeCursor($horizon['xmin'], $horizon['after_id'], now()->getTimestamp(), $context);
    }

    /**
     * @param  list<Change>  $changes
     * @return array{changes: list<Change>, next_cursor: string, reset: bool, server_time: string, poll_after_ms: int}
     */
    private function answer(array $changes, ChangeCursor $next, bool $reset): array
    {
        return ['changes' => $changes, 'next_cursor' => $next->encode(), 'reset' => $reset,
            'server_time' => now()->toIso8601String(), 'poll_after_ms' => self::POLL_AFTER_MS];
    }
}
