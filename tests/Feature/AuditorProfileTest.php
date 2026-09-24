<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditorOperation;
use App\Application\Auditor\GetAuditorProfile;
use App\Application\Auditor\ReadAuditorCertificate;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Auditor\WithdrawAuditorAccreditation;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditorCertificate;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditorFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('reads an empty profile without creating authority or a command outcome', function (): void {
    $fixture = AuditorFixture::make();
    $count = CommandOperation::query()->count();
    $profile = app(GetAuditorProfile::class)->handle($fixture['user']->id, 1);
    expect($profile['revision'])->toBe(0)->and($profile['id'])->toBeNull()
        ->and($profile['party_id'])->toBe($fixture['party']->id)->and($profile['state']['standing']['status'])->toBe('none');
    $this->assertDatabaseCount('auditor_profiles', 0);
    $this->assertDatabaseCount('command_operations', $count);
});

it('atomically records encrypted original certificates pending claims and immutable retry receipts', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    $first = AuditorFixture::submit($fixture['user'], request: $request);
    expect($first['code'])->toBe('ACCREDITATION_SUBMITTED')->and($first['revision'])->toBe(1)
        ->and(AuditorFixture::submit($fixture['user'], request: $request))->toBe($first)
        ->and(app(FindAuditorOperation::class)->handle($fixture['user']->id, 1, 'accreditation.submit', $request))->toBe($first);
    $profile = AuditorProfile::query()->firstOrFail();
    $certificate = AuditorCertificate::query()->firstOrFail();
    $version = AuditorProfileVersion::query()->firstOrFail();
    expect($profile->state['standing']['status'])->toBe('none')
        ->and(($profile->state['submission']['id'] ?? null))->toBe($certificate->id)
        ->and($version->snapshot['state'])->toBe($profile->state)
        ->and($certificate->getRawOriginal('content'))->not->toContain('Synthetic private certificate')
        ->and($certificate->getRawOriginal('filename'))->not->toContain('private-certificate')
        ->and($profile->getRawOriginal('state'))->not->toContain('SYNTHETIC-CPA')
        ->and($version->getRawOriginal('snapshot'))->not->toContain('SYNTHETIC-CPA')
        ->and($certificate->toArray())->not->toHaveKeys(['content', 'filename'])
        ->and($profile->toArray())->not->toHaveKey('state')
        ->and($version->toArray())->not->toHaveKeys(['snapshot', 'reason'])
        ->and(json_encode($first, JSON_THROW_ON_ERROR))->not->toContain('SYNTHETIC-CPA', 'private-certificate', 'Synthetic private certificate');
    $this->assertDatabaseCount('auditor_certificates', 1);
    $this->assertDatabaseCount('auditor_profile_versions', 1);
    expect(fn () => app(SubmitAuditorAccreditation::class)->handle($fixture['user']->id, 1, 0, 'OTHER', '2028-01-01', 'different.pdf', '%PDF-1.7 changed', $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    expect(AuditorFixture::submit($fixture['user'], 0)['code'])->toBe('VERSION_CONFLICT')
        ->and(AuditorFixture::submit($fixture['user'], 1)['code'])->toBe('ACCREDITATION_SUBMISSION_PENDING');
});

it('records validation denials without creating empty aggregates and replays them unchanged', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    $action = app(SubmitAuditorAccreditation::class);
    $first = $action->handle($fixture['user']->id, 1, 0, 'CPA', '2028-01-01', 'bad.exe', 'executable', $request);
    expect($first['code'])->toBe('ACCREDITATION_TYPE_UNSUPPORTED')->and($first['field_errors'])->toHaveKey('certificate')
        ->and($action->handle($fixture['user']->id, 1, 0, 'CPA', '2028-01-01', 'bad.exe', 'executable', $request))->toBe($first);
    $this->assertDatabaseCount('auditor_profiles', 0);
    $this->assertDatabaseCount('auditor_certificates', 0);
    $this->assertDatabaseCount('auditor_profile_versions', 0);
    expect(app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 0, true, (string) Str::uuid())['code'])->toBe('ACCREDITATION_REQUIRED');
});

