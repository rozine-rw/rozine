<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Models\User;
use Illuminate\Support\Str;

/** @phpstan-import-type Facts from \App\Domain\Underwriting\BorrowerCreditFacts */
final class BusinessCreditFactsFixture
{
    /** @return Facts */
    public static function facts(): array
    {
        return ['history' => UnderwritingEvidenceFixture::application()['history'], 'obligations' => [], 'restriction_active' => false];
    }

    /** @return Facts */
    public static function repeat(): array
    {
        return ['history' => [...UnderwritingEvidenceFixture::application()['history'], 'has_rozine_history' => true,
            'repeat_eligibility' => UnderwritingEvidenceFixture::repeat(), 'instalment_conduct' => ['on_time' => 6, 'total' => 6],
            'report_conduct' => ['on_time' => 6, 'total' => 6]], 'obligations' => [], 'restriction_active' => false];
    }

    /**
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public static function record(User $staff, string $businessId, int $revision = 0, ?string $requestId = null, ?array $facts = null): array
    {
        return app(RecordIsolatedBusinessCreditFacts::class)->handle($staff->id, $businessId, $revision,
            $facts ?? self::facts(), 'synthetic:first-time-history', 'Isolated first-time borrower scenario.', $requestId ?? (string) Str::uuid());
    }
}
