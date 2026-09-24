<?php

declare(strict_types=1);

use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\GetAuditTranscription;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\ReadAuditStatement;
use App\Application\Evidence\ReadStatementOriginal;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Identity\ChangeMembership;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorProfile;
use App\Models\BusinessMandate;
use App\Models\IdentityOperator;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditorIndependenceFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

it('requires an accepted assignment and leaves missing audit evidence explicitly unavailable', function (): void {
    $this->freezeSecond();
    $fixture = Fixture::make(1);
    $offer = Fixture::request($fixture);
    $user = $fixture['partners'][0]['user'];
    expect(fn () => app(GetAuditStatements::class)->handle($user->id, 1, $offer->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED')
        ->and(fn () => app(ReadAuditStatement::class)->handle($user->id, 1, $offer->id, 'unknown'))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED')
        ->and(fn () => app(GetAuditTranscription::class)->handle($user->id, 1, $offer->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED');
    Fixture::respond($user, $offer);
    $file = app(GetAuditStatements::class)->handle($user->id, 1, $offer->id);
    $review = AuditorIndependenceReview::query()->where('business_id', $fixture['business'])->firstOrFail();
    $json = app(CanonicalJson::class);
    expect($file['assignment'])->toBe(['id' => $offer->id, 'business_id' => $fixture['business'], 'party_id' => $user->party_id,
        'revision' => 2, 'kind' => 'flash', 'business_revision' => 1, 'mandate_version' => 1,
        'mandate_sha256' => hash('sha256', $json->encode(BusinessMandate::query()->where('business_id', $fixture['business'])->firstOrFail()->terms)),
        'independence' => ['id' => $review->id, 'revision' => 1, 'checked_at' => $review->state['checked_at'],
            'evidence_reference' => $review->state['evidence_reference'], 'sha256' => hash('sha256', $json->encode($review->state))],
        'accreditation' => ['status' => 'active', 'profile_revision' => 3, 'licence' => 'SYNTHETIC-CPA', 'expires_on' => now()->addYear()->format('Y-m-d'), 'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')]])
        ->and($file['evidence'])->toBe(['revision' => 0, 'documents' => []])->and($file['transcription'])->toBeNull()
        ->and(app(GetAuditTranscription::class)->handle($user->id, 1, $offer->id))->toBeNull()
        ->and(fn () => app(ReadAuditStatement::class)->handle($user->id, 1, $offer->id, 'unknown'))->toThrow(CommandRejection::class, 'STATEMENT_NOT_FOUND');
    $this->assertDatabaseCount('statement_evidence', 0);
    $this->assertDatabaseCount('statement_transcriptions', 0);
});

it('provides exact original bytes and current unverified reconciliations only for the assigned Business', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture, 'routine');
    $user = $fixture['partners'][0]['user'];
    Fixture::respond($user, $assignment);
    $file = app(GetAuditStatements::class)->handle($user->id, 1, $assignment->id);
    $original = app(ReadAuditStatement::class)->handle($user->id, 1, $assignment->id, $sources['document_id']);
    expect($file['evidence']['revision'])->toBe(2)->and($file['evidence']['documents'][0]['id'])->toBe($sources['document_id'])
        ->and($file['evidence']['documents'][0]['extraction']['status'])->toBe('pending')
        ->and($file['transcription']['id'])->toBe($sources['transcription_id'])->and($file['transcription']['current'])->toBeTrue()
        ->and($file['transcription']['verified'])->toBeFalse()->and($file['transcription']['payload']['observations'][0]['verified'])->toBeFalse()
        ->and($original)->toBe(['filename' => 'statement-'.$sources['document_id'].'.csv', 'media_type' => 'text/csv',
            'sha256' => hash('sha256', StatementFixture::csv()), 'content' => StatementFixture::csv()])
        ->and(json_encode($file, JSON_THROW_ON_ERROR))->not->toContain('private-account-reference', 'check_reference', 'selection_basis')
        ->and(app(ReadStatementOriginal::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], $sources['document_id'])['filename'])->toBe('private-account-reference.csv');
    $foreign = Fixture::make(0);
    $other = Fixture::statements($foreign);
    expect(fn () => app(ReadAuditStatement::class)->handle($user->id, 1, $assignment->id, $other['document_id']))->toThrow(CommandRejection::class, 'STATEMENT_NOT_FOUND')
        ->and(fn () => app(GetAuditTranscription::class)->handle($user->id, 1, $assignment->id, $other['transcription_id']))->toThrow(CommandRejection::class, 'STATEMENT_TRANSCRIPTION_NOT_FOUND');
});

