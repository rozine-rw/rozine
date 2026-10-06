<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditorAccreditation;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Http\Resources\AuditorAccreditationResource;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Support\AuditorFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-24T21:59:59Z'));
});

it('exposes unaccredited status and available commands without manufacturing a licence', function (): void {
    $fixture = AuditorFixture::make();
    $data = app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1);
    $resource = (new AuditorAccreditationResource($data))->resolve();
    expect($resource['standing'])->toBe(['current' => false, 'reason' => 'ACCREDITATION_REQUIRED'])
        ->and($resource['accreditation'])->toBe(['status' => 'none', 'revision' => 0, 'licence' => null, 'expires_on' => null,
            'days_left' => null, 'submission' => ['status' => 'none']])
        ->and($resource['availability'])->toBe(['accepting' => false, 'radius_km' => 30, 'max_active' => 3, 'revision' => 0])
        ->and($resource['allowed_actions'])->toBe(['accreditation.submit'])
        ->and($resource['identity_context_revision'])->toBe(1)->and($resource['contract_version'])->toBe('auditor-filing-v1')
        ->and($resource['server_time'])->toBe(now()->toIso8601String());
    $this->assertDatabaseCount('auditor_profiles', 0);
});

it('describes pending private evidence without inventing capture or device attestation', function (): void {
    $fixture = AuditorFixture::make();
    $submission = AuditorFixture::submit($fixture['user']);
    $data = app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1);
    $pending = $data['accreditation']['submission'];
    expect($pending['status'])->toBe('pending')->and($pending['id'])->toBe($submission['data']['submission_id'])
        ->and($pending['submitted_on'])->toBe('2026-09-24')->and($data['allowed_actions'])->toBe(['accreditation.withdraw'])
        ->and($pending['evidence'])->toBe(['evidence_id' => $pending['id'], 'kind' => 'licence_certificate',
            'sha256' => hash('sha256', "%PDF-1.7\nSynthetic private certificate\n%%EOF"), 'captured_at' => null, 'source' => 'web_upload',
            'device_attestation' => 'unavailable', 'position' => null, 'accuracy_m' => null]);
    expect(json_encode($data, JSON_THROW_ON_ERROR))->not->toContain('private-certificate.pdf', 'Synthetic private certificate');
});

it('derives current standing afresh after the 30 day boundary while preserving the saved preference', function (): void {
    $fixture = AuditorFixture::make();
    $submission = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submission['data']['submission_id']);
    app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 2, true, (string) Str::uuid());
    $action = app(GetAuditorAccreditation::class);
    $this->travel(30)->days();
    expect($action->handle($fixture['user']->id, 1)['standing'])->toBe(['current' => true, 'reason' => null]);
    $this->travel(1)->seconds();
    $expiredCheck = $action->handle($fixture['user']->id, 1);
    expect($expiredCheck['standing'])->toBe(['current' => false, 'reason' => 'STANDING_CHECK_REQUIRED'])
        ->and($expiredCheck['availability']['accepting'])->toBeTrue()->and($expiredCheck['availability']['revision'])->toBe(3)
        ->and($expiredCheck['allowed_actions'])->toBe(['accreditation.renew', 'availability.update']);
    $this->assertDatabaseCount('auditor_profile_versions', 3);
    expect(json_encode((new AuditorAccreditationResource($expiredCheck))->resolve(), JSON_THROW_ON_ERROR))
        ->not->toContain('check_reference', 'synthetic-manual-register-check', 'Synthetic reviewed evidence.', 'checked_at');
    app(SetAuditorAvailability::class)->handle($fixture['user']->id, 1, 3, false, (string) Str::uuid());
    expect($action->handle($fixture['user']->id, 1)['allowed_actions'])->toBe(['accreditation.renew']);
});

it('marks a licence expired at the Rwanda date boundary without changing immutable history', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = app(SubmitAuditorAccreditation::class)->handle($fixture['user']->id, 1, 0, 'CPA', '2026-09-24', 'licence.pdf', '%PDF-1.7 certificate', (string) Str::uuid());
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    $action = app(GetAuditorAccreditation::class);
    $before = $action->handle($fixture['user']->id, 1);
    expect($before['standing']['current'])->toBeTrue()->and($before['accreditation']['status'])->toBe('active')->and($before['accreditation']['days_left'])->toBe(0);
    $this->travel(1)->seconds();
    $after = $action->handle($fixture['user']->id, 1);
    expect($after['standing'])->toBe(['current' => false, 'reason' => 'ACCREDITATION_EXPIRED'])
        ->and($after['accreditation']['status'])->toBe('expired')->and($after['accreditation']['days_left'])->toBe(-1)
        ->and(AuditorProfileVersion::query()->where('revision', 2)->firstOrFail()->snapshot['state']['standing']['status'])->toBe('active');
});

it('maps suspended and revoked standing to the existing suspended interface state', function (string $decision): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 2, $decision);
    $data = app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1);
    expect($data['standing'])->toBe(['current' => false, 'reason' => 'ACCREDITATION_SUSPENDED'])
        ->and($data['accreditation']['status'])->toBe('suspended')->and($data['availability']['accepting'])->toBeFalse();
})->with(['suspend', 'revoke']);

it('refuses incomplete certificate lineage and rechecks role authority before exposing the view', function (): void {
    $fixture = AuditorFixture::make();
    AuditorFixture::submit($fixture['user']);
    $profile = AuditorProfile::query()->firstOrFail();
    $state = $profile->state;
    $state['submission'] = ['status' => 'pending', 'id' => strtolower((string) Str::ulid()), 'licence' => 'CPA', 'expires_on' => '2027-01-01', 'submitted_at' => '2026-09-24T21:59:59Z'];
    $profile->forceFill(['state' => $state])->save();
    expect(fn () => app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1))->toThrow(CommandRejection::class, 'ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED');
    RoleMembership::query()->where('party_id', $fixture['party']->id)->update(['status' => 'revoked']);
    expect(fn () => app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED');
});

it('returns a journaled staff validation error when a pending certificate expires before approval', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = app(SubmitAuditorAccreditation::class)->handle($fixture['user']->id, 1, 0, 'CPA', '2026-09-24', 'licence.pdf', '%PDF-1.7 certificate', (string) Str::uuid());
    $this->travel(1)->seconds();
    $denied = AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    expect($denied['code'])->toBe('ACCREDITATION_SUBMISSION_EXPIRED')->and($denied['http_status'])->toBe(422)
        ->and($denied['field_errors'])->toHaveKey('submission_id')
        ->and(AuditorProfile::query()->firstOrFail()->revision)->toBe(1);
    $data = app(GetAuditorAccreditation::class)->handle($fixture['user']->id, 1);
    expect($data['accreditation']['status'])->toBe('none')->and($data['accreditation']['submission']['submitted_on'])->toBe('2026-09-24');
});
