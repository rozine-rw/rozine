<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Fixture from BusinessAuthorityFixture as AuthorityFixture
 * @phpstan-import-type Facts from \App\Domain\Auditor\AuditorIndependence
 *
 * @phpstan-type Fixture array{staff: User, party: Party, business: string, authority: AuthorityFixture}
 */
final class AuditorIndependenceFixture
{
    /** @return Fixture */
    public static function make(string $kind = 'organization', int $count = 2): array
    {
        $authority = BusinessAuthorityFixture::make($kind, $count);
        $result = BusinessAuthorityFixture::configure($authority);
        app(ConfigureStaffAccess::class)->handle($authority['staff']->id, true, 'Review audit independence.', (string) Str::uuid(), ['approver']);

        return ['staff' => $authority['staff'], 'party' => Party::factory()->verified()->create(),
            'business' => $result['data']['business']['id'], 'authority' => $authority];
    }

    /** @return Facts */
    public static function facts(): array
    {
        return ['financial_interest' => false, 'current_role_tie' => false, 'role_tie_ended_at' => null,
            'family_or_business_conflict' => false, 'unresolved_conflict' => false];
    }

    /**
     * @param  Fixture  $fixture
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public static function record(array $fixture, int $revision = 0, ?string $request = null, ?array $facts = null): array
    {
        return app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id, $revision,
            $facts ?? self::facts(), now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic:independence-evidence', 'Reviewed complete conflict evidence.', $request ?? (string) Str::uuid());
    }
}
