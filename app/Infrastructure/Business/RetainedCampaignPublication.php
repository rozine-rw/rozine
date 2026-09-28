<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCampaign;
use RuntimeException;

/** Shared verification of the immutable publication and its relational bindings. */
final class RetainedCampaignPublication implements PublishedCampaignEvidence
{
    public function __construct(private CanonicalJson $json) {}

    /** @return array<string, mixed> */
    public function find(string $campaignId): array
    {
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->firstOrFail();
        $payload = $campaign->payload;
        if (hash('sha256', $this->json->encode($payload)) !== $campaign->sha256 || $payload['campaign_id'] !== $campaign->id
            || $payload['business_id'] !== $campaign->business_id || $payload['application_id'] !== $campaign->business_application_id
            || $payload['release_id'] !== $campaign->business_application_release_id || $payload['exposure_reservation_id'] !== $campaign->exposure_reservation_id
            || $payload['principal'] !== $campaign->principal || $payload['actor_user_id'] !== $campaign->actor_user_id
            || $payload['recorded_at'] !== $campaign->live_at->toIso8601String() || $payload['expires_at'] !== $campaign->expires_at->toIso8601String()) {
            throw new RuntimeException('CAMPAIGN_INTEGRITY_FAILED');
        }

        return $payload;
    }
}
