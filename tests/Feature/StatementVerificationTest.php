<?php

declare(strict_types=1);

use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Auditor\RespondToAuditAssignment;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Auditor\WithdrawAuditorAccreditation;
use App\Application\Evidence\FindStatementVerificationOperation;
use App\Application\Evidence\GetAuditStatementVerification;
use App\Application\Evidence\GetStatementTranscription;
use App\Application\Evidence\GetStatementVerification;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Evidence\RecordStatementVerification;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Evidence\StatementReconciliation;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Underwriting\CashFlowEvidence;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorProfile;
use App\Models\BusinessMandate;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditorIndependenceFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('persists an encrypted immutable source-verified monthly snapshot and a minimal exact retry receipt', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $request = (string) Str::uuid();
    $receipt = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    expect($receipt['code'])->toBe('STATEMENT_SOURCE_VERIFIED')->and($receipt['revision'])->toBe(1)
        ->and($receipt['data']['report_approval'])->toBe('not_cosigned')
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request))->toBe($receipt)
        ->and(app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))->toBe($receipt);
    $id = $receipt['data']['verification_id'];
    $owner = $fixture['authority']['users'][0];
    $view = app(GetStatementVerification::class)->handle($owner->id, 1, $fixture['business']);
    $review = AuditorIndependenceReview::query()->where('business_id', $fixture['business'])->where('party_id', $partner['party']->id)->firstOrFail();
    $json = app(CanonicalJson::class);
    expect(app(GetAuditStatementVerification::class)->handle($partner['user']->id, 1, $assignment->id, $id))->toBe($view)
        ->and($view['current'])->toBeTrue()->and($view['revision'])->toBe(1)->and($view['amends_id'])->toBeNull()
        ->and($view['payload']['observations'][0]['verified'])->toBeTrue()
        ->and($view['payload']['observations'][0]['operating_inflow'])->toBe('1000')
        ->and($view['payload']['assignment']['accreditation']['licence'])->toBe('SYNTHETIC-CPA')
        ->and($view['payload']['assignment']['accreditation']['status'])->toBe('active')
        ->and($view['payload']['assignment']['mandate_sha256'])->toBe(hash('sha256', $json->encode(BusinessMandate::query()->where('business_id', $fixture['business'])->firstOrFail()->terms)))
        ->and($view['payload']['assignment']['independence'])->toBe(['id' => $review->id, 'revision' => $review->revision,
            'checked_at' => $review->state['checked_at'], 'evidence_reference' => $review->state['evidence_reference'], 'sha256' => hash('sha256', $json->encode($review->state))])
        ->and($view['payload']['assignment']['revision'])->toBe(2)
        ->and($view['payload']['transcription']['id'])->toBe($sources['transcription_id'])
        ->and($view['payload']['source_revision'])->toBe(2)
        ->and($view['sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode($view['payload'])))
        ->and(app(GetStatementTranscription::class)->handle($owner->id, 1, $fixture['business'])['verified'])->toBeFalse();
    $record = StatementVerification::query()->firstOrFail();
    expect($record->toArray())->not->toHaveKey('payload')
        ->and(DB::table('statement_verifications')->value('payload'))->not->toContain('operating_inflow', 'SYNTHETIC-CPA', 'source_checks')
        ->and(json_encode($receipt, JSON_THROW_ON_ERROR))->not->toContain('operating_inflow', 'source_checks', 'findings', 'licence');
    $this->assertDatabaseCount('statement_verifications', 1);
    expect($assignment->refresh()->status)->toBe('accepted');
});