it('withdraws only the current named submission and retains all certificate history', function (): void {
    $fixture = AuditorFixture::make();
    $firstRequest = (string) Str::uuid();
    $first = AuditorFixture::submit($fixture['user'], request: $firstRequest);
    $action = app(WithdrawAuditorAccreditation::class);
    expect($action->handle($fixture['user']->id, 1, 1, 'not-current', (string) Str::uuid())['code'])->toBe('ACCREDITATION_SUBMISSION_STALE');
    $request = (string) Str::uuid();
    $withdrawn = $action->handle($fixture['user']->id, 1, 1, $first['data']['submission_id'], $request);
    expect($withdrawn['code'])->toBe('ACCREDITATION_WITHDRAWN')->and($withdrawn['revision'])->toBe(2)
        ->and($action->handle($fixture['user']->id, 1, 1, $first['data']['submission_id'], $request))->toBe($withdrawn);
    $second = AuditorFixture::submit($fixture['user'], 2);
    expect($second['revision'])->toBe(3)->and($second['data']['submission_id'])->not->toBe($first['data']['submission_id'])
        ->and(AuditorFixture::submit($fixture['user'], request: $firstRequest))->toBe($first);
    $this->assertDatabaseCount('auditor_certificates', 2);
    $this->assertDatabaseCount('auditor_profile_versions', 3);
    expect(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 3, 'approve', $first['data']['submission_id'])['code'])->toBe('ACCREDITATION_SUBMISSION_STALE');
});

it('requires staff approval preserves approved facts during renewal and refreshes standing separately', function (): void {
    $fixture = AuditorFixture::make();
    $submission = AuditorFixture::submit($fixture['user']);
    $request = (string) Str::uuid();
    $approved = AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submission['data']['submission_id'], $request);
    expect($approved['code'])->toBe('ACCREDITATION_REVIEWED')
        ->and(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submission['data']['submission_id'], $request))->toBe($approved);
    $profile = app(GetAuditorProfile::class)->handle($fixture['user']->id, 1);
    expect($profile['state']['standing']['status'])->toBe('active')->and($profile['state']['certificate_id'])->toBe($submission['data']['submission_id']);
    $availability = app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 2, true, (string) Str::uuid());
    expect($availability['code'])->toBe('AVAILABILITY_UPDATED');
    $renewalRequest = (string) Str::uuid();
    $renewal = app(SubmitAuditorAccreditation::class)->handle($fixture['user']->id, 1, 3, 'RENEWED-CPA', now()->addYears(2)->format('Y-m-d'), 'renewal.pdf', '%PDF-1.7 renewed certificate', $renewalRequest, true);
    expect(app(FindAuditorOperation::class)->handle($fixture['user']->id, 1, 'accreditation.renew', $renewalRequest))->toBe($renewal);
    expect(app(GetAuditorProfile::class)->handle($fixture['user']->id, 1)['state']['standing'])->toBe($profile['state']['standing']);
    expect(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 4, 'reject', $renewal['data']['submission_id'])['revision'])->toBe(5);
    $this->travel(29)->days();
    expect(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 5, 'recheck')['revision'])->toBe(6);
    $newProfile = app(GetAuditorProfile::class)->handle($fixture['user']->id, 1);
    expect($newProfile['state']['standing']['checked_at'])->not->toBe($profile['state']['standing']['checked_at'])
        ->and($newProfile['state']['accepting'])->toBeTrue();
    expect(AuditorProfileVersion::query()->where('revision', 2)->firstOrFail()->getRawOriginal('reason'))->not->toContain('Synthetic reviewed evidence');
});

it('suspends or revokes availability immediately and refuses automatic reactivation by a routine recheck', function (string $decision, string $status): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 2, true, (string) Str::uuid());
    expect(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 3, $decision)['revision'])->toBe(4);
    $state = app(GetAuditorProfile::class)->handle($fixture['user']->id, 1)['state'];
    expect($state['standing']['status'])->toBe($status)->and($state['accepting'])->toBeFalse()
        ->and(app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 4, true, (string) Str::uuid())['code'])->toBe('ACCREDITATION_SUSPENDED')
        ->and(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 4, 'recheck')['code'])->toBe('ACCREDITATION_REVIEW_INVALID');
})->with([['suspend', 'suspended'], ['revoke', 'revoked']]);

