<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use Illuminate\Support\Str;

/**
 * Tells the account's Party that its password changed (BRS v2.0 NTF-R01, Security), whether the
 * person changed it signed in or reset a forgotten one. Each change is its own event.
 */
final readonly class NoticePasswordChange
{
    public function __construct(private PartyNotices $notices) {}

    public function handle(int $userId): void
    {
        $this->notices->recordForAccount($userId, PartyNoticeKind::PasswordChanged, [], 'password-changed:'.Str::lower((string) Str::ulid()));
    }
}
