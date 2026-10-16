<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCreditSnapshot;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessCreditFactsFixture;

it('records encrypted versioned credit facts once without placing private source facts in the journal', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $result = BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, requestId: $request);
    expect($result['code'])->toBe('CREDIT_FACTS_RECORDED')->and($result['revision'])->toBe(1)
        ->and(BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, requestId: $request))->toBe($result);
    $record = BusinessCreditSnapshot::query()->firstOrFail();
    $snapshot = app(BusinessCreditFactsStore::class)->withCurrent($fixture['business']->id, fn (?array $facts): ?array => $facts);
    expect($snapshot['sha256'])->toBe($record->sha256)->and($snapshot['facts'])->toBe(BusinessCreditFactsFixture::facts())
        ->and($snapshot['revision'])->toBe(1)->and($snapshot['source_kind'])->toBe('isolated_alpha')
        ->and($snapshot['source_reference'])->toBe('synthetic:first-time-history');
    foreach (['facts', 'reason', 'source_reference'] as $field) {
        expect($record->toArray())->not->toHaveKey($field)
            ->and($record->getRawOriginal($field))->not->toBe($record->getAttribute($field));
    }
    $journal = CommandOperation::query()->where('command', 'business.credit.fixture')->firstOrFail();
    expect(json_encode($journal->toArray(), JSON_THROW_ON_ERROR))->not->toContain('first-time-history', 'restriction_active', 'first-time borrower');
    $this->assertDatabaseCount('business_credit_snapshots', 1);
    expect(fn () => BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, 1, $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('never invents credit history and never falls back after a source withdrawal', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $store = app(BusinessCreditFactsStore::class);
    $read = fn (): ?array => $store->withCurrent($fixture['business']->id, fn (?array $facts): ?array => $facts);
    expect($read())->toBeNull();
    BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id);
    expect($read()['facts']['history']['has_rozine_history'])->toBeFalse();
    $withdrawal = app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['authority']['staff']->id, $fixture['business']->id, 1,
        null, 'synthetic:withdrawal', 'Withdraw inaccurate source facts.', (string) Str::uuid());
    expect($withdrawal['revision'])->toBe(2)->and($withdrawal['data']['credit_facts']['status'])->toBe('withdrawn')->and($read())->toBeNull();
    $old = BusinessCreditSnapshot::query()->where('revision', 1)->firstOrFail();
    expect($old->facts)->toBe(BusinessCreditFactsFixture::facts());
    $new = BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, 2,
        facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    expect($new['revision'])->toBe(3)->and($read()['facts']['restriction_active'])->toBeTrue()->and($read()['sha256'])->not->toBe($old->sha256);
    $stale = BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, 2);
    expect($stale['code'])->toBe('VERSION_CONFLICT')->and($stale['revision'])->toBe(3);
    $this->assertDatabaseCount('business_credit_snapshots', 3);
});

it('rechecks staff permission and MFA even for identical retries without exposing business existence', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $staff = $fixture['authority']['staff'];
    $request = (string) Str::uuid();
    BusinessCreditFactsFixture::record($staff, $fixture['business']->id, requestId: $request);
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Read-only staff.', (string) Str::uuid(), ['analyst']);
    foreach ([$fixture['business']->id, (string) Str::ulid()] as $businessId) {
        expect(fn () => BusinessCreditFactsFixture::record($staff, $businessId, requestId: $request))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    }
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Restore verification permission.', (string) Str::uuid(), ['compliance']);
    $staff->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => BusinessCreditFactsFixture::record($staff, $fixture['business']->id, requestId: $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
    $this->assertDatabaseCount('business_credit_snapshots', 1);
});

it('requires an existing business and isolates reads by business', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $other = BusinessApplicationFixture::make();
    BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id);
    $store = app(BusinessCreditFactsStore::class);
    expect($store->withCurrent($other['business']->id, fn (?array $facts): ?array => $facts))->toBeNull()
        ->and(fn () => $store->withCurrent((string) Str::ulid(), fn (?array $facts): ?array => $facts))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => BusinessCreditFactsFixture::record($fixture['authority']['staff'], (string) Str::ulid()))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
});

it('rejects synthetic facts in production on read and write including replay', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, requestId: $request);
    $this->app->instance('env', 'production');
    expect(app(BusinessCreditFactsStore::class)->withCurrent($fixture['business']->id, fn (?array $facts): ?array => $facts))->toBeNull()
        ->and(fn () => BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, requestId: $request))->toThrow(LogicException::class, 'ISOLATION_SEED_DENIED');
});

it('journals invalid source input without a partial snapshot', function (string $case): void {
    $fixture = BusinessApplicationFixture::make();
    $revision = 0;
    $reference = 'synthetic:scenario';
    $reason = 'Credit fixture.';
    $facts = BusinessCreditFactsFixture::facts();
    match ($case) {
        'revision' => $revision = -1,
        'reference' => $reference = 'provider:real',
        'empty reference' => $reference = 'synthetic: ',
        'long reference' => $reference .= str_repeat('a', 255),
        'control reference' => $reference .= "\n",
        'empty reason' => $reason = ' ',
        'long reason' => $reason = str_repeat('a', 2001),
        'control reason' => $reason .= "\n",
        'facts' => $facts['restriction_active'] = 'false',
        default => throw new LogicException('Unknown invalid credit source scenario.'),
    };
    $key = (string) Str::uuid();
    $record = fn (): array => app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['authority']['staff']->id, $fixture['business']->id,
        $revision, $facts, $reference, $reason, $key);
    $denial = $record();
    expect($denial['status'])->toBe('rejected')->and($denial['http_status'])->toBe(422)
        ->and($denial['code'])->toBe($case === 'facts' ? 'CREDIT_FACTS_INVALID' : 'CREDIT_SOURCE_INVALID')->and($record())->toBe($denial);
    $this->assertDatabaseCount('business_credit_snapshots', 0);
})->with(['revision', 'reference', 'empty reference', 'long reference', 'control reference', 'empty reason', 'long reason', 'control reason', 'facts']);

it('detects mismatched source hashes before running the protected callback', function (): void {
    $record = BusinessCreditSnapshot::factory()->create(['status' => 'available', 'facts' => BusinessCreditFactsFixture::facts(), 'sha256' => str_repeat('0', 64)]);
    expect(fn () => app(BusinessCreditFactsStore::class)->withCurrent($record->business_id, function (): never {
        $this->fail('Corrupted facts must never reach an evaluation.');
    }))->toThrow(CommandRejection::class, 'CREDIT_FACTS_CORRUPTED');
});

it('rolls back protected effects and enforces immutable source history in PostgreSQL', function (): void {
    $fixture = BusinessApplicationFixture::make();
    BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id);
    $record = BusinessCreditSnapshot::query()->firstOrFail();
    foreach (['update', 'delete'] as $mutation) {
        expect(fn () => DB::transaction(fn () => $mutation === 'update'
            ? DB::table('business_credit_snapshots')->where('id', $record->id)->update(['sha256' => str_repeat('0', 64)])
            : DB::table('business_credit_snapshots')->where('id', $record->id)->delete()))->toThrow(QueryException::class, 'Business credit snapshots are immutable');
    }
    expect(fn () => app(BusinessCreditFactsStore::class)->withCurrent($fixture['business']->id, function () use ($fixture): never {
        $fixture['business']->forceFill(['revision' => 99])->save();
        throw new RuntimeException('Roll back protected effect.');
    }))->toThrow(RuntimeException::class, 'Roll back protected effect.');
    expect($fixture['business']->refresh()->revision)->toBe(1)->and($record->refresh()->sha256)->toBe($record->sha256);
});
