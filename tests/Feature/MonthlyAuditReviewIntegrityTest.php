<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Evidence\Contracts\StatementStore;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Http\Resources\AuditDisputeResource;
use App\Models\AuditDisputeProof;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditSigningKeyRevocation;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\StatementVerification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery\Expectation;
use Mockery\MockInterface;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/** @param array<string, mixed> $fixture
 * @return array<string, mixed>
 */
function reviewIntegrityInput(array $fixture, int $revision = 1): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => $revision,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1,
        'digest' => $fixture['fields']['digest'], 'supporting_text' => 'Retained proof needs a second review.'];
}

it('retains a disputed parent until its authorized replacement is sealed and starts a fresh window', function (bool $staffRequired): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $input = reviewIntegrityInput($fixture);
    $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []);
    if ($staffRequired) {
        $store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.escalate',
            [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'Staff requested a decision on the outstanding proof.']);
        $store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve',
            [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'The retained evidence requires a correction.', 'decision' => 'require_amendment']);
    }
    $parent = AuditReportPublication::query()->firstOrFail();
    $this->travel(2)->hours();
    $amended = app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->revision + 1, (string) Str::uuid());
    expect($amended['code'])->toBe('AUDIT_AMENDMENT_CREATED')->and($parent->fresh()->status)->toBe($staffRequired ? 'escalated' : 'disputed');
    $child = AuditReport::query()->whereKey($amended['data']['audit_id'])->firstOrFail();
    foreach (['statements' => [], 'count' => ['cash' => '108000000', 'stock_units' => '190', 'operational_status' => 'active',
        'financial_proofs' => ['bank', 'momo'], 'inventory_proofs' => ['photo']], 'photos' => ['titles' => ['extra-1' => 'Stock room']]] as $step => $fields) {
        expect(app(SaveAuditReportStep::class)->handle($fixture['user']->id, 1, $child->id, $child->refresh()->revision, $step, $fields, (string) Str::uuid())['code'])->toBe('AUDIT_STEP_SAVED');
    }
    $prepared = Fixture::prepare([...$fixture, 'report' => $child]);
    expect(Fixture::seal($prepared)['code'])->toBe('AUDIT_SEALED');
    $parent->refresh();
    $replacement = AuditReportPublication::query()->where('audit_report_id', $child->id)->firstOrFail();
    expect($parent->status)->toBe('amended')->and($parent->review['outcome'])->toBe('amended')
        ->and($replacement->due_at->diffInHours($replacement->delivered_at, true))->toBe(24.0)
        ->and($replacement->delivered_at->greaterThan($parent->delivered_at))->toBeTrue();
    foreach (['business', 'auditor', 'staff'] as $role) {
        $page = AuditDisputeResource::project([...$parent->review, 'proof_files' => []], request(), $role,
            ['business' => $fixture['audit']['business'], 'assignment' => $fixture['assignment']->id]);
        expect($page['amendment']['link']['url'])->toContain($child->id);
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('records stale and invalid monthly dispute commands without a partial proof or publication', function (array $change, string $code): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $input = [...reviewIntegrityInput($fixture), ...$change];
    $result = $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []);
    expect($result['code'])->toBe($code)->and($store->findOperation($fixture['audit']['authority']['users'][0]->id, 1, $input['request_id'], 'report.dispute'))->toBe($result);
    expect(AuditReportPublication::query()->firstOrFail()->revision)->toBe(1)->and(AuditDisputeProof::query()->count())->toBe(0);
})->with([
    [['expected_revision' => 0], 'VERSION_CONFLICT'], [['report_revision' => 1], 'DIGEST_STALE'],
    [['mandate_version' => 2], 'MANDATE_STALE'], [['supporting_text' => str_repeat('é', 1001)], 'REPORT_DISPUTE_PROOF_INVALID'],
    [['supporting_text' => "invisible\u{200B}proof"], 'REPORT_DISPUTE_PROOF_INVALID'], [['supporting_text' => ''], 'REPORT_DISPUTE_PROOF_REQUIRED'],
]);

