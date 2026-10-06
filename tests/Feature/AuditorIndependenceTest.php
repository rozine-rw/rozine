<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditorIndependence;
use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Auditor\AuditorIndependence;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorIndependenceVersion;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditorIndependenceFixture as Independence;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('records encrypted independence evidence and immutable revisions with a minimal exact retry receipt', function (): void {
    $fixture = Independence::make();
    $read = app(GetAuditorIndependence::class);
    expect($read->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id))->toBeNull();
    $request = (string) Str::uuid();
    $result = Independence::record($fixture, request: $request);
    $record = AuditorIndependenceReview::query()->firstOrFail();
    $history = AuditorIndependenceVersion::query()->firstOrFail();
    expect($result['code'])->toBe('AUDITOR_INDEPENDENCE_RECORDED')->and($result['revision'])->toBe(1)
        ->and(Independence::record($fixture, request: $request))->toBe($result)
        ->and($read->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id))->toBe($history->snapshot)
        ->and($record->getRawOriginal('state'))->not->toContain('synthetic:independence-evidence', 'financial_interest')
        ->and($history->getRawOriginal('snapshot'))->not->toContain('synthetic:independence-evidence')
        ->and($history->getRawOriginal('reason'))->not->toContain('conflict evidence')
        ->and($record->toArray())->not->toHaveKey('state')->and($history->toArray())->not->toHaveKeys(['snapshot', 'reason'])
        ->and(json_encode($result, JSON_THROW_ON_ERROR))->not->toContain('financial_interest', 'synthetic:independence-evidence');
    $changed = [...Independence::facts(), 'financial_interest' => true];
    expect(Independence::record($fixture, facts: $changed)['code'])->toBe('VERSION_CONFLICT');
    expect(fn () => Independence::record($fixture, request: $request, facts: $changed))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    expect(Independence::record($fixture, 1, facts: $changed)['revision'])->toBe(2)
        ->and(Independence::record($fixture, request: $request))->toBe($result)
        ->and($record->refresh()->state['facts']['financial_interest'])->toBeTrue();
    $this->assertDatabaseCount('auditor_independence_reviews', 1);
    $this->assertDatabaseCount('auditor_independence_versions', 2);
});