it('retains extraction provenance as recorded while later parser work appends a new version', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    $owner = $fixture['authority']['users'][0];
    $get = app(GetStatementVerification::class);
    $first = $get->handle($owner->id, 1, $fixture['business']);
    $extraction = StatementExtraction::query()->where('statement_original_id', $sources['document_id'])->firstOrFail();
    expect($first['payload']['source_provenance'])->toBe([$sources['document_id'] => ['sha256' => hash('sha256', StatementFixture::csv()),
        'extraction' => ['id' => $extraction->id, 'revision' => 1, 'parser_version' => $extraction->parser_version, 'status' => 'pending',
            'reason_codes' => $extraction->reason_codes, 'record_count' => null]]]);
    $next = StatementExtraction::factory()->create(['statement_original_id' => $sources['document_id'], 'revision' => 2,
        'status' => 'needs_review', 'reason_codes' => ['STATEMENT_FORMAT_REQUIRES_REVIEW'], 'record_count' => null]);
    expect($get->handle($owner->id, 1, $fixture['business']))->toBe($first);
    Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: 1);
    $current = $get->handle($owner->id, 1, $fixture['business']);
    expect($current['payload']['source_provenance'][$sources['document_id']]['extraction']['id'])->toBe($next->id)
        ->and($current['payload']['source_provenance'][$sources['document_id']]['extraction']['revision'])->toBe(2)
        ->and($current['payload']['source_provenance'][$sources['document_id']]['extraction']['status'])->toBe('needs_review')
        ->and($current['amends_id'])->toBe($first['id'])->and($current['current'])->toBeTrue();
});

it('allows the replacement Auditor to append their own attestation without rewriting or exposing it to the former Auditor', function (): void {
    $fixture = Fixture::make();
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $former = Fixture::recipient($fixture, $assignment);
    Fixture::respond($former['user'], $assignment);
    $assignment->refresh();
    $receipt = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    $first = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    Fixture::respond($former['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
    $assignment->refresh();
    $replacement = Fixture::recipient($fixture, $assignment);
    expect($replacement['party']->id)->not->toBe($former['party']->id);
    Fixture::respond($replacement['user'], $assignment);
    $assignment->refresh();
    expect(app(GetAuditStatementVerification::class)->handle($replacement['user']->id, 1, $assignment->id, $first['id']))->toBe([...$first, 'current' => false]);
    Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: 1);
    $current = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    expect($current['current'])->toBeTrue()->and($current['amends_id'])->toBe($receipt['data']['verification_id'])
        ->and($current['payload']['assignment']['party_id'])->toBe($replacement['party']->id)
        ->and($current['payload']['assignment']['revision'])->toBe($assignment->revision)
        ->and(app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], $first['id']))->toBe([...$first, 'current' => false]);
    expect(fn () => app(GetAuditStatementVerification::class)->handle($former['user']->id, 1, $assignment->id, $current['id']))
        ->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
});

it('retains factual correction lineage and never lets a replay restore a superseded snapshot', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $request = (string) Str::uuid();
    $first = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    $review = StatementFixture::review([$sources['document_id'] => hash('sha256', StatementFixture::csv())]);
    $review['recurring_owner_draw'] = '250';
    $review['findings'] = 'Corrected recurring draw supported by the source review.';
    $second = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, verificationRevision: 1);
    $get = app(GetStatementVerification::class);
    $owner = $fixture['authority']['users'][0];
    $current = $get->handle($owner->id, 1, $fixture['business']);
    expect($current['id'])->toBe($second['data']['verification_id'])->and($current['amends_id'])->toBe($first['data']['verification_id'])
        ->and($current['payload']['review']['recurring_owner_draw'])->toBe('250')
        ->and($get->handle($owner->id, 1, $fixture['business'], $first['data']['verification_id'])['current'])->toBeFalse()
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request))->toBe($first)
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'])['code'])->toBe('VERSION_CONFLICT');
    expect(fn () => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, requestId: $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 2, 'new.csv', StatementFixture::csv('200'), (string) Str::uuid());
    expect($get->handle($owner->id, 1, $fixture['business'])['current'])->toBeFalse()
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request))->toBe($first)
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, evidenceRevision: 3, verificationRevision: 2)['code'])->toBe('STATEMENT_TRANSCRIPTION_STALE');
    $input = StatementFixture::transcription($sources['document_id']);
    $newTranscription = app(RecordStatementTranscription::class)->handle($owner->id, 1, $fixture['business'], 3,
        $input['rails'], $input['months'], $input['statements'], (string) Str::uuid());
    expect(Fixture::verifyStatements($fixture, $assignment, $newTranscription['data']['transcription']['id'], $review, evidenceRevision: 4, verificationRevision: 2)['code'])->toBe('STATEMENT_SOURCE_REVIEW_REQUIRED');
    $this->assertDatabaseCount('statement_verifications', 2);
});

