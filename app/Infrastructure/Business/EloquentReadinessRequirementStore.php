<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Domain\Business\ReadinessRequirements;
use App\Models\BusinessReadinessRequirement;
use Illuminate\Support\Facades\DB;

final class EloquentReadinessRequirementStore implements ReadinessRequirementStore
{
    public function forBusiness(string $businessId): array
    {
        $order = array_flip(array_keys(ReadinessRequirements::RULES));
        $rows = BusinessReadinessRequirement::query()->where('business_id', $businessId)->get()
            ->sortBy(fn (BusinessReadinessRequirement $row): int => $order[$row->code])->values();

        return array_values($rows->map(fn (BusinessReadinessRequirement $row): array => ['code' => $row->code, 'status' => $row->status,
            'triggered_on' => $row->triggered_on->toDateString(), 'due_on' => $row->due_on?->toDateString(), 'met_on' => $row->met_on?->toDateString()])->all());
    }

    public function trigger(string $businessId, string $code, string $status, ?string $dueOn, string $today): bool
    {
        $row = new BusinessReadinessRequirement;
        $row->forceFill(['business_id' => $businessId, 'code' => $code, 'status' => $status, 'triggered_on' => $today, 'due_on' => $dueOn,
            'met_on' => null, 'policy_version' => ReadinessRequirements::POLICY_VERSION, 'revision' => 1,
            'created_at' => now(), 'updated_at' => now()]);

        return BusinessReadinessRequirement::query()->insertOrIgnore([...$row->getAttributes(), 'id' => $row->newUniqueId()]) === 1;
    }

    public function move(string $businessId, string $code, array $from, string $to, ?string $dueOn, ?string $metOn = null, ?array $confirmation = null): bool
    {
        $changes = ['status' => $to, 'due_on' => $dueOn, 'updated_at' => now()];
        if ($to === 'met') {
            $changes = [...$changes, 'met_on' => $metOn, 'confirmation' => json_encode($confirmation, JSON_THROW_ON_ERROR)];
        }

        return BusinessReadinessRequirement::query()->where('business_id', $businessId)->where('code', $code)->whereIn('status', $from)
            ->update([...$changes, 'revision' => DB::raw('revision + 1')]) === 1;
    }

    public function hasOverdue(string $businessId): bool
    {
        return BusinessReadinessRequirement::query()->where('business_id', $businessId)->where('status', 'overdue')->exists();
    }
}
