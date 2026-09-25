<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\AcceptAuditEngagementTerms;
use App\Application\Auditor\RecordAuditEngagementTerms;
use App\Models\AuditEngagementRelease;
use App\Models\User;
use Illuminate\Support\Str;

final class AuditEngagementFixture
{
    /** @return array<string, mixed> */
    public static function documents(): array
    {
        return ['master_services' => ['title' => 'Synthetic master services terms',
            'body' => "SYNTHETIC ALPHA ONLY.\nThe practitioner accepts these example platform terms; no real professional engagement is represented."],
            'agreed_procedures' => ['title' => 'Synthetic MVP-AUP-1 procedure terms',
                'body' => "SYNTHETIC ALPHA ONLY.\nReview originals, reconciliations, premises and source lineage; retain factual findings without a credit verdict."]];
    }

    public static function release(User $staff, int $revision = 0, string $status = 'active', ?string $version = null): AuditEngagementRelease
    {
        $result = app(RecordAuditEngagementTerms::class)->handle($staff->id, $revision, $status, $status === 'active' ? ($version ?? 'synthetic-terms-'.($revision + 1)) : null,
            $status === 'active' ? self::documents() : [], true, 'synthetic:approved-fixture', 'Retain isolated example terms.', (string) Str::uuid());

        return AuditEngagementRelease::query()->whereKey($result['data']['release_id'])->firstOrFail();
    }

    /** @return array<string, mixed> */
    public static function accept(User $user, AuditEngagementRelease $release, ?string $request = null): array
    {
        return app(AcceptAuditEngagementTerms::class)->handle($user->id, 1, $release->id, $release->revision, $release->sha256, true, $request ?? (string) Str::uuid());
    }

    public static function ready(User $staff, User $user): void
    {
        $release = AuditEngagementRelease::query()->orderByDesc('revision')->first();
        if ($release === null || $release->status !== 'active') {
            $release = self::release($staff, $release->revision ?? 0);
        }
        self::accept($user, $release);
    }
}
