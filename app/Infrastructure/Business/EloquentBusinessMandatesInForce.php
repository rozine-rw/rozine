<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessMandatesInForce;
use App\Domain\Business\MandateAuthority;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;

/** Reads the current mandate through the same normalization Business authority applies. */
final class EloquentBusinessMandatesInForce implements BusinessMandatesInForce
{
    public function __construct(private MandateAuthority $mandates) {}

    public function current(string $businessId): ?array
    {
        $business = BusinessProfile::query()->whereKey($businessId)->first();
        $mandate = $business === null ? null
            : BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->first();
        if ($business === null || $mandate === null) {
            return null;
        }
        $terms = $this->mandates->normalize($business->entity_kind, $business->entity_party_id, $mandate->terms);
        $at = now('UTC')->format('Y-m-d\TH:i:s\Z');
        if ($terms['status'] !== 'active' || $terms['effective_at'] > $at || ($terms['expires_at'] !== null && $terms['expires_at'] <= $at)) {
            return null;
        }

        return ['id' => (string) $mandate->getKey(), 'version' => $mandate->version, 'expires_at' => $terms['expires_at']];
    }
}