it('requires the month to finish in Kigali before verifying its sources through the application boundary', function (): void {
    $this->travelTo(new DateTimeImmutable('2026-08-31T21:59:59Z'));
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    expect(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'])['code'])->toBe('STATEMENT_COMPLETE_MONTH_REQUIRED');
    $this->travel(1)->seconds();
    expect(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'])['code'])->toBe('STATEMENT_SOURCE_VERIFIED');
});

it('records rejected factual reviews without approving sources and keeps the rejection stable on retry', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $review = StatementFixture::review([$sources['document_id'] => hash('sha256', StatementFixture::csv())]);
    $review['checks']['originals_authentic'] = false;
    $request = (string) Str::uuid();
    $receipt = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, requestId: $request);
    expect($receipt['code'])->toBe('STATEMENT_REVIEW_INCOMPLETE')->and($receipt['http_status'])->toBe(422)
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, requestId: $request))->toBe($receipt)
        ->and(app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))->toBe($receipt);
    $this->assertDatabaseCount('statement_verifications', 0);
    expect(StatementEvidence::query()->firstOrFail()->revision)->toBe(2);
});

it('journals malformed source reviews as stable validation failures without partial verification', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $review = StatementFixture::review([$sources['document_id'] => hash('sha256', StatementFixture::csv())]);
    $review['source_checks'][$sources['document_id']] = ['reference' => 'Missing required hash'];
    $request = (string) Str::uuid();
    $receipt = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, requestId: $request);
    expect($receipt['code'])->toBe('STATEMENT_SOURCE_REVIEW_REQUIRED')->and($receipt['http_status'])->toBe(422)
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], $review, requestId: $request))->toBe($receipt)
        ->and(app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))->toBe($receipt);
    $this->assertDatabaseCount('statement_verifications', 0);
});

it('requires current accepted-assignment authority on execution replay and lookup', function (string $fault): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $request = (string) Str::uuid();
    Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    if ($fault === 'standing') {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    } elseif ($fault === 'conflict') {
        app(RespondToAuditAssignment::class)->handle($partner['user']->id, 1, $assignment->id, 2, 'conflict', 'financial_interest', 'New financial interest.', (string) Str::uuid());
    } elseif ($fault === 'mfa') {
        $partner['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } else {
        $authority = $fixture['authority'];
        $authority['terms']['status'] = 'revoked';
        BusinessAuthorityFixture::configure($authority, 1);
    }
    foreach ([
        fn () => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request),
        fn () => app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request),
        fn () => app(GetAuditStatementVerification::class)->handle($partner['user']->id, 1, $assignment->id),
    ] as $operation) {
        try {
            $operation();
            $this->fail('Withdrawn authority must deny new and replayed operations.');
        } catch (IdentityViolation|CommandRejection $exception) {
            expect($exception->getMessage())->toBe(match ($fault) {
                'standing' => 'ACCREDITATION_SUSPENDED', 'conflict' => 'ASSIGNMENT_NOT_FOUND', 'mfa' => 'MFA_REQUIRED', default => 'MANDATE_REQUIRED',
            });
        }
    }
})->with(['standing', 'conflict', 'mfa', 'mandate']);

