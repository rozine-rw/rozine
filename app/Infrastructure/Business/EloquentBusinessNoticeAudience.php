<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\User;

/** Reads the Business's entity Party and its current mandate from the Business records. */
final readonly class EloquentBusinessNoticeAudience implements BusinessNoticeAudience
{
    public function entityParty(string $businessId): string
    {
        return BusinessProfile::query()->whereKey($businessId)->sole()->entity_party_id;
    }

    public function findEntityParty(string $businessId): ?string
    {
        return BusinessProfile::query()->whereKey($businessId)->value('entity_party_id');
    }

    public function recipients(string $businessId): array
    {
        $business = BusinessProfile::query()->whereKey($businessId)->sole();
        $terms = BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->first()?->terms;
        $people = array_values(array_filter($terms['people'] ?? [], fn (array $person): bool => in_array('business.view', $person['permissions'], true)));

        return array_values(array_map(intval(...), User::query()->whereIn('party_id', array_column($people, 'party_id'))->orderBy('id')->pluck('id')->all()));
    }
}
