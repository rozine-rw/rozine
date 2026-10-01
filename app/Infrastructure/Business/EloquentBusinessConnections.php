<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessConnections;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Business\MandateAuthority;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use LogicException;

/** @phpstan-import-type DeclaredConnections from BusinessConnections */
final class EloquentBusinessConnections implements BusinessConnections
{
    public function __construct(private MandateAuthority $mandates, private CanonicalJson $json) {}

    public function lockDeclared(string $businessId): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('PRIMARY_TRANSACTION_REQUIRED');
        }
        $business = BusinessProfile::query()->whereKey($businessId)->lockForUpdate()->first()
            ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404);
        $mandate = BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->first()
            ?? throw new CommandRejection('MANDATE_REQUIRED', 403);
        $terms = $this->mandates->normalize($business->entity_kind, $business->entity_party_id, $mandate->terms);
        $at = now('UTC')->format('Y-m-d\TH:i:s\Z');
        if ($terms['status'] !== 'active' || $terms['effective_at'] > $at
            || ($terms['expires_at'] !== null && $terms['expires_at'] <= $at)) {
            throw new CommandRejection('MANDATE_REQUIRED', 403);
        }
        $partyIds = array_values(array_unique([$business->entity_party_id, ...array_column($terms['people'], 'party_id')]));
        sort($partyIds);
        $digest = hash('sha256', $this->json->encode(['scope' => 'declared-business-connections-v1', 'business_id' => $business->id,
            'entity_party_id' => $business->entity_party_id, 'mandate_id' => $mandate->id, 'mandate_version' => $mandate->version, 'terms' => $terms]));

        return ['scope' => 'declared-business-connections-v1', 'business_id' => $business->id, 'entity_party_id' => $business->entity_party_id,
            'mandate_id' => $mandate->id, 'mandate_version' => $mandate->version, 'mandate_sha256' => $digest,
            'checked_at' => $at, 'party_ids' => $partyIds, 'complete' => false];
    }
}
