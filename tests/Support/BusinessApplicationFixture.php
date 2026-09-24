<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\SaveBusinessApplication;
use App\Domain\Business\ApplicationDraft;
use App\Models\BusinessApplication;
use App\Models\BusinessProfile;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Fixture from BusinessAuthorityFixture
 * @phpstan-import-type Fields from ApplicationDraft
 *
 * @phpstan-type ApplicationFixture array{authority: Fixture, business: BusinessProfile, application: BusinessApplication}
 */
final class BusinessApplicationFixture
{
    /** @return ApplicationFixture */
    public static function make(string $kind = 'person', int $people = 1): array
    {
        $authority = BusinessAuthorityFixture::make($kind, $people);
        BusinessAuthorityFixture::configure($authority);
        $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
        app(CreateBusinessApplication::class)->handle($authority['users'][0]->id, 1, $business->id, 0, (string) Str::uuid());

        return ['authority' => $authority, 'business' => $business,
            'application' => BusinessApplication::query()->where('business_id', $business->id)->firstOrFail()];
    }

    /** @return Fields */
    public static function fields(string $target = '8000000'): array
    {
        return ['title' => 'Synthetic equipment purchase', 'target' => $target, 'term_months' => 6,
            'use_of_funds' => ['equipment'], 'story' => "Test-only business plan.\nNo real loan application."];
    }

    /**
     * @param  ApplicationFixture  $fixture
     * @return array<string, mixed>
     */
    public static function save(array $fixture, int $revision = 1, ?string $requestId = null, string $target = '8000000', int $actor = 0): array
    {
        return app(SaveBusinessApplication::class)->handle($fixture['authority']['users'][$actor]->id, 1,
            $fixture['business']->id, $fixture['application']->id, $revision, self::fields($target), 'raise', $requestId ?? (string) Str::uuid());
    }
}
