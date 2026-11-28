<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Business\Contracts\CampaignTitles;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Secondary\Contracts\SecondaryOversight;
use App\Models\SecondaryHalt;
use App\Models\SecondaryListingRecord;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Compliance's read of the secondary market's halts in force and open listings, straight from the
 * market's own tables (`secondary_halts`, `secondary_listings`), the issued notes a halt can name
 * (a disbursement closed as `issued`), and how resale runs: `live` when it is switched on, else
 * `simulated` while UAT's money simulation serves it, else `off`.
 *
 * @phpstan-import-type Oversight from SecondaryOversight
 */
final readonly class EloquentSecondaryOversight implements SecondaryOversight
{
    /** The most open listings the page shows at once, newest first. */
    private const int LISTING_LIMIT = 50;

    /** The most issued notes the page offers to halt, newest first. */
    private const int NOTE_LIMIT = 100;

    public function __construct(private AuthorizeStaffPermission $staff, private CampaignTitles $titles, private UatMoneySimulation $simulation) {}

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

            $notes = DB::table('disbursement_closings')->join('disbursements', 'disbursements.id', '=', 'disbursement_closings.disbursement_id')
                ->where('disbursement_closings.kind', 'issued')->orderByDesc('disbursement_closings.created_at')->orderByDesc('disbursements.business_campaign_id')
                ->limit(self::NOTE_LIMIT)->pluck('disbursements.business_campaign_id')
                ->map(fn (string $noteId): array => ['note_id' => $noteId, 'deal_name' => $name($noteId)])->values()->all();

            return ['mode' => match (true) {
                config('app.secondary_trading') === true => 'live',
                $this->simulation->active() => 'simulated',
                default => 'off',
            }, 'halts' => $halts, 'listings' => $listings, 'notes' => $notes];
        }), 3);

        return $overview;
    }
}
