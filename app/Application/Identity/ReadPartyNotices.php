<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\PartyNotices;

/**
 * The signed-in account's own notices (BRS v2.0 NTF-R01): its Party's newest notices with the
 * unread count, and marking them read. Notices are the account's own, so a person still being
 * verified reads the result of their verification here too.
 *
 * @phpstan-import-type Inbox from PartyNotices
 */
final readonly class ReadPartyNotices
{
    private const int LIMIT = 50;

    public function __construct(private PartyNotices $notices) {}

    /** @return Inbox */
    public function inbox(int $userId): array
    {
        return $this->notices->inbox($userId, self::LIMIT);
    }

    public function unread(int $userId): int
    {
        return $this->notices->unread($userId);
    }

    /** Marks one notice read, or all of them when none is named, and returns the unread count after. */
    public function markRead(int $userId, ?string $noticeId): int
    {
        $this->notices->markRead($userId, $noticeId);

        return $this->notices->unread($userId);
    }
}
