<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;

/**
 * Admin Messaging History (`messaging.view`, BRS v2.0 NTF-R01): every notice receipt, newest
 * first, with who it reached, through which channel and the category its kind is filed under. One
 * more receipt than the page shows is read, so the page knows whether older ones remain. The page
 * also carries the catalogue behind it: every notice kind the platform sends as it happens, with
 * its audience and channels, and the transactional email templates under resources/views/mail.
 *
 * @phpstan-type FiledDelivery array{
 *     id: string, kind: string, channel: 'in_app'|'mail', status: 'delivered'|'sent'|'failed', recipient: string,
 *     recipient_kind: 'person'|'business', delivered_at: string, category: string
 * }
 * @phpstan-type Automation array{kind: string, category: string, audience: string, channels: list<'in_app'|'mail'>}
 * @phpstan-type Template array{key: string, audience: string}
 * @phpstan-type History array{deliveries: list<FiledDelivery>, older: string|null, automations: list<Automation>, templates: list<Template>}
 */
final readonly class BrowseMessageHistory
{
    public function __construct(private PartyNotices $notices, private AuthorizeStaffPermission $staff) {}

    /** @return History */
    public function handle(int $actorId, ?string $search, ?string $before, int $limit): array
    {
        $this->staff->check($actorId, 'messaging.view');
        $deliveries = $this->notices->deliveries($search, $before, $limit + 1);
        $page = array_map(fn (array $delivery): array => [...$delivery,
            'category' => PartyNoticeKind::tryFrom($delivery['kind'])?->category() ?? 'general'], array_slice($deliveries, 0, $limit));

        return ['deliveries' => $page, 'older' => count($deliveries) > $limit ? $page[$limit - 1]['id'] : null,
            'automations' => array_map(fn (PartyNoticeKind $kind): array => ['kind' => $kind->value, 'category' => $kind->category(),
                'audience' => $kind->audience(), 'channels' => $kind->mailed() ? ['in_app', 'mail'] : ['in_app']], PartyNoticeKind::cases()),
            'templates' => self::templates()];
    }

    /**
     * The email templates by folder and name, e.g. `investor.payout_received`; the account folder's
     * mails reach anyone with an account.
     *
     * @return list<Template>
     */
    private static function templates(): array
    {
        $files = glob(resource_path('views/mail/*/*.blade.php')) ?: [];
        sort($files);

        return array_map(function (string $file): array {
            $folder = basename(dirname($file));

            return ['key' => $folder.'.'.str_replace('-', '_', basename($file, '.blade.php')),
                'audience' => $folder === 'account' ? 'everyone' : $folder];
        }, $files);
    }
}
