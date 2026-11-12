<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Business\Contracts\EnteredBusinesses;
use App\Models\AuditReportPublication;

final class EloquentEnteredBusinesses implements EnteredBusinesses
{
    public function all(): array
    {
        return array_values(AuditReportPublication::query()->where('status', 'published')->distinct()->orderBy('business_id')
            ->pluck('business_id')->map(fn (mixed $id): string => (string) $id)->all());
    }

    public function has(string $businessId): bool
    {
        return AuditReportPublication::query()->where('status', 'published')->where('business_id', $businessId)->exists();
    }
}