it('allows pausing with stale standing but requires a current check to resume', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    $this->travel(31)->days();
    $action = app(SetAuditorAvailability::class);
    expect($action->handle($fixture['user']->id, 1, 2, true, (string) Str::uuid())['code'])->toBe('STANDING_CHECK_REQUIRED')
        ->and($action->handle($fixture['user']->id, 1, 2, false, (string) Str::uuid())['code'])->toBe('AVAILABILITY_UPDATED');
});

it('checks current authorization for commands reads and recorded outcomes', function (string $change, string $reason): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    AuditorFixture::submit($fixture['user'], request: $request);
    if ($change === 'mfa') {
        $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } elseif ($change === 'membership') {
        RoleMembership::query()->where('party_id', $fixture['party']->id)->update(['status' => 'revoked']);
    } else {
        $fixture['party']->forceFill(['verified_at' => null])->save();
    }
    expect(fn () => AuditorFixture::submit($fixture['user'], request: $request))->toThrow(IdentityViolation::class, $reason)
        ->and(fn () => app(GetAuditorProfile::class)->handle($fixture['user']->id, 1))->toThrow(IdentityViolation::class, $reason)
        ->and(fn () => app(FindAuditorOperation::class)->handle($fixture['user']->id, 1, 'accreditation.submit', $request))->toThrow(IdentityViolation::class, $reason);
})->with([['mfa', 'MFA_REQUIRED'], ['membership', 'ROLE_MEMBERSHIP_REQUIRED'], ['person', 'IDENTITY_VERIFICATION_REQUIRED']]);