it('retains correction lineage and marks audit reads stale after new Business evidence', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $user = $fixture['partners'][0]['user'];
    Fixture::respond($user, $assignment);
    $input = StatementFixture::transcription($sources['document_id']);
    $owner = $fixture['authority']['users'][0];
    $correction = app(RecordStatementTranscription::class)->handle($owner->id, 1, $fixture['business'], 2,
        $input['rails'], $input['months'], $input['statements'], (string) Str::uuid());
    $currentId = $correction['data']['transcription']['id'];
    $get = app(GetAuditTranscription::class);
    expect($get->handle($user->id, 1, $assignment->id)['id'])->toBe($currentId)
        ->and($get->handle($user->id, 1, $assignment->id)['amends_id'])->toBe($sources['transcription_id'])
        ->and($get->handle($user->id, 1, $assignment->id, $sources['transcription_id'])['current'])->toBeFalse()
        ->and($get->handle($user->id, 1, $assignment->id, $currentId)['current'])->toBeTrue();
    app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 3, 'new.csv', StatementFixture::csv('200'), (string) Str::uuid());
    $file = app(GetAuditStatements::class)->handle($user->id, 1, $assignment->id);
    expect($file['evidence']['revision'])->toBe(4)->and($file['transcription']['current'])->toBeFalse()
        ->and($file['transcription']['id'])->toBe($currentId)->and($file['transcription']['verified'])->toBeFalse();
    $this->assertDatabaseCount('statement_transcriptions', 2);
});

