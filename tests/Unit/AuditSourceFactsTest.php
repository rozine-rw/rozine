<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditSourceFacts;
use App\Domain\Auditor\Wgs84Distance;
use App\Domain\Operations\CommandRejection;
use Tests\Support\AuditSourceFactsFixture;

function auditSourceFactsNow(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-09-25T10:00:00Z');
}

it('keeps declared facts exact and every missing fact explicitly null rather than zero', function (): void {
    $normalizer = new AuditSourceFacts(new Wgs84Distance);
    $facts = AuditSourceFactsFixture::facts('2026-09-25T09:30:00Z');
    expect($normalizer->normalize($facts, auditSourceFactsNow()))->toBe($facts)
        ->and($normalizer->normalize(AuditSourceFactsFixture::empty(), auditSourceFactsNow()))->toBe(AuditSourceFactsFixture::empty());
    $declaredZero = [...AuditSourceFactsFixture::empty(), 'declared_stock_rwf' => '0', 'declared_stock_units' => '0',
        'photos' => ['required' => [], 'extra' => []], 'proof_ids' => ['financial' => [], 'inventory' => []]];
    expect($normalizer->normalize($declaredZero, auditSourceFactsNow()))->toBe($declaredZero);
});

it('rejects malformed, unbounded or URL-bearing source facts', function (Closure $mutate): void {
    $facts = AuditSourceFactsFixture::facts('2026-09-25T09:30:00Z');
    $facts = $mutate($facts);
    expect(fn () => (new AuditSourceFacts(new Wgs84Distance))->normalize($facts, auditSourceFactsNow()))
        ->toThrow(CommandRejection::class, 'AUDIT_SOURCE_FACTS_INVALID');
})->with([
    'unknown top-level key' => fn (array $facts): array => [...$facts, 'image_url' => 'https://example.test/a.jpg'],
    'photo image url' => function (array $facts): array {
        $facts['photos']['required'][0]['url'] = 'https://example.test/a.jpg';

        return $facts;
    },
    'check-in not an object' => fn (array $facts): array => [...$facts, 'check_in' => 'recorded'],
    'review flag not boolean' => function (array $facts): array {
        $facts['check_in']['review_required'] = 'false';

        return $facts;
    },
    'zero-padded stock value' => fn (array $facts): array => [...$facts, 'declared_stock_rwf' => '0100'],
    'numeric stock value' => fn (array $facts): array => [...$facts, 'declared_stock_rwf' => 100],
    'negative units' => fn (array $facts): array => [...$facts, 'declared_stock_units' => '-1'],
    'unbounded units' => fn (array $facts): array => [...$facts, 'declared_stock_units' => str_repeat('9', 19)],
    'blank label' => fn (array $facts): array => [...$facts, 'declared_unit_label' => ' '],
    'long label' => fn (array $facts): array => [...$facts, 'declared_sector_label' => str_repeat('a', 121)],
    'control label' => fn (array $facts): array => [...$facts, 'declared_account_label' => "Bank\n4417"],
    'future check-in' => function (array $facts): array {
        $facts['check_in']['at'] = '2026-09-25T10:00:01Z';

        return $facts;
    },
    'offset check-in' => function (array $facts): array {
        $facts['check_in']['at'] = '2026-09-25T09:30:00+00:00';

        return $facts;
    },
    'numeric capture time' => function (array $facts): array {
        $facts['photos']['extra'][0]['captured_at'] = 1_790_000_000;

        return $facts;
    },
    'latitude out of range' => function (array $facts): array {
        $facts['check_in']['position']['latitude'] = '91';

        return $facts;
    },
    'float coordinates' => function (array $facts): array {
        $facts['check_in']['position']['longitude'] = 30.0619;

        return $facts;
    },
    'accuracy out of range' => function (array $facts): array {
        $facts['photos']['required'][0]['position']['accuracy_m'] = 30001;

        return $facts;
    },
    'duplicate photo across groups' => function (array $facts): array {
        $facts['photos']['extra'][0]['id'] = 'storefront';

        return $facts;
    },
    'keyed photo list' => function (array $facts): array {
        $facts['photos']['required'] = ['storefront' => $facts['photos']['required'][0]];

        return $facts;
    },
    'too many photos' => function (array $facts): array {
        $facts['photos']['extra'] = array_map(fn (int $index): array => ['id' => 'extra-'.$index, 'captured_at' => null, 'position' => null, 'title' => null], range(1, 21));

        return $facts;
    },
    'long photo title' => function (array $facts): array {
        $facts['photos']['extra'][0]['title'] = str_repeat('a', 51);

        return $facts;
    },
    'uppercase identifier' => function (array $facts): array {
        $facts['proof_ids']['financial'][0] = 'Bank';

        return $facts;
    },
    'duplicate proof' => function (array $facts): array {
        $facts['proof_ids']['inventory'] = ['photo', 'photo'];

        return $facts;
    },
    'proof ids not a list' => function (array $facts): array {
        $facts['proof_ids']['financial'] = 'bank';

        return $facts;
    },
]);
