<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use RuntimeException;

/** Verifies closure evidence before it changes the campaign view or accepted exposure. */
final class RetainedCampaignClosures implements CampaignClosureEvidence
{
    public function __construct(private CanonicalJson $json) {}

    /** @return array<string, mixed>|null */
    public function find(string $campaignId): ?array
    {
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->firstOrFail();
        $closure = BusinessCampaignClosure::query()->where('business_campaign_id', $campaign->id)->first();
        if ($closure !== null) {
            $this->verify($closure, $campaign);
        }

        return $closure?->payload;
    }

    /** @return array<string, string> */
    public function released(string $businessId): array
    {
        $closed = BusinessCampaignClosure::query()->where('business_id', $businessId)->get();
        $campaigns = BusinessCampaign::query()->whereIn('id', $closed->pluck('business_campaign_id'))->get()->keyBy('id');
        $released = [];
        foreach ($closed as $closure) {
            $this->verify($closure, $campaigns->get($closure->business_campaign_id) ?? throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED'));
            $released[$closure->exposure_reservation_id] = $closure->principal;
        }

        return $released;
    }

    private function verify(BusinessCampaignClosure $closure, BusinessCampaign $campaign): void
    {
        $payload = $closure->payload;
        if (! hash_equals($closure->sha256, hash('sha256', $this->json->encode($payload)))
            || ! hash_equals($campaign->sha256, hash('sha256', $this->json->encode($campaign->payload)))
            || ($payload['closure_id'] ?? null) !== $closure->id
            || ($payload['campaign_id'] ?? null) !== $campaign->id || $closure->business_campaign_id !== $campaign->id
            || ($payload['campaign_sha256'] ?? null) !== $campaign->sha256
            || ($payload['business_id'] ?? null) !== $campaign->business_id || $closure->business_id !== $campaign->business_id
            || ($payload['exposure_reservation_id'] ?? null) !== $campaign->exposure_reservation_id || $closure->exposure_reservation_id !== $campaign->exposure_reservation_id
            || ($payload['principal_released'] ?? null) !== $campaign->principal || $closure->principal !== $campaign->principal
            || ($payload['phase'] ?? null) !== $closure->phase || ! in_array($closure->phase, ['cancelled', 'expired'], true)
            || ($payload['closed_at'] ?? null) !== $closure->closed_at->toIso8601String()
            || ($payload['actor_user_id'] ?? null) !== $closure->actor_user_id
            || ($payload['scope'] ?? null) !== 'unfunded-v1' || ($payload['committed_refunded'] ?? null) !== ['currency' => 'RWF', 'amount' => '0']
            || ($payload['investors'] ?? null) !== 0 || ($payload['revision'] ?? null) !== 2) {
            throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
        }
    }
}
