<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Domain\Identity\PartyNoticeKind;

/**
 * A Party's in-app notices and their delivery receipts (BRS v2.0 NTF-R01). A notice is recorded in
 * the same transaction as the event it reports, so no notice exists without its event and no event
 * key produces two notices. An e-mailed kind is sent after commit, leaving a receipt either way.
 *
 * @phpstan-type Notice array{id: string, kind: string, facts: array<string, mixed>, created_at: string, read: bool}
 * @phpstan-type Inbox array{unread: int, notices: list<Notice>}
 * @phpstan-type Delivery array{
 *     id: string, kind: string, channel: 'in_app'|'mail', status: 'delivered'|'sent'|'failed', recipient: string,
 *     recipient_kind: 'person'|'business', delivered_at: string
 * }
 */
interface PartyNotices
{
    /**
     * Records one notice for an event inside the caller's transaction. Replaying an event key
     * records nothing new. An e-mailed kind goes to the named accounts, or, when none are named,
     * to the Party's own accounts.
     *
     * @param  array<string, string>  $facts
     * @param  list<int>|null  $mailTo
     */
    public function record(string $partyId, PartyNoticeKind $kind, array $facts, string $eventKey, ?array $mailTo = null): void;

    /**
     * Records one notice for an event on the account itself, for the account's Party; an account
     * with no Party records nothing.
     *
     * @param  array<string, string>  $facts
     */
    public function recordForAccount(int $userId, PartyNoticeKind $kind, array $facts, string $eventKey): void;

    /**
     * The account's own Party's newest notices and how many are unread; an account with no Party
     * has none.
     *
     * @return Inbox
     */
    public function inbox(int $userId, int $limit): array;

    /** How many of the account's Party's notices are unread. */
    public function unread(int $userId): int;

    /**
     * Marks one of the account's Party's notices read, or all of them when no notice is named, and
     * returns how many were newly read. A notice that is not theirs is not found.
     */
    public function markRead(int $userId, ?string $noticeId): int;

    /**
     * A Party's newest notices and how many are unread, for a caller that already holds
     * authority over that Party (a Business's entity Party, read under Business authority).
     *
     * @return Inbox
     */
    public function partyInbox(string $partyId, int $limit): array;

    /** How many of a Party's notices are unread, for a caller holding authority over it. */
    public function partyUnread(string $partyId): int;

    /**
     * Marks one of a Party's notices read, or all of them, for a caller holding authority over it,
     * and returns how many were newly read.
     */
    public function markPartyRead(string $partyId, ?string $noticeId): int;

    /**
     * Every delivery receipt, newest first, with the notice's kind and who it reached: the account
     * a mail went to, or the Party's Business or account for an in-app notice. A search matches the
     * recipient's name, the kind or its category; `before` continues after a receipt id. Grants nothing: the
     * caller has checked staff authority.
     *
     * @return list<Delivery>
     */
    public function deliveries(?string $search, ?string $before, int $limit): array;
}