it('denies proof and receipt recovery after Business authority is revoked', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $input = reviewIntegrityInput($fixture);
    $owner = $fixture['audit']['authority']['users'][0];
    $store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, $input,
        [['filename' => 'proof.pdf', 'content' => "%PDF-1.4\nSynthetic\n%%EOF"]]);
    $proof = AuditDisputeProof::query()->firstOrFail();
    RoleMembership::query()->where('party_id', $owner->party_id)->update(['status' => 'revoked']);
    expect(fn () => $store->proof($owner->id, 1, 'business', $fixture['audit']['business'], $fixture['report']->id, $proof->id))->toThrow(IdentityViolation::class)
        ->and(fn () => $store->findOperation($owner->id, 1, $input['request_id'], 'report.dispute'))->toThrow(IdentityViolation::class);
});

it('protects delivery time and event history against direct mutation and refuses destructive rollback after activation', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect(fn () => DB::transaction(fn () => $publication->forceFill(['due_at' => now()->addDays(2), 'revision' => 2])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => AuditPublicationEvent::query()->delete()))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_09_26_103442_add_monthly_audit_review_policy.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing monthly review history requires a forward migration.');
});

it('round trips the inactive migration without changing retained legacy publications', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $publication = DB::table('audit_report_publications')->first();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_09_26_103442_add_monthly_audit_review_policy.php');
    $migration->down();
    $migration->up();
    expect((array) DB::table('audit_report_publications')->first())->toBe((array) $publication)
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
});

it('records legacy, amended and withdrawn-evidence dispute refusals without changing publications', function (string $state, string $code): void {
    $fixture = Fixture::ready(kind: $state === 'legacy' ? 'flash' : 'monthly');
    Fixture::seal($fixture);
    if ($state === 'amended') {
        app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->revision + 1, (string) Str::uuid());
    } elseif ($state === 'withdrawn') {
        AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    }
    $result = app(AuditReportPublicationStore::class)->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'],
        $fixture['report']->id, reviewIntegrityInput($fixture), []);
    expect($result['code'])->toBe($code)->and(AuditReportPublication::query()->firstOrFail()->revision)->toBe(1);
})->with([['legacy', 'REPORT_REVIEW_NOT_SUPPORTED'], ['amended', 'AUDIT_REPORT_AMENDED'], ['withdrawn', 'AUDIT_PUBLICATION_UNAVAILABLE']]);

it('keeps malformed UTF-8 dispute refusals recoverable under the same request UUID', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $input = [...reviewIntegrityInput($fixture), 'supporting_text' => "invalid\xFF"];
    $result = $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []);
    expect($result['code'])->toBe('REPORT_DISPUTE_PROOF_INVALID')
        ->and($store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []))->toBe($result);
});

it('denies stale review stages and invalid staff decisions without publishing', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $input = [...reviewIntegrityInput($fixture), 'reason' => 'Evidence must remain reviewable.'];
    expect($store->uphold($fixture['user']->id, $fixture['report']->id, $input)['code'])->toBe('REPORT_REVIEW_CLOSED')
        ->and($store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve', $input)['code'])->toBe('REPORT_REVIEW_CLOSED')
        ->and(fn () => $store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'report.auto_approve', $input))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
    $store->dispute($fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []);
    $store->uphold($fixture['user']->id, $fixture['report']->id, [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 2]);
    $resolve = [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 3, 'decision' => 'discard'];
    expect($store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve', $resolve)['code'])->toBe('REPORT_DISPUTE_DECISION_INVALID');
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    expect($store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve',
        [...$resolve, 'request_id' => (string) Str::uuid(), 'decision' => 'uphold'])['code'])->toBe('AUDIT_PUBLICATION_UNAVAILABLE');
    expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('escalated');
});

it('returns the retained signature for a second UUID without recording another signature', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $result = Fixture::cosign($fixture);
    $retry = Fixture::cosign($fixture, revision: 2);
    expect($retry['code'])->toBe('REPORT_PUBLISHED')->and($retry['data'])->toBe($result['data']);
    $this->assertDatabaseCount('audit_report_signatures', 1);
});

