<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Full original held-return authentication; no time-based or confirmed-refund retirement.
 * @phpstan-import-type CampaignInput from PrimaryCampaignSource
 * @phpstan-import-type ReplayFacts from RetainedPrimaryReservation
 */
final readonly class RetainedHeldClaimRelease
{
    public function __construct(private RetainedPrimaryReservation $reservations, private PrimaryReturnedCash $cash,
        private CanonicalJson $json) {}

    /** Caller already holds Business, campaign, all roots/commitments, then all affected Party wallets.
     * @param  CampaignInput  $campaign
     */
    public function retain(PrimaryReservationRecord $root, array $campaign, LockedWallet $wallet): void
    {
        $this->cash->requireReturned($wallet, WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
        $binding = $this->binding($root, $campaign);
        $existing = DB::table('primary_held_claim_releases')->where('primary_reservation_id', $root->id)->first();
        if ($existing === null) {
            DB::table('primary_held_claim_releases')->insert([...$binding,
                'sha256' => $this->digest($binding), 'created_at' => now('UTC')->format('Y-m-d H:i:s.uP')]);
        } else {
            $this->requireBinding($existing, $binding);
        }
    }

    /** SELECT-only: replays every retired root and original cash, never writes or locks evidence.
     * @param  CampaignInput|ReplayFacts|null  $campaign
     * @return list<string>
     */
    public function retired(string $campaignId, ?array $campaign = null): array
    {
        $rows = DB::table('primary_held_claim_releases as releases')->join('primary_reservations as roots',
            'roots.id', 'releases.primary_reservation_id')->where('roots.business_campaign_id', $campaignId)
            ->orderBy('roots.id')->get(['releases.*']);
        if ($rows->isEmpty()) {
            return [];
        }
        $ids = [];
        foreach ($rows as $row) {
            $root = PrimaryReservationRecord::query()->whereKey($row->primary_reservation_id)->sole();
            $this->requireBinding($row, $this->binding($root, $campaign ?? $this->reservations->facts($root)));
            $ids[] = $root->id;
        }
        DB::select('SELECT check_primary_claim_generations(?)', [$campaignId]);

        return $ids;
    }

    /** @param CampaignInput|ReplayFacts $campaign
     * @return array<string, string>
     */
    public function binding(PrimaryReservationRecord $root, array $campaign): array
    {
        [$reservation, $version] = $this->reservations->read($root, $campaign);
        if (! in_array($reservation->state, ['released', 'expired'], true)
            || PrimaryCommitment::query()->where('primary_reservation_id', $root->id)->exists()) {
            throw new RuntimeException('HELD_CLAIM_RETURN_INTEGRITY_FAILED');
        }
        $cash = $this->cash->inspectHeldReturn($root->party_id, WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));

        return ['primary_reservation_id' => $root->id, 'root_sha256' => $root->sha256,
            'version_id' => $version->id, 'version_sha256' => $version->sha256, ...$cash];
    }

    /** @param array<string, string> $binding */
    private function requireBinding(object $row, array $binding): void
    {
        $columns = get_object_vars($row);
        foreach ($binding as $key => $value) {
            if (($columns[$key] ?? null) !== $value) {
                throw new RuntimeException('HELD_CLAIM_RETURN_INTEGRITY_FAILED');
            }
        }
        if (! is_string($columns['sha256'] ?? null) || ! hash_equals($columns['sha256'], $this->digest($binding))) {
            throw new RuntimeException('HELD_CLAIM_RETURN_INTEGRITY_FAILED');
        }
    }

    /** @param array<string, mixed> $payload */
    private function digest(array $payload): string
    {
        return hash('sha256', $this->json->encode($payload));
    }
}
