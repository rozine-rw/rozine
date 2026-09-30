<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reads a funded commitment's decrypted reservation and confirmed revision. The funding evidence
 * is loaded first: it re-derives the funding, reservation and revision digests and refuses any
 * retained purchase whose ordinals, rights or terms no longer equal those records.
 */
final readonly class RetainedHoldingSource implements HoldingSource
{
    public function __construct(private CampaignFundingEvidence $fundings, private CanonicalJson $json) {}

    public function facts(string $commitmentId): array
    {
        if (DB::table('primary_funding_commitments')->where('commitment_id', $commitmentId)->doesntExist()) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_UNAVAILABLE');
        }
        $commitment = PrimaryCommitment::query()->whereKey($commitmentId)->sole();
        $root = PrimaryReservationRecord::query()->whereKey($commitment->primary_reservation_id)->sole();
        $version = PrimaryReservationVersion::query()->whereKey($commitment->primary_reservation_version_id)->sole();
        $this->fundings->find($root->business_campaign_id);

        return ['business_campaign_id' => $root->business_campaign_id, 'commitment_id' => $commitment->id, 'primary_reservation_id' => $root->id,
            'party_id' => $root->party_id, 'units' => $root->units, 'principal' => $root->principal,
            'ordinals' => array_values(array_map(fn (array $range): array => ['first' => (int) $range['first'], 'last' => (int) $range['last']], $root->payload['ordinals'])),
            'rights' => $root->payload['rights'], 'terms' => $version->payload['terms'], 'reservation_sha256' => $root->sha256,
            'confirmation_version_id' => $version->id, 'confirmation_revision' => $version->revision, 'confirmation_sha256' => $version->sha256];
    }

    public function verify(string $holdingId): void
    {
        $holding = DB::table('primary_holdings')->where('id', $holdingId)->first();
        if ($holding === null) {
            throw new RuntimeException('PRIMARY_HOLDING_NOT_FOUND');
        }
        $held = ['business_campaign_id' => $holding->business_campaign_id, 'commitment_id' => $holding->commitment_id,
            'primary_reservation_id' => $holding->primary_reservation_id, 'party_id' => $holding->party_id, 'units' => $holding->units,
            'principal' => $holding->principal, 'ordinals' => json_decode($holding->ordinals, true, 512, JSON_THROW_ON_ERROR),
            'rights' => json_decode($holding->rights, true, 512, JSON_THROW_ON_ERROR), 'terms' => json_decode($holding->terms, true, 512, JSON_THROW_ON_ERROR),
            'reservation_sha256' => $holding->reservation_sha256, 'confirmation_version_id' => $holding->confirmation_version_id,
            'confirmation_revision' => $holding->confirmation_revision, 'confirmation_sha256' => $holding->confirmation_sha256];
        if ($this->json->encode($held) !== $this->json->encode($this->facts($holding->commitment_id))) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_MISMATCH');
        }
    }
}
