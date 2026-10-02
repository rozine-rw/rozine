<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Domain\Primary\FundingAdmission;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RetainedPrimaryFunding implements CampaignFundingEvidence
{
    public function __construct(private CanonicalJson $json) {}

    public function find(string $campaignId): ?array
    {
        $funding = PrimaryCampaignFunding::query()->where('business_campaign_id', $campaignId)->first();
        if ($funding === null) {
            return null;
        }
        $payload = $funding->payload;
        $bindings = DB::table('primary_funding_commitments')->where('funding_id', $funding->id)->orderBy('reservation_id')->get()
            ->map(fn (object $row): array => [$row->commitment_id, $row->reservation_id, $row->hold_entry_id,
                $row->commit_entry_id, $row->wallet_id, $row->origin_operation_id])->all();
        $purchases = $payload['commitments'] ?? null;
        if (! is_array($purchases) || ! array_is_list($purchases) || $purchases === []) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }
        foreach ($purchases as $purchase) {
            if (! is_array($purchase) || ! is_array($purchase['cash'] ?? null)
                || array_diff(['commitment_id', 'reservation_id', 'confirmation_version_id', 'party_id', 'units', 'principal', 'ordinals', 'rights', 'terms', 'disclosure_sha256'], array_keys($purchase)) !== []
                || array_diff(['hold_entry_id', 'commit_entry_id', 'wallet_id', 'origin_operation_id', 'amount'], array_keys($purchase['cash'])) !== []) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
        }
        $expected = array_map(fn (array $purchase): array => [$purchase['commitment_id'], $purchase['reservation_id'],
            $purchase['cash']['hold_entry_id'], $purchase['cash']['commit_entry_id'], $purchase['cash']['wallet_id'],
            $purchase['cash']['origin_operation_id']], $purchases);
        if (! hash_equals($funding->sha256, hash('sha256', $this->json->encode($payload)))
            || ($payload['scope'] ?? null) !== 'primary-funding-v1' || ($payload['funding_id'] ?? null) !== $funding->id
            || ($payload['campaign_id'] ?? null) !== $campaignId || ($payload['business_id'] ?? null) !== $funding->business_id
            || ($payload['publication_sha256'] ?? null) !== $funding->publication_sha256
            || ($payload['exposure_reservation_id'] ?? null) !== $funding->exposure_reservation_id
            || ($payload['principal'] ?? null) !== $funding->principal || ($payload['recorded_at'] ?? null) !== $funding->created_at->utc()->format('Y-m-d\TH:i:s.u\Z')
            || ! is_array($payload['admission'] ?? null)
            || FundingAdmission::rejectionReason($payload['admission'], $campaignId, $funding->publication_sha256) !== null || $bindings !== $expected) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }

        foreach ($payload['commitments'] as $purchase) {
            $this->requirePurchase($funding, $purchase);
        }

        return $payload;
    }

    /** @param array<string, mixed> $purchase */
    private function requirePurchase(PrimaryCampaignFunding $funding, array $purchase): void
    {
        $root = PrimaryReservationRecord::query()->whereKey($purchase['reservation_id'])->first();
        $commitment = PrimaryCommitment::query()->whereKey($purchase['commitment_id'])->first();
        $version = PrimaryReservationVersion::query()->whereKey($purchase['confirmation_version_id'])->first();
        if ($root === null || $commitment === null || $version === null
            || $root->business_campaign_id !== $funding->business_campaign_id || $root->publication_sha256 !== $funding->publication_sha256
            || $commitment->primary_reservation_id !== $root->id || $commitment->primary_reservation_version_id !== $version->id
            || $version->primary_reservation_id !== $root->id || $version->state !== 'confirmed'
            || ! hash_equals($root->sha256, hash('sha256', $this->json->encode($root->payload)))
            || ! hash_equals($version->sha256, hash('sha256', $this->json->encode($version->payload)))
            || $purchase['party_id'] !== $root->party_id || $purchase['units'] !== (string) $root->units
            || $purchase['principal'] !== $root->principal || $purchase['cash']['amount'] !== $root->principal
            || $purchase['cash']['origin_operation_id'] !== $root->origin_operation_id
            || $purchase['ordinals'] !== ($root->payload['ordinals'] ?? null) || $purchase['rights'] !== ($root->payload['rights'] ?? null)
            || $purchase['terms'] !== ($version->payload['terms'] ?? null) || $purchase['disclosure_sha256'] !== ($version->payload['disclosure_sha256'] ?? null)) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }
    }
}
