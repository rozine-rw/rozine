<?php

declare(strict_types=1);

use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Evidence\GetStaffStatementAuditReadiness;
use App\Application\Evidence\GetStatementAuditReadiness;
use App\Application\Evidence\IngestStatement;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\StatementTranscription;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\StatementFixture;

it('tells the Business and dispatching staff whether a statement review can be requested, and why not', function (): void {
    $fixture = Fixture::make(1);
    $owner = $fixture['authority']['users'][0];
    $business = app(GetStatementAuditReadiness::class);
    $staff = app(GetStaffStatementAuditReadiness::class);
    $empty = $business->handle($owner->id, 1, $fixture['business']);
    expect($empty)->toMatchArray(['contract_version' => 'initial-statement-audit-1', 'business_id' => $fixture['business'], 'evidence_revision' => 0,
        'transcription' => null, 'ready_to_request' => false, 'reasons' => [['code' => 'STATEMENT_ORIGINAL_REQUIRED', 'field' => 'evidence']],
        'assignment' => null, 'source_verified' => false,
        'coverage' => ['declared_month_count' => 0, 'first_month' => null, 'last_month' => null],
        'underwriting' => ['status' => 'not_evaluated', 'eligible' => null], 'allowed_actions' => []])
        ->and($staff->handle($fixture['staff']->id, $fixture['business']))->toBe($empty);

    $sources = Fixture::statements($fixture);
    $transcription = StatementTranscription::query()->whereKey($sources['transcription_id'])->firstOrFail();
    $ready = $staff->handle($fixture['staff']->id, $fixture['business']);
    expect($ready['transcription'])->toBe(['id' => $transcription->id, 'revision' => 2, 'sha256' => $transcription->sha256, 'current' => true])
        ->and($ready['evidence_revision'])->toBe(2)->and($ready['ready_to_request'])->toBeTrue()->and($ready['reasons'])->toBe([])
        ->and($ready['allowed_actions'])->toBe(['audit.statement_review.request'])
        ->and($ready['coverage'])->toBe(['declared_month_count' => 1, 'first_month' => '2026-08', 'last_month' => '2026-08'])
        ->and($ready['readiness_sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode(['version' => 'initial-statement-audit-1',
            'business_id' => $fixture['business'], 'business_revision' => $ready['business_revision'], 'mandate_version' => $ready['mandate_version'],
            'evidence_revision' => 2, 'manifest_sha256' => $ready['manifest_sha256'], 'transcription' => ['id' => $transcription->id, 'sha256' => $transcription->sha256]])))
        ->and($ready['readiness_sha256'])->not->toBe($empty['readiness_sha256'])
        ->and(json_encode($ready, JSON_THROW_ON_ERROR))->not->toContain('opening_balance')->not->toContain('.csv')
        // The Business sees the same readiness without the staff action.
        ->and($business->handle($owner->id, 1, $fixture['business']))->toBe([...$ready, 'allowed_actions' => []]);

    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    $open = $staff->handle($fixture['staff']->id, $fixture['business']);
    expect($open['assignment'])->toBe(['id' => $assignment->id, 'revision' => $assignment->revision, 'kind' => 'flash', 'purpose' => 'monthly', 'status' => $assignment->status])
        ->and($open['reasons'])->toBe([['code' => 'AUDIT_ENGAGEMENT_OPEN', 'field' => 'assignment']])->and($open['allowed_actions'])->toBe([]);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $verified = $staff->handle($fixture['staff']->id, $fixture['business']);
    expect($verified['source_verified'])->toBeTrue()
        ->and($verified['reasons'])->toBe([['code' => 'STATEMENT_ALREADY_VERIFIED', 'field' => null], ['code' => 'AUDIT_ENGAGEMENT_OPEN', 'field' => 'assignment']]);

    app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 2, 'next.csv', StatementFixture::csv('300'), (string) Str::uuid());
    $moved = $business->handle($owner->id, 1, $fixture['business']);
    expect($moved['transcription'])->toBeNull()->and($moved['coverage']['declared_month_count'])->toBe(0)
        ->and($moved['source_verified'])->toBeFalse()->and($moved['evidence_revision'])->toBe(3)
        ->and($moved['reasons'][0])->toBe(['code' => 'STATEMENT_TRANSCRIPTION_STALE', 'field' => 'transcription']);
});

it('reports unreconciled statements, an unverified Business and a closed last audit as reasons not to request', function (): void {
    $fixture = Fixture::make(0);
    $owner = $fixture['authority']['users'][0];
    app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid());
    $assignment = Fixture::request($fixture);
    app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', (string) Str::uuid());
    $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();

    expect(app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business'])['reasons'])
        ->toBe([['code' => 'BUSINESS_VERIFICATION_REQUIRED', 'field' => 'business'], ['code' => 'STATEMENT_RECONCILIATION_REQUIRED', 'field' => 'transcription'],
            ['code' => 'AUDIT_ENGAGEMENT_CLOSED', 'field' => 'assignment']]);
});

it('keeps readiness to dispatching staff and the Business own members', function (): void {
    $fixture = Fixture::make(1);
    $other = BusinessApplicationFixture::make();
    expect(fn () => app(GetStaffStatementAuditReadiness::class)->handle($fixture['partners'][0]['staff']->id, $fixture['business']))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, (string) Str::ulid()))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => app(GetStatementAuditReadiness::class)->handle($other['authority']['users'][0]->id, 1, $fixture['business']))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
});