it('withdraws all private statement access immediately after an accepted Auditor declares a conflict', function (): void {
    $fixture = Fixture::make();
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    Fixture::respond($partner['user'], $assignment, 'conflict', 'Discovered a family interest.', 'family_or_business');
    expect(fn () => app(GetAuditStatements::class)->handle($partner['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(fn () => app(ReadAuditStatement::class)->handle($partner['user']->id, 1, $assignment->id, $sources['document_id']))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(fn () => app(GetAuditTranscription::class)->handle($partner['user']->id, 1, $assignment->id, $sources['transcription_id']))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    $replacement = Fixture::recipient($fixture, $assignment->refresh());
    expect(fn () => app(GetAuditStatements::class)->handle($replacement['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED');
    Fixture::respond($replacement['user'], $assignment);
    expect(app(GetAuditStatements::class)->handle($replacement['user']->id, 1, $assignment->id)['evidence']['revision'])->toBe(2);
});

it('rechecks current standing independence mandate membership and MFA before returning private evidence', function (string $fault, string $code): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    $user = $partner['user'];
    Fixture::respond($user, $assignment);
    if ($fault === 'standing') {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    } elseif ($fault === 'independence') {
        app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $partner['party']->id, 1,
            [...AuditorIndependenceFixture::facts(), 'financial_interest' => true], now('UTC')->format('Y-m-d\TH:i:s\Z'), 'new:interest', 'New financial interest.', (string) Str::uuid());
    } elseif ($fault === 'mandate') {
        $authority = $fixture['authority'];
        $authority['terms']['status'] = 'revoked';
        BusinessAuthorityFixture::configure($authority, 1);
    } elseif ($fault === 'membership') {
        IdentityOperator::factory()->create(['user_id' => $partner['staff']->id]);
        app(ChangeMembership::class)->handle($partner['staff']->id, $partner['party']->id, 'auditor', 'revoked', 1,
            'case:revocation', 'Revoke membership.', (string) Str::uuid());
    } elseif ($fault === 'business identity') {
        $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    } elseif ($fault === 'mfa') {
        $user->forceFill(['two_factor_confirmed_at' => null])->save();
    } else {
        $user->forceFill(['context_revision' => 2])->save();
    }
    foreach ([
        fn () => app(GetAuditStatements::class)->handle($user->id, 1, $assignment->id),
        fn () => app(ReadAuditStatement::class)->handle($user->id, 1, $assignment->id, $sources['document_id']),
        fn () => app(GetAuditTranscription::class)->handle($user->id, 1, $assignment->id, $sources['transcription_id']),
    ] as $read) {
        try {
            $read();
            $this->fail('Private statement access must be denied.');
        } catch (CommandRejection|IdentityViolation $denied) {
            expect($denied->getMessage())->toBe($code);
        }
    }
})->with([
    ['standing', 'ACCREDITATION_SUSPENDED'], ['independence', 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED'], ['mandate', 'MANDATE_REQUIRED'],
    ['membership', 'ROLE_MEMBERSHIP_REQUIRED'], ['business identity', 'PARTY_AUTHORITY_REQUIRED'], ['mfa', 'MFA_REQUIRED'], ['context', 'ACTIVE_ROLE_REVISION_CONFLICT'],
]);

it('rejects an unrelated Auditor and does not infer private access from staff or Business membership', function (): void {
    $fixture = Fixture::make();
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    Fixture::respond($partner['user'], $assignment);
    foreach ($fixture['partners'] as $other) {
        if ($other['party']->id !== $partner['party']->id) {
            expect(fn () => app(GetAuditStatements::class)->handle($other['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
        }
    }
    expect(fn () => app(GetAuditStatements::class)->handle($fixture['authority']['users'][0]->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND')
        ->and(fn () => app(ReadAuditStatement::class)->handle($fixture['staff']->id, 1, $assignment->id, $sources['document_id']))->toThrow(IdentityViolation::class)
        ->and(fn () => app(GetAuditStatements::class)->handle($partner['user']->id, 1, 'missing'))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
});

it('preserves access to accepted work when new-offer availability is paused or capacity is full', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    Fixture::engagement($partner['party']->id);
    Fixture::engagement($partner['party']->id);
    app(SetAuditorAvailability::class)->handle($partner['user']->id, 1, 3, false, (string) Str::uuid());
    expect(app(GetAuditStatements::class)->handle($partner['user']->id, 1, $assignment->id)['evidence']['revision'])->toBe(2)
        ->and(AuditorProfile::query()->firstOrFail()->state['accepting'])->toBeFalse();
});

it('retains authorized accepted evidence for late remediation without extending the Flash deadline', function (): void {
    $this->freezeTime();
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $partner = $fixture['partners'][0];
    Fixture::respond($partner['user'], $assignment);
    $deadline = $assignment->state['complete_by'];
    $this->travel(25)->hours();
    expect(app(ReadAuditStatement::class)->handle($partner['user']->id, 1, $assignment->id, $sources['document_id'])['content'])->toBe(StatementFixture::csv())
        ->and(app(GetAuditStatements::class)->handle($partner['user']->id, 1, $assignment->id)['transcription']['verified'])->toBeFalse()
        ->and($assignment->refresh()->state['complete_by'])->toBe($deadline)
        ->and($assignment->status)->toBe('accepted');
});

it('checks original and transcription integrity on the Auditor read path', function (string $fault): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $assignment = Fixture::request($fixture);
    $user = $fixture['partners'][0]['user'];
    Fixture::respond($user, $assignment);
    $event = 'eloquent.retrieved: '.($fault === 'original' ? StatementOriginal::class : StatementTranscription::class);
    Event::listen($event, function (StatementOriginal|StatementTranscription $record): void {
        $record->sha256 = str_repeat('0', 64);
    });
    try {
        expect(fn () => $fault === 'original'
            ? app(ReadAuditStatement::class)->handle($user->id, 1, $assignment->id, $sources['document_id'])
            : app(GetAuditTranscription::class)->handle($user->id, 1, $assignment->id, $sources['transcription_id']))->toThrow(RuntimeException::class);
    } finally {
        Event::forget($event);
    }
})->with(['original', 'transcription']);