it('keeps missing histories side effect free and never reads a foreign Business snapshot', function (): void {
    $fixture = Fixture::make(1);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $owner = $fixture['authority']['users'][0];
    expect(app(GetStatementVerification::class)->handle($owner->id, 1, $fixture['business']))->toBeNull()
        ->and(app(GetAuditStatementVerification::class)->handle($partner['user']->id, 1, $assignment->id))->toBeNull()
        ->and(fn () => app(GetStatementVerification::class)->handle($owner->id, 1, $fixture['business'], 'unknown'))->toThrow(CommandRejection::class, 'STATEMENT_VERIFICATION_NOT_FOUND');
    $empty = app(RecordStatementVerification::class)->handle($partner['user']->id, 1, $assignment->id, 2, 0, 0, 'unknown', str_repeat('0', 64), StatementFixture::review([]), (string) Str::uuid());
    expect($empty['code'])->toBe('STATEMENT_RECONCILIATION_REQUIRED');
    $other = Fixture::make(1);
    $otherSources = Fixture::statements($other);
    $otherAssignment = Fixture::request($other);
    Fixture::respond($other['partners'][0]['user'], $otherAssignment);
    $receipt = Fixture::verifyStatements($other, $otherAssignment->refresh(), $otherSources['transcription_id']);
    expect(fn () => app(GetStatementVerification::class)->handle($owner->id, 1, $other['business']))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => app(GetStatementVerification::class)->handle($owner->id, 1, $fixture['business'], $receipt['data']['verification_id']))->toThrow(CommandRejection::class, 'STATEMENT_VERIFICATION_NOT_FOUND');
    Fixture::statements($fixture);
    expect(Fixture::verifyStatements($fixture, $assignment, $otherSources['transcription_id'])['code'])->toBe('STATEMENT_TRANSCRIPTION_NOT_FOUND');
});

it('pins source and assignment revisions and refuses a changed transcription digest', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $staleAssignment = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    expect($staleAssignment['code'])->toBe('VERSION_CONFLICT')->and($staleAssignment['revision'])->toBe(2);
    $assignment->refresh();
    $staleEvidence = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], evidenceRevision: 1);
    expect($staleEvidence['code'])->toBe('VERSION_CONFLICT')->and($staleEvidence['revision'])->toBe(2);
    $review = StatementFixture::review([$sources['document_id'] => hash('sha256', StatementFixture::csv())]);
    $receipt = app(RecordStatementVerification::class)->handle($partner['user']->id, 1, $assignment->id, 2, 2, 0, $sources['transcription_id'], str_repeat('0', 64), $review, (string) Str::uuid());
    expect($receipt['code'])->toBe('STATEMENT_TRANSCRIPTION_STALE');
    $this->assertDatabaseCount('statement_verifications', 0);
});

