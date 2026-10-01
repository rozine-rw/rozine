<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessConnections;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('projects the current normalized declaration under only the Business gate without claiming independence', function (string $kind, int $count): void {
    $fixture = BusinessAuthorityFixture::make($kind, $count);
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->sole();
    $mandate = BusinessMandate::query()->sole();
    $original = $mandate->getAttributes();
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        $queries[] = $event->sql;
    });
    $source = app(BusinessConnections::class);
    $evidence = $source->lockDeclared($business->id);
    $expected = array_values(array_unique([$fixture['entity'], ...array_column($fixture['terms']['people'], 'party_id')]));
    sort($expected);
    expect($evidence)->toMatchArray(['scope' => 'declared-business-connections-v1', 'business_id' => $business->id,
        'entity_party_id' => $fixture['entity'], 'mandate_id' => $mandate->id, 'mandate_version' => 1,
        'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'party_ids' => $expected, 'complete' => false])
        ->and($evidence['mandate_sha256'])->toMatch('/^[0-9a-f]{64}$/D')
        ->and($source->lockDeclared($business->id))->toBe($evidence)
        ->and(array_values(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'for update'))))
        ->toHaveCount(2)
        ->and(array_filter($queries, fn (string $sql): bool => ! str_starts_with($sql, 'select ')))->toBe([])
        ->and($mandate->refresh()->getAttributes())->toBe($original);
    foreach (array_filter($queries, fn (string $sql): bool => str_contains($sql, 'for update')) as $query) {
        expect($query)->toContain('"business_profiles"');
    }
})->with(['person' => ['person', 1], 'organization' => ['organization', 2]]);

it('pins the current mandate identity even when a new declaration retains the same terms', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->sole();
    $source = app(BusinessConnections::class);
    $first = $source->lockDeclared($business->id);
    expect(BusinessAuthorityFixture::configure($fixture, 1)['code'])->toBe('BUSINESS_AUTHORITY_RECORDED');
    $second = $source->lockDeclared($business->id);
    expect($second['party_ids'])->toBe($first['party_ids'])
        ->and($second['mandate_id'])->not->toBe($first['mandate_id'])
        ->and($second['mandate_version'])->toBe(2)
        ->and($second['mandate_sha256'])->not->toBe($first['mandate_sha256'])
        ->and(BusinessMandate::query()->count())->toBe(2);
});

it('refuses missing Business or current mandate input instead of projecting an empty connection set', function (bool $missingBusiness): void {
    $id = $missingBusiness ? strtolower((string) Str::ulid()) : BusinessProfile::factory()->create()->id;
    expect(fn () => app(BusinessConnections::class)->lockDeclared($id))
        ->toThrow(CommandRejection::class, $missingBusiness ? 'BUSINESS_NOT_FOUND' : 'MANDATE_REQUIRED');
})->with([true, false]);

it('refuses revoked future and exactly expired current mandates', function (string $state): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $fixture['terms'] = match ($state) {
        'revoked' => [...$fixture['terms'], 'status' => 'revoked'],
        'future' => [...$fixture['terms'], 'effective_at' => now()->addSecond()->format('Y-m-d\TH:i:s\Z')],
        'expired' => [...$fixture['terms'], 'expires_at' => now()->format('Y-m-d\TH:i:s\Z')],
        default => throw new InvalidArgumentException('Unknown mandate boundary.'),
    };
    BusinessAuthorityFixture::configure($fixture, 1);
    expect(fn () => app(BusinessConnections::class)->lockDeclared(BusinessProfile::query()->sole()->id))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
})->with(['revoked', 'future', 'expired']);

it('refuses damaged declarations rather than trusting their current version pointer', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $mandate = BusinessMandate::query()->sole();
    DB::statement('ALTER TABLE business_mandates DISABLE TRIGGER USER');
    try {
        $mandate->forceFill(['terms' => [...$mandate->terms, 'people' => []]])->save();
    } finally {
        DB::statement('ALTER TABLE business_mandates ENABLE TRIGGER USER');
    }
    expect(fn () => app(BusinessConnections::class)->lockDeclared($mandate->business_id))
        ->toThrow(CommandRejection::class, 'MANDATE_INVALID');
});

it('uses the shared declaration in Primary known-connection refusal without promoting absence to independence', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2);
    BusinessAuthorityFixture::configure($fixture);
    $id = BusinessProfile::query()->sole()->id;
    $source = app(PrimaryCampaignSource::class);
    foreach ([$fixture['entity'], ...array_column($fixture['terms']['people'], 'party_id')] as $partyId) {
        expect(fn () => $source->rejectKnownConnections($id, [strtoupper($partyId)]))
            ->toThrow(CommandRejection::class, 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
    }
    $outsider = strtolower((string) Str::ulid());
    $source->rejectKnownConnections($id, [$outsider]);
    expect(app(BusinessConnections::class)->lockDeclared($id)['party_ids'])->not->toContain($outsider);
});
