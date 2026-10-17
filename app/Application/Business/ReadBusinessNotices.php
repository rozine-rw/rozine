<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Identity\Contracts\PartyNotices;

/**
 * A Business's notices behind its bell (BRS v2.0 NTF-R01), held by the Business's entity Party
 * and read only under current Business authority (`business.view`): the newest notices with the
 * unread count, and marking them read. Reads are shared by everyone who may view the Business.
 *
 * @phpstan-import-type Inbox from PartyNotices
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 */
final readonly class ReadBusinessNotices
{
    private const int LIMIT = 50;

    public function __construct(private WithBusinessAuthority $authority, private PartyNotices $notices) {}

    /** @return Inbox */
    public function inbox(int $userId, int $contextRevision, string $businessId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (array $business): array => $this->notices->partyInbox($business['entity_party_id'], self::LIMIT));
    }

    public function unread(int $userId, int $contextRevision, string $businessId): int
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (array $business): int => $this->notices->partyUnread($business['entity_party_id']));
    }

    /** Marks one notice read, or all of them when none is named, and returns the unread count after. */
    public function markRead(int $userId, int $contextRevision, string $businessId, ?string $noticeId): int
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, function (array $business) use ($noticeId): int {
            $this->notices->markPartyRead($business['entity_party_id'], $noticeId);

            return $this->notices->partyUnread($business['entity_party_id']);
        });
    }
}