it('requires current Operations permission and verified authority for every declared person including the Auditor', function (): void {
    $fixture = Independence::make();
    $request = (string) Str::uuid();
    Independence::record($fixture, request: $request);
    $fixture['party']->forceFill(['verified_at' => null])->save();
    expect(fn () => Independence::record($fixture, request: $request))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $fixture['party']->forceFill(['verified_at' => now()])->save();
    app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Compliance can verify identity but not clear assignment conflicts.', (string) Str::uuid(), ['compliance']);
    expect(fn () => Independence::record($fixture, request: $request))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    expect(fn () => app(GetAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    $this->assertDatabaseCount('auditor_independence_versions', 1);
});

it('never lets a submitted false flag clear an actual current mandate relationship', function (): void {
    $fixture = Independence::make();
    $fixture['party'] = $fixture['authority']['people'][0];
    Independence::record($fixture);
    expect(AuditorIndependenceReview::query()->firstOrFail()->state['facts']['current_role_tie'])->toBeTrue();
});

it('requires renewed review after the Business mandate changes and preserves old reviewed facts', function (): void {
    $fixture = Independence::make();
    Independence::record($fixture);
    $state = AuditorIndependenceReview::query()->firstOrFail()->state;
    $policy = new AuditorIndependence;
    expect($policy->facts($state, 1, now()->toDateTimeImmutable()))->toBe(Independence::facts());
    BusinessAuthorityFixture::configure($fixture['authority'], 1);
    expect(fn () => $policy->facts($state, 2, now()->toDateTimeImmutable()))->toThrow(CommandRejection::class, 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED');
    Independence::record($fixture, 1);
    expect(AuditorIndependenceReview::query()->firstOrFail()->state['mandate_version'])->toBe(2)
        ->and(AuditorIndependenceVersion::query()->where('revision', 1)->firstOrFail()->snapshot['state'])->toBe($state);
});

it('denies missing malformed or future independence facts instead of treating them as clearance', function (string $case): void {
    $review = AuditorIndependenceReview::factory()->make()->state;
    if ($case === 'missing') {
        $review = null;
    } elseif ($case === 'malformed') {
        $review['checked_at'] = 'unknown';
    } else {
        $review['checked_at'] = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
    }
    expect(fn () => (new AuditorIndependence)->facts($review, 1, now()->toDateTimeImmutable()))->toThrow(CommandRejection::class, 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED');
})->with(['missing', 'malformed', 'future']);

it('validates exact conflict facts time and review evidence without persisting a partial decision', function (string $case, string $code): void {
    $fixture = Independence::make();
    $facts = Independence::facts();
    $time = now('UTC')->format('Y-m-d\TH:i:s\Z');
    $reference = 'synthetic:review';
    $reason = 'Reviewed full conflict evidence.';
    switch ($case) {
        case 'missing flag': unset($facts['financial_interest']);
            break;
        case 'unknown flag': $facts['rating'] = 'Strong';
            break;
        case 'false text': $facts['financial_interest'] = 'false';
            break;
        case 'role flag': $facts['current_role_tie'] = 0;
            break;
        case 'family flag': $facts['family_or_business_conflict'] = null;
            break;
        case 'unresolved flag': $facts['unresolved_conflict'] = [];
            break;
        case 'ended number': $facts['role_tie_ended_at'] = 0;
            break;
        case 'ended malformed': $facts['role_tie_ended_at'] = 'yesterday';
            break;
        case 'ended future': $facts['role_tie_ended_at'] = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
            break;
        case 'future check': $time = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
            break;
        case 'invalid check': $time = '2026-02-30T00:00:00Z';
            break;
        case 'empty reference': $reference = '';
            break;
        case 'long reference': $reference = str_repeat('x', 256);
            break;
        case 'control reason': $reason = "hidden\u{202E}reason";
            break;
    }
    $request = (string) Str::uuid();
    $action = fn (): array => app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id, 0,
        $facts, $time, $reference, $reason, $request);
    $result = $action();
    expect($result['code'])->toBe($code)->and($result['http_status'])->toBe(422)->and($action())->toBe($result);
    $this->assertDatabaseCount('auditor_independence_reviews', 0);
    $this->assertDatabaseCount('auditor_independence_versions', 0);
})->with([
    ['missing flag', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'], ['unknown flag', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'],
    ['false text', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'], ['role flag', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'],
    ['family flag', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'], ['unresolved flag', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'],
    ['ended number', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'], ['ended malformed', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'], ['ended future', 'AUDITOR_INDEPENDENCE_FACTS_INVALID'],
    ['future check', 'AUDITOR_INDEPENDENCE_TIME_INVALID'], ['invalid check', 'AUDITOR_INDEPENDENCE_TIME_INVALID'],
    ['empty reference', 'AUDITOR_INDEPENDENCE_EVIDENCE_REQUIRED'], ['long reference', 'AUDITOR_INDEPENDENCE_EVIDENCE_REQUIRED'], ['control reason', 'AUDITOR_INDEPENDENCE_EVIDENCE_REQUIRED'],
]);

it('records ended ties and refuses to replace a newer review with an older observation', function (): void {
    $fixture = Independence::make();
    $facts = [...Independence::facts(), 'role_tie_ended_at' => now('UTC')->subMonths(23)->format('Y-m-d\TH:i:s\Z')];
    Independence::record($fixture, facts: $facts);
    expect(AuditorIndependenceReview::query()->firstOrFail()->state['facts'])->toBe($facts);
    expect(app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $fixture['party']->id, 1,
        Independence::facts(), now('UTC')->subSecond()->format('Y-m-d\TH:i:s\Z'), 'synthetic:older', 'Older evidence.', (string) Str::uuid())['code'])->toBe('AUDITOR_INDEPENDENCE_TIME_INVALID');
});

it('enforces immutable independence history valid revisions and one current review per pair', function (): void {
    $history = AuditorIndependenceVersion::factory()->create();
    expect(fn () => DB::transaction(fn () => $history->forceFill(['actor_user_id' => 42])->save()))->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => $history->delete()))->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => AuditorIndependenceReview::factory()->create(['revision' => 0])))->toThrow(QueryException::class, 'independence_review_revision');
    expect(fn () => DB::transaction(fn () => AuditorIndependenceVersion::factory()->create(['revision' => 0])))->toThrow(QueryException::class, 'independence_version_revision');
    expect(fn () => DB::transaction(fn () => AuditorIndependenceReview::factory()->create([
        'business_id' => $history->snapshot['business_id'], 'party_id' => $history->snapshot['party_id'],
    ])))->toThrow(QueryException::class);
});

it('rolls back the review and receipt if immutable history cannot be stored', function (): void {
    $fixture = Independence::make();
    $count = CommandOperation::query()->count();
    $event = 'eloquent.creating: '.AuditorIndependenceVersion::class;
    Event::listen($event, fn () => throw new RuntimeException('Synthetic independence failure'));
    try {
        expect(fn () => Independence::record($fixture))->toThrow(RuntimeException::class, 'Synthetic independence failure');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('auditor_independence_reviews', 0);
    $this->assertDatabaseCount('auditor_independence_versions', 0);
    $this->assertDatabaseCount('command_operations', $count);
});

it('reverses and reapplies the isolated independence history schema', function (): void {
    $migration = require database_path('migrations/2026_09_24_110246_create_auditor_independence_reviews_and_history.php');
    $migration->down();
    expect(Schema::hasTable('auditor_independence_reviews'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('auditor_independence_versions'))->toBeTrue()
        ->and(AuditorIndependenceVersion::factory()->create()->snapshot['revision'])->toBe(1);
});