it('rejects missing and foreign review targets and proof roles without disclosing originals', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $store = app(AuditReportPublicationStore::class);
    $input = [...reviewIntegrityInput($fixture), 'reason' => 'Review retained evidence.'];
    $foreign = User::factory()->for(Party::factory()->verified())->create();
    $missing = (string) Str::ulid();
    expect(fn () => $store->uphold($foreign->id, $fixture['report']->id, $input))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
        ->and(fn () => $store->uphold($fixture['user']->id, $fixture['report']->id, $input))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
        ->and(fn () => $store->staffCase($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
        ->and(fn () => $store->staffCase($fixture['audit']['staff']->id, $missing, $fixture['report']->id))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
        ->and(fn () => $store->proof($foreign->id, 1, 'investor', '', $fixture['report']->id, $missing))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
    Fixture::seal($fixture);
    expect(fn () => $store->proof($fixture['user']->id, 1, 'auditor', '', $fixture['report']->id, $missing))->toThrow(CommandRejection::class, 'REPORT_PROOF_NOT_FOUND');
    $this->mock(AuditAssignmentStore::class, function (MockInterface $mock) use ($fixture): void {
        $mock->shouldReceive('withAccepted');
        $expectation = $mock->mockery_findExpectation('withAccepted', []);
        if (! $expectation instanceof Expectation) {
            throw new RuntimeException('The expected assignment test boundary was not registered.');
        }
        $expectation->once()->andReturnUsing(fn (int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed => $operation(['revision' => $fixture['assignment']->revision + 1]));
    });
    expect(fn () => app(AuditReportPublicationStore::class)->uphold($fixture['user']->id, $fixture['report']->id, $input))->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
});

it('refuses review lookup with a wrong command, role or missing publication target', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $owner = $fixture['audit']['authority']['users'][0];
    expect(fn () => $store->findOperation($owner->id, 1, (string) Str::uuid(), 'audit.dispute.resolve'))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    foreach ([['auditor', 'report.cosign'], ['staff', 'report.dispute'], ['business', 'report.dispute']] as [$role, $command]) {
        expect(fn () => $store->findReviewOperation($fixture['user']->id, 1, $role, $command, (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    }
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$fixture['user']->party_id, 'actor_user_id' => $fixture['user']->id,
        'command' => 'audit.dispute.uphold', 'request_id' => $request, 'target_type' => 'audit.publication', 'target_id' => (string) Str::ulid()]);
    expect(fn () => $store->findReviewOperation($fixture['user']->id, 1, 'auditor', 'audit.dispute.uphold', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('fails closed on corrupt retained review events, proof content and seal policy', function (string $fault): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $owner = $fixture['audit']['authority']['users'][0];
    $store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, reviewIntegrityInput($fixture),
        [['filename' => 'original.pdf', 'content' => "%PDF-1.4\nSynthetic\n%%EOF"]]);
    $proof = AuditDisputeProof::query()->firstOrFail();
    $model = match ($fault) {
        'proof' => AuditDisputeProof::class, 'policy' => AuditReportPublication::class, default => AuditPublicationEvent::class
    };
    $event = 'eloquent.retrieved: '.$model;
    Event::listen($event, function ($record) use ($fault): void {
        $record->forceFill(match ($fault) {
            'proof' => ['content' => 'changed'], 'policy' => ['policy_version' => 'audit-publication-legacy'], default => ['sha256' => str_repeat('0', 64)]
        });
    });
    try {
        if ($fault === 'proof') {
            expect(fn () => $store->proof($owner->id, 1, 'business', $fixture['audit']['business'], $fixture['report']->id, $proof->id))->toThrow(RuntimeException::class, 'AUDIT_DISPUTE_PROOF_INTEGRITY_FAILED');
        } else {
            expect(fn () => $store->get($owner->id, 1, $fixture['audit']['business'], $fixture['report']->id))->toThrow(CommandRejection::class,
                $fault === 'policy' ? 'AUDIT_SEAL_UNAVAILABLE' : 'AUDIT_REVIEW_UNAVAILABLE');
        }
    } finally {
        Event::forget($event);
    }
})->with(['event', 'proof', 'policy']);

it('keeps blocked automatic publication visible to operations and the scheduled sweep', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    Party::query()->whereKey($fixture['audit']['authority']['users'][0]->party_id)->update(['verified_at' => null]);
    $this->travel(25)->hours();
    $page = app(AuditReportPublicationStore::class)->staffCase($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id);
    expect($page['publication_available'])->toBeFalse()->and($page['unavailable_reason'])->toBe('AUDIT_PUBLICATION_UNAVAILABLE');
    Cache::forget('monthly-audit-review-sweep-cursor');
    expect(Artisan::call('audits:advance-reviews'))->toBe(0)
        ->and(Artisan::output())->toContain('remains unpublished:');
    expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('pending');
});

it('does not publish a candidate that becomes disputed before the worker reacquires its lock', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $this->travel(25)->hours();
    $event = 'eloquent.retrieved: '.AuditReportPublication::class;
    Event::listen($event, function (AuditReportPublication $publication): void {
        if (array_key_exists('status', $publication->getAttributes())) {
            $publication->status = 'disputed';
        }
    });
    try {
        expect(app(AuditReportPublicationStore::class)->advanceDue()['published'])->toBe(0);
    } finally {
        Event::forget($event);
    }
    expect(AuditReportPublication::query()->firstOrFail()->status)->toBe('pending');
});

it('rechecks the verification author after taking system publication authority and handles missing verification', function (): void {
    $authority = BusinessAuthorityFixture::make();
    $created = BusinessAuthorityFixture::configure($authority);
    $businessId = $created['data']['business']['id'];
    expect(app(StatementStore::class)->withSystemVerification($businessId,
        fn (array $business, ?array $verification): ?array => $verification))->toBeNull();
    $fixture = Fixture::ready(kind: 'monthly');
    $replacement = Party::factory()->verified()->create();
    $event = 'eloquent.retrieved: '.StatementVerification::class;
    Event::listen($event, function (StatementVerification $record) use ($replacement): void {
        if (! array_key_exists('payload', $record->getAttributes())) {
            return;
        }
        $payload = $record->payload;
        $payload['assignment']['party_id'] = $replacement->id;
        $record->forceFill(['actor_party_id' => $replacement->id, 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    try {
        expect(fn () => app(StatementStore::class)->withSystemVerification($fixture['audit']['business'],
            fn (array $business, ?array $verification): ?array => $verification))->toThrow(CommandRejection::class, 'VERSION_CONFLICT');
    } finally {
        Event::forget($event);
    }
});

it('preserves pre-policy monthly seals and their all-signers by-the-seventh publication rule', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $fixture = Fixture::ready(2, 'monthly');
    $store = app(AuditReportPublicationStore::class);
    $event = 'eloquent.creating: '.AuditReportSeal::class;
    Event::listen($event, function (AuditReportSeal $seal): void {
        $payload = array_diff_key($seal->payload, ['publication_policy' => true]);
        $signature = app(AuditReportCryptography::class)->sign($payload);
        $seal->forceFill(['payload' => $payload, 'audit_signing_key_id' => $signature['key_id'], 'jws' => $signature['jws']]);
    });
    $this->mock(AuditReportPublicationStore::class, function (MockInterface $mock) use ($store): void {
        $mock->shouldReceive('open');
        $expectation = $mock->mockery_findExpectation('open', []);
        if (! $expectation instanceof Expectation) {
            throw new RuntimeException('The legacy publication boundary was not registered.');
        }
        $expectation->once()->andReturnUsing(function (string $reportId, string $digest, array $payload) use ($store): void {
            $store->open($reportId, $digest, array_diff_key($payload, ['publication_policy' => true]));
        });
    });
    try {
        expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    } finally {
        Event::forget($event);
        app()->instance(AuditReportPublicationStore::class, $store);
    }
    $publication = AuditReportPublication::query()->firstOrFail();
    expect($publication->policy_version)->toBe('audit-publication-legacy')->and($publication->delivered_at)->toBeNull()
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_COSIGNATURE_RECORDED');
    $this->travel(3)->days();
    expect(Fixture::cosign($fixture, signer: 1, revision: 2)['code'])->toBe('REPORT_WINDOW_CLOSED')
        ->and($store->advanceDue()['published'])->toBe(0)->and($publication->refresh()->status)->toBe('pending');
    $this->assertDatabaseCount('audit_publication_events', 0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('requires every retained event to commit with its publication and rejects forged review actors', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    $owner = $fixture['audit']['authority']['users'][0];
    $event = ['audit_report_publication_id' => $publication->id, 'publication_revision' => 2, 'command' => 'report.dispute',
        'actor_kind' => 'party', 'actor_party_id' => $owner->party_id, 'actor_user_id' => $owner->id, 'created_at' => now()];
    expect(fn () => DB::transaction(function () use ($event): void {
        AuditPublicationEvent::factory()->create($event);
        DB::statement('SET CONSTRAINTS audit_publication_event_committed IMMEDIATE');
    }))->toThrow(QueryException::class, 'A review event must commit with its publication revision');
    expect(fn () => DB::transaction(fn () => AuditPublicationEvent::factory()->create([...$event, 'actor_user_id' => $fixture['user']->id])))
        ->toThrow(QueryException::class, 'Review actor account must belong to its attributed Party');
    expect(AuditPublicationEvent::query()->count())->toBe(1)->and($publication->refresh()->revision)->toBe(1);
});
