<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Business\Contracts\CampaignTitles;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Secondary\Contracts\SecondaryOversight;
use App\Models\SecondaryHalt;
use App\Models\SecondaryListingRecord;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Compliance's read of the secondary market's halts in force and open listings, straight from the
 * market's own tables (`secondary_halts`, `secondary_listings`).
 *
 * @phpstan-import-type Oversight from SecondaryOversight
 */
final readonly class EloquentSecondaryOversight implements SecondaryOversight
{
    /** The most open listings the page shows at once, newest first. */
    private const int LISTING_LIMIT = 50;

    public function __construct(private AuthorizeStaffPermission $staff, private CampaignTitles $titles) {}

    public function overview(int $staffUserId): array
    {
        /** @var Oversight $overview */
        $overview = DB::transaction(fn (): array => $this->staff->handle($staffUserId, 'secondary.halt', function (): array {
            $names = [];
            $name = function (string $noteId) use (&$names): string {
                return $names[$noteId] ??= $this->titles->title(strtoupper($noteId));
            };
            $halts = SecondaryHalt::query()->whereDoesntHave('lift')->orderBy('halted_at')->get()
                ->map(fn (SecondaryHalt $halt): array => ['halt_id' => $halt->id, 'note_id' => $halt->business_campaign_id,
                    'deal_name' => $halt->business_campaign_id === null ? null : $name($halt->business_campaign_id), 'reason' => $halt->reason,
                    'halted_at' => $halt->halted_at->utc()->format(DateTimeInterface::ATOM)])->values()->all();
            $listings = SecondaryListingRecord::query()->where('state', 'open')->where('expires_at', '>', now('UTC'))->orderByDesc('listed_at')
                ->limit(self::LISTING_LIMIT)->get()
                ->map(fn (SecondaryListingRecord $row): array => ['listing_id' => $row->id, 'note_id' => $row->business_campaign_id,
                    'deal_name' => $name($row->business_campaign_id), 'remaining_units' => (string) $row->remaining_units,
                    'price' => ['currency' => 'RWF', 'amount' => (string) $row->price],
                    'listed_at' => $row->listed_at->utc()->format(DateTimeInterface::ATOM),
                    'expires_at' => $row->expires_at->utc()->format(DateTimeInterface::ATOM)])->values()->all();

            return ['halts' => $halts, 'listings' => $listings];
        }), 3);

        return $overview;
    }
}