it('rolls back verification and its receipt together if immutable persistence fails', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    $event = 'eloquent.created: '.StatementVerification::class;
    Event::listen($event, fn () => throw new RuntimeException('Synthetic storage failure.'));
    try {
        expect(fn () => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']))->toThrow(RuntimeException::class, 'Synthetic storage failure.');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('statement_verifications', 0);
    expect(CommandOperation::query()->where('command', 'statement.verify')->count())->toBe(0);
});

it('recomputes reconciliation and checks exact original hashes before granting source verification', function (string $fault): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    $event = 'eloquent.retrieved: '.StatementTranscription::class;
    Event::listen($event, function (StatementTranscription $record) use ($fault): void {
        $payload = $record->payload;
        if ($fault === 'source') {
            $payload['source_hashes'][array_key_first($payload['source_hashes'])] = str_repeat('0', 64);
        } elseif ($fault === 'unknown source') {
            $payload['source_hashes']['unknown'] = str_repeat('0', 64);
        } elseif ($fault === 'classification') {
            $payload['classification_version'] = 'unknown';
        } else {
            $payload['observations'][0]['operating_inflow'] = '999999999';
        }
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    try {
        if (in_array($fault, ['source', 'unknown source'], true)) {
            expect(fn () => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']))->toThrow(RuntimeException::class, 'STATEMENT_SOURCE_INTEGRITY_FAILED');
            expect(CommandOperation::query()->where('command', 'statement.verify')->count())->toBe(0);
        } else {
            expect(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'])['code'])->toBe('STATEMENT_RECONCILIATION_REQUIRED');
        }
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('statement_verifications', 0);
})->with(['source', 'unknown source', 'classification', 'observations']);

it('makes stored verification history immutable and fails closed on a corrupted snapshot', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $record = StatementVerification::query()->firstOrFail();
    expect(fn () => DB::transaction(fn () => $record->forceFill(['payload' => []])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $record->delete()))->toThrow(QueryException::class)
        ->and(fn () => new StatementVerification(['payload' => []]))->toThrow(MassAssignmentException::class);
    $event = 'eloquent.retrieved: '.StatementVerification::class;
    Event::listen($event, function (StatementVerification $record): void {
        $record->sha256 = str_repeat('0', 64);
    });
    try {
        expect(fn () => app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']))->toThrow(RuntimeException::class, 'STATEMENT_VERIFICATION_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
});

it('refuses the former reviewer receipt if identity relinking wins after its journal lookup', function (): void {
    $fixture = Fixture::make(2);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $request = (string) Str::uuid();
    Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    Fixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
    $assignment->refresh();
    $replacement = Fixture::recipient($fixture, $assignment);
    Fixture::respond($replacement['user'], $assignment);
    Event::listen('eloquent.retrieved: '.CommandOperation::class, function () use ($partner, $replacement): void {
        $partner['user']->forceFill(['party_id' => $replacement['party']->id, 'context_revision' => 2,
            'active_membership_id' => $replacement['user']->refresh()->active_membership_id, 'active_membership_revision' => 1])->save();
    });
    try {
        expect(fn () => app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 2, $request))
            ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    } finally {
        Event::forget('eloquent.retrieved: '.CommandOperation::class);
    }
});

it('keeps verification lookup scoped to the actual reviewer and assignment target', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $request = (string) Str::uuid();
    expect(fn () => app(FindStatementVerificationOperation::class)->handle(User::factory()->create()->id, 1, $request))->toThrow(IdentityViolation::class, 'IDENTITY_NOT_LINKED');
    app(OperationJournal::class)->execute('party:'.$partner['party']->id, $partner['user']->id, 'statement.verify', $request, 'other', 'synthetic', [],
        function (): void {}, fn (): OperationResult => new OperationResult('SYNTHETIC', [], 1));
    expect(fn () => app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('reverses the verification table without losing original evidence or its existing transcription schema', function (): void {
    $migration = require database_path('migrations/2026_09_24_124527_create_statement_verifications_table.php');
    $sourcePins = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    $sourcePins->down();
    $migration->down();
    expect(Schema::hasTable('statement_verifications'))->toBeFalse()->and(Schema::hasTable('statement_transcriptions'))->toBeTrue();
    $migration->up();
    $sourcePins->up();
    $record = StatementVerification::factory()->create();
    expect($record->revision)->toBe(1)->and($record->toArray())->not->toHaveKey('payload');
});

it('keeps synthetic verification factories readable as history without granting current source authority', function (): void {
    $fixture = Fixture::make(0);
    $evidence = StatementEvidence::factory()->create(['business_id' => $fixture['business']]);
    $record = StatementVerification::factory()->create(['statement_evidence_id' => $evidence->id]);
    $read = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    expect($read['id'])->toBe($record->id)->and($read['current'])->toBeFalse()
        ->and($read['payload']['policy_version'])->toBe('synthetic-only')
        ->and($read['payload']['observations'])->toBe([])
        ->and($read['payload']['report_approval'])->toBe('not_cosigned');
});

it('preserves historical source verification while withdrawing its current status after a conflict or changed authority', function (string $fault): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    if (in_array($fault, ['suspend', 'revoke'], true)) {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, $fault);
    } elseif ($fault === 'membership') {
        RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'suspended']);
    } elseif ($fault === 'conflicting membership') {
        RoleMembership::factory()->create(['party_id' => $partner['party']->id, 'role' => 'investor', 'status' => 'active']);
    } elseif ($fault === 'identity') {
        $partner['party']->forceFill(['verified_at' => null])->save();
    } elseif ($fault === 'certificate') {
        $profile = AuditorProfile::query()->where('party_id', $partner['party']->id)->firstOrFail();
        $profile->forceFill(['state' => [...$profile->state, 'certificate_id' => null]])->save();
    } elseif ($fault === 'conflict') {
        Fixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
    } elseif ($fault === 'mandate') {
        $authority = $fixture['authority'];
        $authority['profile']['name'] = 'Updated legal business name';
        BusinessAuthorityFixture::configure($authority, 1);
    } elseif ($fault === 'review') {
        app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $partner['party']->id, 1,
            [...AuditorIndependenceFixture::facts(), 'financial_interest' => true], now('UTC')->format('Y-m-d\TH:i:s\Z'), 'new:interest', 'Current interest found.', (string) Str::uuid());
    } else {
        $independence = AuditorIndependenceReview::query()->firstOrFail();
        $independence->forceFill(['state' => [...$independence->state, 'mandate_version' => 99]])->save();
    }
    $read = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    expect($read['current'])->toBeFalse()->and($read['payload']['observations'][0]['verified'])->toBeTrue();
    $this->assertDatabaseCount('statement_verifications', 1);
})->with(['conflict', 'mandate', 'review', 'stale review', 'suspend', 'revoke', 'membership', 'conflicting membership', 'identity', 'certificate']);

it('retains historical validity through ordinary expiry pause and withdrawal of a pending renewal', function (string $change): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    if ($change === 'renewal') {
        $submission = app(SubmitAuditorAccreditation::class)->handle($partner['user']->id, 1, 3, 'RENEWAL-CPA', now()->addYears(2)->format('Y-m-d'),
            'renewal.pdf', "%PDF-1.7\nRenewal claim\n%%EOF", (string) Str::uuid(), true);
        expect(app(WithdrawAuditorAccreditation::class)->handle($partner['user']->id, 1, 4, $submission['data']['submission_id'], (string) Str::uuid())['code'])->toBe('ACCREDITATION_WITHDRAWN');
    } else {
        $profile = AuditorProfile::query()->where('party_id', $partner['party']->id)->firstOrFail();
        $state = $profile->state;
        if ($change === 'expiry') {
            $state['standing']['expires_on'] = now()->subDay()->format('Y-m-d');
        } else {
            $state['accepting'] = false;
        }
        $profile->forceFill(['state' => $state])->save();
    }
    expect(app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'])['current'])->toBeTrue();
})->with(['expiry', 'pause', 'renewal']);

it('keeps a valid historical classification readable but not current after a classifier upgrade', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $transcriptionEvent = 'eloquent.retrieved: '.StatementTranscription::class;
    $verificationEvent = 'eloquent.retrieved: '.StatementVerification::class;
    Event::listen($transcriptionEvent, function (StatementTranscription $record): void {
        $payload = $record->payload;
        $payload['classification_version'] = 'statement-classification-1';
        foreach ($payload['observations'] as &$observation) {
            $observation['classification_version'] = 'statement-classification-1';
        }
        unset($observation);
        $record->forceFill(['classification_version' => 'statement-classification-1', 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    Event::listen($verificationEvent, function (StatementVerification $record): void {
        $payload = $record->payload;
        $payload['classification_version'] = 'statement-classification-1';
        foreach ($payload['observations'] as &$observation) {
            $observation['classification_version'] = 'statement-classification-1';
        }
        unset($observation);
        $payload['transcription']['sha256'] = StatementTranscription::query()->whereKey($record->transcription_id)->firstOrFail()->sha256;
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    try {
        $read = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
        expect($read['current'])->toBeFalse()->and($read['payload']['classification_version'])->not->toBe(StatementReconciliation::VERSION);
    } finally {
        Event::forget($transcriptionEvent);
        Event::forget($verificationEvent);
    }
});

it('refuses a verification whose canonical snapshot disagrees with its transcription classification or engagement', function (string $fault): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    $event = 'eloquent.retrieved: '.StatementVerification::class;
    Event::listen($event, function (StatementVerification $record) use ($fault): void {
        $payload = $record->payload;
        if ($fault === 'digest') {
            $payload['transcription']['sha256'] = str_repeat('0', 64);
        } elseif ($fault === 'observation') {
            $payload['observations'][0]['classification_version'] = 'wrong';
        } elseif ($fault === 'engagement') {
            $payload['assignment']['engagement']['id'] = (string) Str::ulid();
        } else {
            $payload['classification_version'] = 'wrong';
        }
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    try {
        expect(fn () => app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']))
            ->toThrow(RuntimeException::class, 'STATEMENT_VERIFICATION_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['digest', 'observation', 'classification', 'engagement']);

it('supplies the full first-time underwriting window only from persisted Auditor-reviewed source facts', function (): void {
    $fixture = Fixture::make(1);
    $owner = $fixture['authority']['users'][0];
    $csv = "date,reference,amount\n";
    $first = new DateTimeImmutable('2023-09-01');
    for ($index = 0; $index < 36; $index++) {
        $day = $first->modify('+'.$index.' months')->format('Y-m-d');
        foreach (['sales' => '10000000', 'costs' => '-6000000', 'draw' => '-500000', 'debt' => '-500000'] as $reference => $amount) {
            $csv .= $day.','.$reference.','.$amount."\n";
        }
    }
    $ingested = app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 0, 'synthetic-36-months.csv', $csv, (string) Str::uuid());
    $sourceId = $ingested['data']['document_id'];
    $months = $statements = [];
    for ($index = 0; $index < 36; $index++) {
        $date = $first->modify('+'.$index.' months');
        $month = $date->format('Y-m');
        $day = $date->format('Y-m-d');
        $months[] = $month;
        $statements[] = ['rail_id' => 'bank-a', 'month' => $month, 'opening_balance' => (string) ($index * 3000000),
            'closing_balance' => (string) (($index + 1) * 3000000), 'source_ids' => [$sourceId], 'transactions' => [
                StatementFixture::transaction('sales', '10000000', 'operating_inflow', $day, $sourceId),
                StatementFixture::transaction('costs', '-6000000', 'operating_outflow', $day, $sourceId),
                StatementFixture::transaction('draw', '-500000', 'owner_draw', $day, $sourceId),
                StatementFixture::transaction('debt', '-500000', 'debt_service', $day, $sourceId),
            ]];
    }
    $transcription = app(RecordStatementTranscription::class)->handle($owner->id, 1, $fixture['business'], 1,
        [['id' => 'bank-a', 'active_from' => '2023-09', 'active_until' => null]], $months, $statements, (string) Str::uuid());
    $assignment = Fixture::request($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $review = StatementFixture::review([$sourceId => hash('sha256', $csv)]);
    $review['recurring_owner_draw'] = '500000';
    $review['obligations'] = [['id' => 'existing-loan', 'principal' => '2000000', 'source_ids' => [$sourceId],
        'service_by_month' => ['2026-09' => '500000', '2026-10' => '500000', '2026-11' => '500000', '2026-12' => '500000', '2027-01' => '0', '2027-02' => '0']]];
    $review['findings'] = 'Synthetic full-window review, including the evidenced external obligation and recurring draw.';
    Fixture::verifyStatements($fixture, $assignment->refresh(), $transcription['data']['transcription']['id'], $review);
    $snapshot = app(GetStatementVerification::class)->handle($owner->id, 1, $fixture['business']);
    $facts = $snapshot['payload'];
    $calculation = (new CashFlowEvidence)->analyze($facts['observations'], '2026-08', '2026-09', 6,
        $facts['review']['recurring_owner_draw'], $facts['review']['obligations']);
    expect($snapshot['current'])->toBeTrue()->and($calculation['verified_months'])->toBe(36)
        ->and($calculation['mean_nocf']->isEqualTo(4000000))->toBeTrue()
        ->and($calculation['cfads']->isEqualTo(3000000))->toBeTrue()
        ->and($calculation['committed_exposure']->isEqualTo(2000000))->toBeTrue()
        ->and($facts['report_approval'])->toBe('not_cosigned');
});
