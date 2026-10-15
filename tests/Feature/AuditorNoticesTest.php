<?php

declare(strict_types=1);

use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Models\AuditAssignment;
use App\Models\BusinessProfile;
use App\Models\PartyNotice;
use App\Models\User;
use App\Notifications\Auditor\AccreditationDecided;
use App\Notifications\Auditor\AuditJobType;
use App\Notifications\Auditor\JobOffered;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;

/*
 * The notices behind the Auditor bell (BRS v2.0 NTF-R01): the verification desk's accreditation
 * decision and each job dispatch offers the partner, in-app and by e-mail, held by the partner's
 * own Party.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    Notification::fake();
});

it('tells the partner their accreditation is approved, once, in-app and by e-mail', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    $request = (string) Str::uuid();
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id'], $request);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id'], $request);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 2, 'recheck');

    $notice = PartyNotice::query()->sole();
    expect([$notice->party_id, $notice->kind, $notice->facts])->toEqual([$fixture['party']->id, 'auditor.accreditation_approved',
        ['licence' => 'SYNTHETIC-CPA', 'expires_on' => now()->addYear()->format('Y-m-d')]]);
    Notification::assertSentToTimes($fixture['user'], AccreditationDecided::class, 1);
    Notification::assertSentTo($fixture['user'], AccreditationDecided::class,
        fn (AccreditationDecided $mail): bool => $mail->rejectionReason === null && $mail->url === route('auditor.profile'));
});

it('tells the partner why their accreditation was rejected', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'reject', $submitted['data']['submission_id']);

    expect(PartyNotice::query()->sole()->only(['kind', 'facts']))->toEqual(['kind' => 'auditor.accreditation_rejected', 'facts' => ['reason' => 'Synthetic reviewed evidence.']]);
    Notification::assertSentTo($fixture['user'], AccreditationDecided::class,
        fn (AccreditationDecided $mail): bool => $mail->rejectionReason === 'Synthetic reviewed evidence.');
});

it('reminds an active partner to renew once, from 45 Kigali days before the licence lapses, unless a renewal is pending', function (): void {
    $fixture = AuditorFixture::make();
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', AuditorFixture::submit($fixture['user'])['data']['submission_id']);
    $renewing = AuditorFixture::make();
    AuditorFixture::review($renewing['staff'], $renewing['party']->id, 1, 'approve', AuditorFixture::submit($renewing['user'])['data']['submission_id']);
    $expiresOn = now()->addYear()->format('Y-m-d');
    $lastDay = CarbonImmutable::parse($expiresOn, 'Africa/Kigali');

    $this->travelTo($lastDay->subDays(46)->setTime(23, 30));
    expect(Artisan::call('auditors:remind-renewals'))->toBe(0)->and(Artisan::output())->toContain('Reminded 0 Audit Partners to renew.');
    app(SubmitAuditorAccreditation::class)->handle($renewing['user']->id, 1, 2, 'SYNTHETIC-CPA', $lastDay->addYear()->format('Y-m-d'),
        'renewal.pdf', "%PDF-1.7\nSynthetic renewal\n%%EOF", (string) Str::uuid(), true);

    $this->travelTo($lastDay->subDays(45)->setTime(0, 5));
    expect(Artisan::call('auditors:remind-renewals'))->toBe(0)->and(Artisan::output())->toContain('Reminded 1 Audit Partners to renew.');
    Artisan::call('auditors:remind-renewals');
    $this->travelTo($lastDay->setTime(23, 59));
    Artisan::call('auditors:remind-renewals');

    $notice = PartyNotice::query()->where('kind', 'auditor.accreditation_renewal_due')->sole();
    expect([$notice->party_id, $notice->facts])->toEqual([$fixture['party']->id,
        ['licence' => 'SYNTHETIC-CPA', 'expires_on' => $expiresOn]]);
    Notification::assertSentToTimes($fixture['user'], AccreditationDecided::class, 1);
    Notification::assertCount(2);
    $this->actingAs($fixture['user'])->getJson(route('auditor.notices'))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'auditor.accreditation_renewal_due')->assertJsonPath('data.notices.0.link', ['url' => '/auditor/profile', 'method' => 'get']);

    $this->travelTo($lastDay->addDay()->setTime(0, 5));
    expect(Artisan::call('auditors:remind-renewals'))->toBe(0)->and(Artisan::output())->toContain('Reminded 0 Audit Partners to renew.');
});

it('offers each dispatched job to its partner, and the next partner after a decline', function (): void {
    $fixture = AuditAssignmentFixture::make();
    $assignment = AuditAssignmentFixture::request($fixture);
    $first = AuditAssignmentFixture::recipient($fixture, $assignment);
    $profile = BusinessProfile::query()->whereKey($fixture['business'])->sole();

    $offer = PartyNotice::query()->where('kind', 'auditor.job_offered')->sole();
    expect([$offer->party_id, $offer->facts, $offer->event_key])->toEqual([$first['party']->id, ['type' => 'flash', 'business' => $profile->profile['name'],
        'location' => $profile->profile['district'], 'accept_by' => $assignment->state['accept_by'], 'assignment_id' => $assignment->id],
        'audit-job-offered:'.$assignment->id.':'.$assignment->revision]);
    Notification::assertSentTo($first['user'], JobOffered::class, fn (JobOffered $mail): bool => $mail->type === AuditJobType::Flash
        && $mail->businessName === $profile->profile['name'] && $mail->url === route('auditor.jobs.index'));

    AuditAssignmentFixture::respond($first['user'], $assignment, 'decline', 'Synthetic decline.');
    $next = AuditAssignment::query()->whereKey($assignment->id)->sole();
    expect($next->party_id)->not->toBe($first['party']->id)
        ->and(PartyNotice::query()->where('kind', 'auditor.job_offered')->where('party_id', $next->party_id)->count())->toBe(1)
        ->and(PartyNotice::query()->where('kind', 'auditor.job_offered')->count())->toBe(2);
});

it('lists the partner\'s notices behind the Auditor bell and marks them read', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'reject', $submitted['data']['submission_id']);
    $notice = PartyNotice::query()->sole();

    $this->actingAs(User::factory()->create())->getJson(route('auditor.notices'))->assertOk()->assertJsonPath('data.unread', 0)->assertJsonCount(0, 'data.notices');
    $this->postJson(route('auditor.notices.read', $notice->id))->assertOk();
    expect($notice->refresh()->read_at)->toBeNull();

    $this->actingAs($fixture['user'])->getJson(route('auditor.notices'))->assertOk()->assertHeaderContains('Cache-Control', 'no-store')
        ->assertJsonPath('data.unread', 1)->assertJsonPath('data.notices.0.kind', 'auditor.accreditation_rejected')
        ->assertJsonPath('data.notices.0.link', ['url' => '/auditor/profile', 'method' => 'get'])
        ->assertJsonPath('data.notices.0.read_action', ['url' => "/auditor/notices/{$notice->id}/read", 'method' => 'post'])
        ->assertJsonPath('data.actions.read_all', ['url' => '/auditor/notices/read-all', 'method' => 'post']);
    $this->get(route('auditor.home'))->assertInertia(fn ($page) => $page->where('unread_notifications', 1)
        ->where('links.notifications', ['url' => '/auditor/notices', 'method' => 'get']));
    $this->postJson(route('auditor.notices.read', $notice->id))->assertOk()->assertJsonPath('data.unread', 0);
    $this->postJson(route('auditor.notices.read_all'))->assertOk()->assertJsonPath('data.unread', 0);
    expect($notice->refresh()->read_at)->not->toBeNull();
});

it('reads Auditor notices over API v1 with auditor:read and marks them read only with auditor:command', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    AuditAssignmentFixture::request($fixture, 'routine');
    $partner = $fixture['partners'][0]['user'];
    $offer = PartyNotice::query()->where('kind', 'auditor.job_offered')->sole();

    Sanctum::actingAs($partner, ['investor:read']);
    $this->getJson(route('api.v1.auditor.notices'))->assertForbidden();
    Sanctum::actingAs($partner, ['auditor:read']);
    $this->getJson(route('api.v1.auditor.notices'))->assertOk()->assertJsonPath('data.unread', 2)
        ->assertJsonPath('data.notices.0.id', $offer->id)->assertJsonPath('data.notices.0.facts.type', 'routine')
        ->assertJsonPath('data.notices.0.link.url', '/api/v1/auditor/jobs')
        ->assertJsonPath('data.notices.0.read_action.url', "/api/v1/auditor/notices/{$offer->id}/read");
    $this->postJson(route('api.v1.auditor.notices.read', $offer->id))->assertForbidden();
    Sanctum::actingAs($partner, ['auditor:read', 'auditor:command']);
    $this->postJson(route('api.v1.auditor.notices.read', $offer->id))->assertOk()->assertJsonPath('data.unread', 1);
});