it('shares a receipt only with another currently authorized login for the same Party', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    $first = AuditorFixture::submit($fixture['user'], request: $request);
    $otherLogin = User::factory()->withTwoFactor()->for($fixture['party'])->create();
    app(SelectActiveRole::class)->handle($otherLogin->id, 'auditor', 0, (string) Str::uuid());
    expect(AuditorFixture::submit($otherLogin, request: $request))->toBe($first);
    $other = AuditorFixture::make();
    expect(fn () => app(FindAuditorOperation::class)->handle($other['user']->id, 1, 'accreditation.submit', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    expect(fn () => app(FindAuditorOperation::class)->handle($fixture['user']->id, 1, 'invented', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    expect(fn () => app(GetAuditorProfile::class)->handle($fixture['user']->id, 0))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    expect(fn () => app(GetAuditorProfile::class)->handle(User::factory()->create()->id, 0))->toThrow(IdentityViolation::class, 'IDENTITY_NOT_LINKED');
});

it('denies incorrect journal target scope and relinking between lookup and the locked authority check', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$fixture['party']->id, 'actor_user_id' => $fixture['user']->id,
        'command' => 'accreditation.submit', 'request_id' => $request, 'target_type' => 'business', 'target_id' => $fixture['party']->id]);
    expect(fn () => app(FindAuditorOperation::class)->handle($fixture['user']->id, 1, 'accreditation.submit', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    $other = AuditorFixture::make();
    $repository = app(IdentityRepository::class);
    $calls = 0;
    $mock = $this->createMock(IdentityRepository::class);
    $mock->method('forUser')->willReturnCallback(function (int $id) use ($repository, $fixture, $other, &$calls): array {
        $snapshot = $repository->forUser($id);
        if ($calls++ === 0) {
            $fixture['user']->forceFill(['party_id' => $other['party']->id])->save();
        }

        return $snapshot;
    });
    $this->app->instance(IdentityRepository::class, $mock);
    expect(fn () => app(GetAuditorProfile::class)->handle($fixture['user']->id, 1))->toThrow(IdentityViolation::class, 'IDENTITY_RECORD_NOT_FOUND');
});

it('restricts staff review to the explicit permission and checks verified identity before granting standing', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    expect(fn () => AuditorFixture::review($fixture['user'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']))->toThrow(IdentityViolation::class);
    $fixture['party']->forceFill(['verified_at' => null])->save();
    expect(fn () => AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    expect(AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'reject', $submitted['data']['submission_id'])['code'])->toBe('ACCREDITATION_REVIEWED');
});

it('permits private certificate reads only for its current Auditor or authorized staff', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    $certificateId = $submitted['data']['submission_id'];
    $read = app(ReadAuditorCertificate::class);
    $own = $read->handle($fixture['user']->id, 1, $fixture['party']->id, $certificateId);
    expect($own['filename'])->toBe('private-certificate.pdf')->and($own['sha256'])->toBe(hash('sha256', $own['content']))
        ->and($read->handle($fixture['staff']->id, null, $fixture['party']->id, $certificateId, true))->toBe($own);
    $other = AuditorFixture::make();
    expect(fn () => $read->handle($other['user']->id, 1, $fixture['party']->id, $certificateId))->toThrow(IdentityViolation::class, 'IDENTITY_RECORD_NOT_FOUND');
    expect(fn () => $read->handle($fixture['user']->id, null, $fixture['party']->id, $certificateId))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    expect(fn () => $read->handle($fixture['user']->id, 1, $fixture['party']->id, 'missing'))->toThrow(CommandRejection::class, 'ACCREDITATION_CERTIFICATE_NOT_FOUND');
    expect(fn () => $read->handle($other['user']->id, 1, $other['party']->id, $certificateId))->toThrow(CommandRejection::class, 'ACCREDITATION_CERTIFICATE_NOT_FOUND');
    expect(fn () => $read->handle($other['user']->id, null, $fixture['party']->id, $certificateId, true))->toThrow(IdentityViolation::class);
});

it('fails closed when private certificate bytes no longer match their immutable digest', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    $certificate = AuditorCertificate::query()->firstOrFail();
    DB::statement('ALTER TABLE auditor_certificates DISABLE TRIGGER auditor_certificates_immutable');
    try {
        $certificate->forceFill(['content' => '%PDF-1.7 corrupted'])->save();
    } finally {
        DB::statement('ALTER TABLE auditor_certificates ENABLE TRIGGER auditor_certificates_immutable');
    }
    expect(fn () => app(ReadAuditorCertificate::class)->handle($fixture['user']->id, 1, $fixture['party']->id, $submitted['data']['submission_id']))
        ->toThrow(CommandRejection::class, 'ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED');
});

it('rolls back the aggregate certificate history and journal together after a persistence failure', function (): void {
    $fixture = AuditorFixture::make();
    $count = CommandOperation::query()->count();
    $event = 'eloquent.creating: '.AuditorProfileVersion::class;
    Event::listen($event, fn () => throw new RuntimeException('Synthetic persistence failure'));
    try {
        expect(fn () => AuditorFixture::submit($fixture['user']))->toThrow(RuntimeException::class, 'Synthetic persistence failure');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('auditor_profiles', 0);
    $this->assertDatabaseCount('auditor_certificates', 0);
    $this->assertDatabaseCount('auditor_profile_versions', 0);
    $this->assertDatabaseCount('command_operations', $count);
});

it('enforces immutable history and certificates in PostgreSQL', function (): void {
    $version = AuditorProfileVersion::factory()->create();
    $certificate = AuditorCertificate::factory()->create();
    foreach ([$version, $certificate] as $record) {
        expect(fn () => DB::transaction(fn () => $record->forceFill(['actor_user_id' => 42])->save()))->toThrow(QueryException::class, 'immutable');
        expect(fn () => DB::transaction(fn () => $record->delete()))->toThrow(QueryException::class, 'immutable');
    }
    expect(fn () => DB::transaction(fn () => AuditorProfile::factory()->create(['revision' => 0])))->toThrow(QueryException::class, 'auditor_profile_revision');
    expect(fn () => DB::transaction(fn () => AuditorProfile::factory()->create(['party_id' => AuditorProfile::query()->firstOrFail()->party_id])))->toThrow(QueryException::class);
});

it('reverses and reapplies the isolated accreditation schema', function (): void {
    $migration = require database_path('migrations/2026_09_24_093501_create_auditor_profiles_and_accreditation_history.php');
    $migration->down();
    expect(Schema::hasTable('auditor_profiles'))->toBeFalse()->and(Schema::hasTable('auditor_certificates'))->toBeFalse();
    $migration->up();
    expect(Schema::hasTable('auditor_profile_versions'))->toBeTrue();
    expect(AuditorProfileVersion::factory()->create()->snapshot['revision'])->toBe(1);
});
