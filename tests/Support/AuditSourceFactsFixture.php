<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\RecordIsolatedAuditSourceFacts;
use App\Models\AuditAssignment;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Fixture from AuditAssignmentFixture
 * @phpstan-import-type Partner from AuditAssignmentFixture
 * @phpstan-import-type Facts from \App\Domain\Auditor\AuditSourceFacts
 */
final class AuditSourceFactsFixture
{
    /** @return array{audit: Fixture, assignment: AuditAssignment, partner: Partner} */
    public static function accepted(int $partners = 1, string $kind = 'flash'): array
    {
        $audit = AuditAssignmentFixture::make($partners);
        $assignment = AuditAssignmentFixture::request($audit, $kind);
        $partner = AuditAssignmentFixture::recipient($audit, $assignment);
        AuditAssignmentFixture::respond($partner['user'], $assignment);

        return ['audit' => $audit, 'assignment' => $assignment->refresh(), 'partner' => $partner];
    }

    /** @return Facts */
    public static function facts(?string $at = null): array
    {
        $at ??= now('UTC')->subMinutes(30)->format('Y-m-d\TH:i:s\Z');
        $position = ['latitude' => '-1.9441', 'longitude' => '30.0619', 'accuracy_m' => 12];

        return ['declared_stock_rwf' => '38000000', 'declared_stock_units' => '190', 'declared_unit_label' => 'Sealed crates',
            'declared_sector_label' => 'FMCG / Perishables', 'declared_account_label' => 'Synthetic bank ending 4417',
            'check_in' => ['at' => $at, 'position' => $position, 'review_required' => false],
            'photos' => ['required' => [['id' => 'storefront', 'captured_at' => $at, 'position' => $position, 'title' => null],
                ['id' => 'inventory', 'captured_at' => null, 'position' => null, 'title' => null]],
                'extra' => [['id' => 'extra-1', 'captured_at' => $at, 'position' => $position, 'title' => 'Cold room at capacity']]],
            'proof_ids' => ['financial' => ['bank', 'momo'], 'inventory' => ['photo']]];
    }

    /** @return Facts */
    public static function empty(): array
    {
        return ['declared_stock_rwf' => null, 'declared_stock_units' => null, 'declared_unit_label' => null,
            'declared_sector_label' => null, 'declared_account_label' => null,
            'check_in' => ['at' => null, 'position' => null, 'review_required' => null],
            'photos' => ['required' => null, 'extra' => null], 'proof_ids' => ['financial' => null, 'inventory' => null]];
    }

    /**
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public static function record(User $staff, AuditAssignment $assignment, int $revision = 0, ?string $requestId = null, ?array $facts = null, ?int $assignmentRevision = null): array
    {
        return app(RecordIsolatedAuditSourceFacts::class)->handle($staff->id, $assignment->id, $assignmentRevision ?? $assignment->revision, $revision,
            $facts ?? self::facts(), 'synthetic:c2-stock-declaration', 'Isolated C2 procedure acceptance scenario.', $requestId ?? (string) Str::uuid());
    }
}
