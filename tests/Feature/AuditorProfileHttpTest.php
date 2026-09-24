<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditorProfileStore;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\AuditorFixture;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-09-24T09:00:00Z'));
});

const PROFILE_HTTP_PDF = "%PDF-1.7\nSynthetic HTTP certificate\n%%EOF";

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function profileHttpSubmission(array $overrides = []): array
{
    return ['licence' => 'SYNTHETIC-HTTP-CPA', 'expires_on' => '2027-09-30',
        'certificate' => UploadedFile::fake()->createWithContent('licence.pdf', PROFILE_HTTP_PDF),
        'identity_context_revision' => 1, 'expected_revision' => 0, 'request_id' => (string) Str::uuid(), ...$overrides];
}

/**
 * @param  array<string, mixed>  $payload
 * @return TestResponse<Response>
 */
function profileHttpPost(string $route, array $payload): TestResponse
{
    return post(route($route), $payload, ['Accept' => 'application/json']);
}

/**
 * Compares a live page with the fixture that stands for its TypeScript contract, key by key. A
 * null on either side, or a list, ends the comparison at that key.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function profileHttpSameShape(array $live, array $fixture, string $path = ''): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        if (is_array($value) && is_array($fixture[$key]) && ! array_is_list($value) && ! array_is_list($fixture[$key])) {
            profileHttpSameShape($value, $fixture[$key], $path.'.'.$key);
        }
    }
}

/** @return array<string, mixed> */
function profileHttpFixture(string $name): array
{
    /** @var array{props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);

    return $fixture['props'];
}

/**
 * The live page's own props, without the props every Inertia page shares.
 *
 * @return array<string, mixed>
 */
function profileHttpProps(User $user): array
{
    $props = [];
    actingAs($user)->get(route('auditor.profile'))->assertOk()
        ->assertInertia(function (Assert $page) use (&$props): Assert {
            $props = $page->toArray()['props'];

            return $page->component('auditor/profile');
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/** @return array{user: User, party: Party, staff: User, submission: string} */
function profileHttpApproved(): array
{
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);

    return [...$fixture, 'submission' => $submitted['data']['submission_id']];
}

it('renders a first-time Auditor profile from the protected facts with the contract shape and real links', function (): void {
    $fixture = AuditorFixture::make();
    $fixture['user']->forceFill(['name' => 'Synthetic Partner'])->save();
    $props = profileHttpProps($fixture['user']);
    profileHttpSameShape($props, profileHttpFixture('auditor-profile-first-time'));
    expect($props)->toMatchArray([
        'contract_version' => 'auditor-filing-v1', 'identity_context_revision' => 1, 'server_time' => now()->toIso8601String(),
        'allowed_actions' => ['accreditation.submit'], 'section' => 'accreditation',
        'auditor' => ['name' => 'Synthetic Partner', 'firm' => null, 'accreditation' => null, 'avatar_url' => null, 'since_year' => null],
        'quality_score' => null, 'on_time_pct' => null, 'jobs_done' => 0, 'open_jobs' => 0,
        'standing' => ['current' => false, 'reason' => 'ACCREDITATION_REQUIRED'],
        'accreditation' => ['status' => 'none', 'revision' => 0, 'licence' => null, 'expires_on' => null, 'days_left' => null, 'submission' => ['status' => 'none']],
        'availability' => ['accepting' => false, 'radius_km' => 30, 'max_active' => 3, 'revision' => 0, 'update' => ['url' => '/auditor/availability', 'method' => 'post']],
        'actions' => ['submit' => ['url' => '/auditor/accreditation', 'method' => 'post'],
            'renew' => ['url' => '/auditor/accreditation/renewal', 'method' => 'post'],
            'withdraw' => ['url' => '/auditor/accreditation/withdrawal', 'method' => 'post']],
    ])->and($props['links'])->toBe([
        'home' => ['url' => '/auditor', 'method' => 'get'], 'jobs' => null, 'portfolio' => null,
        'profile' => ['url' => '/auditor/profile', 'method' => 'get'], 'launcher' => ['url' => '/dashboard', 'method' => 'get'],
        'sections' => ['accreditation' => ['url' => '/auditor/profile', 'method' => 'get'],
            'availability' => ['url' => '/auditor/profile?section=availability', 'method' => 'get']],
        'operation' => ['url' => '/auditor/operations/{request_id}', 'method' => 'get'],
        'certificate' => null, 'submitted_certificate' => null,
    ]);
    $this->get(route('auditor.profile', ['section' => 'availability']))->assertInertia(fn (Assert $page): Assert => $page->where('section', 'availability'));
    $this->get(route('auditor.profile', ['section' => ['availability']]))->assertInertia(fn (Assert $page): Assert => $page->where('section', 'accreditation'));
    $this->get(route('auditor.profile', ['section' => 'earnings']))->assertInertia(fn (Assert $page): Assert => $page->where('section', 'accreditation'));
    $this->assertDatabaseCount('auditor_profiles', 0);
});

it('links the pending and approved certificates and derives active, lapsed and expired standing afresh', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    profileHttpSameShape(profileHttpProps($fixture['user']), profileHttpFixture('auditor-profile-first-time-pending'));
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('allowed_actions', ['accreditation.withdraw'])->where('accreditation.submission.status', 'pending')
        ->where('links.certificate', null)
        ->where('links.submitted_certificate', ['url' => '/auditor/accreditation/certificates/'.$submitted['data']['submission_id'], 'method' => 'get']));
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
    profileHttpSameShape(profileHttpProps($fixture['user']), profileHttpFixture('auditor-profile'));
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('allowed_actions', ['accreditation.renew', 'availability.update'])
        ->where('standing', ['current' => true, 'reason' => null])->where('accreditation.status', 'active')
        ->where('accreditation.licence', 'SYNTHETIC-CPA')->where('accreditation.days_left', 365)
        ->where('links.certificate', ['url' => '/auditor/accreditation/certificates/'.$submitted['data']['submission_id'], 'method' => 'get'])
        ->where('links.submitted_certificate', null));
    profileHttpPost('auditor.availability.update', ['accepting' => true, 'identity_context_revision' => 1, 'expected_revision' => 2,
        'request_id' => (string) Str::uuid()])->assertOk();
    $this->travel(31)->days();
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('standing', ['current' => false, 'reason' => 'STANDING_CHECK_REQUIRED'])->where('availability.accepting', true)
        ->where('allowed_actions', ['accreditation.renew', 'availability.update']));
});

it('shows an expired licence at the Rwanda date boundary', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-24T21:59:59Z'));
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $submitted = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['expires_on' => '2026-09-24']))
        ->assertOk()->json('data.submission_id');
    AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted);
    $this->travel(1)->seconds();
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('standing', ['current' => false, 'reason' => 'ACCREDITATION_EXPIRED'])->where('accreditation.status', 'expired')
        ->where('accreditation.days_left', -1)->where('allowed_actions', ['accreditation.renew']));
});

it('denies the profile to guests, other roles and an Auditor without two-factor authentication', function (): void {
    $this->get(route('auditor.profile'))->assertRedirect(route('login'));
    $party = Party::factory()->verified()->create();
    $investor = User::factory()->withTwoFactor()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);
    app(SelectActiveRole::class)->handle($investor->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($investor)->get(route('auditor.profile'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'ROLE_NOT_AVAILABLE'));
    $this->getJson(route('auditor.profile'))->assertForbidden()->assertJsonPath('code', 'ROLE_NOT_AVAILABLE');
    $fixture = AuditorFixture::make();
    $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->actingAs($fixture['user'])->get(route('auditor.profile'))
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'MFA_REQUIRED'));
});

it('records a multipart first-time submission, replays the same request ID and continues to the profile', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $payload = profileHttpSubmission();
    $first = profileHttpPost('auditor.accreditation.submit', $payload)->assertOk()
        ->assertJsonPath('status', 'completed')->assertJsonPath('code', 'ACCREDITATION_SUBMITTED')->assertJsonPath('revision', 1)
        ->assertJsonPath('data.next', ['url' => '/auditor/profile', 'method' => 'get'])
        ->assertJsonPath('allowed_actions', ['accreditation.withdraw'])
        ->assertJsonPath('server_time', now()->toIso8601String())->assertJsonPath('recorded_at', now()->toIso8601String())
        ->assertJsonPath('errors', [])->assertJsonPath('field_errors', []);
    expect($first->json())->toHaveKeys(['operation_id', 'status', 'code', 'data', 'revision', 'policy_version', 'server_time', 'recorded_at',
        'allowed_actions', 'field_errors', 'errors'])->and($first->json('data.submission_id'))->toBeString();
    $this->travel(5)->minutes();
    $payload['certificate'] = UploadedFile::fake()->createWithContent('licence.pdf', PROFILE_HTTP_PDF);
    $replay = profileHttpPost('auditor.accreditation.submit', $payload)->assertOk();
    expect($replay->json('operation_id'))->toBe($first->json('operation_id'))->and($replay->json('recorded_at'))->toBe($first->json('recorded_at'))
        ->and($replay->json('server_time'))->toBe(now()->toIso8601String())->and($replay->json('data'))->toBe($first->json('data'));
    $this->assertDatabaseCount('auditor_certificates', 1);
    $payload['licence'] = 'CHANGED';
    $payload['certificate'] = UploadedFile::fake()->createWithContent('licence.pdf', PROFILE_HTTP_PDF);
    profileHttpPost('auditor.accreditation.submit', $payload)->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page->where('accreditation.submission.status', 'pending'));
});

it('refuses a stale revision with 409 and records the domain 422 with its errors bag for the lookup to replay', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['expected_revision' => 4]))->assertConflict()
        ->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('revision', 0)
        ->assertJsonPath('allowed_actions', ['accreditation.submit']);
    $request = (string) Str::uuid();
    $refused = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission([
        'request_id' => $request, 'certificate' => UploadedFile::fake()->createWithContent('licence.exe', 'MZ executable'),
    ]))->assertUnprocessable()->assertJsonPath('code', 'ACCREDITATION_TYPE_UNSUPPORTED')
        ->assertJsonPath('errors.certificate', ['Upload a PDF, PNG or JPEG certificate.'])->assertJsonPath('data', []);
    $this->travel(1)->minutes();
    $this->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))
        ->assertUnprocessable()->assertJsonPath('code', 'ACCREDITATION_TYPE_UNSUPPORTED')
        ->assertJsonPath('errors.certificate', ['Upload a PDF, PNG or JPEG certificate.'])
        ->assertJsonPath('operation_id', $refused->json('operation_id'))->assertJsonPath('recorded_at', $refused->json('recorded_at'))
        ->assertJsonPath('server_time', now()->toIso8601String());
    profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['expires_on' => '2020-01-01']))->assertUnprocessable()
        ->assertJsonPath('code', 'ACCREDITATION_EXPIRY_INVALID')->assertJsonPath('errors.expires_on', ['Enter a current certificate expiry date.']);
    profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['licence' => '', 'certificate' => null, 'request_id' => 'not-a-uuid']))
        ->assertUnprocessable()->assertJsonValidationErrors(['licence', 'certificate', 'request_id']);
    $this->assertDatabaseCount('auditor_profiles', 0);
});

it('checks the current identity context and role before a command is recorded', function (string $change, int $status, string $code): void {
    $fixture = AuditorFixture::make();
    $count = CommandOperation::query()->count();
    $revision = 1;
    if ($change === 'context') {
        $revision = 0;
    } elseif ($change === 'mfa') {
        $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } else {
        RoleMembership::query()->where('party_id', $fixture['party']->id)->update(['status' => 'revoked']);
    }
    $this->actingAs($fixture['user']);
    profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['identity_context_revision' => $revision]))
        ->assertStatus($status)->assertJsonPath('code', $code);
    profileHttpPost('auditor.availability.update', ['accepting' => false, 'identity_context_revision' => $revision, 'expected_revision' => 0,
        'request_id' => (string) Str::uuid()])->assertStatus($status)->assertJsonPath('code', $code);
    expect(CommandOperation::query()->count())->toBe($count);
})->with([
    'stale identity context' => ['context', 409, 'ACTIVE_ROLE_REVISION_CONFLICT'],
    'two-factor removed' => ['mfa', 403, 'MFA_REQUIRED'],
    'membership revoked' => ['membership', 403, 'ROLE_MEMBERSHIP_REQUIRED'],
]);

it('refuses commands from a login without an Auditor role or a linked Party', function (): void {
    $this->actingAs(User::factory()->create());
    profileHttpPost('auditor.accreditation.withdraw', ['submission_id' => 'x', 'identity_context_revision' => 0, 'expected_revision' => 0,
        'request_id' => (string) Str::uuid()])->assertStatus(403)->assertJsonPath('code', 'IDENTITY_NOT_LINKED');
    $this->getJson(route('auditor.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'accreditation.submit']))
        ->assertForbidden();
    $this->post(route('auditor.accreditation.submit'), [], ['Accept' => 'application/json'])->assertUnprocessable();
    auth()->logout();
    $this->postJson(route('auditor.availability.update'), [])->assertUnauthorized();
});

it('withdraws the named pending submission and continues to the profile', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $submission = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission())->json('data.submission_id');
    $payload = ['identity_context_revision' => 1, 'expected_revision' => 1, 'request_id' => (string) Str::uuid()];
    profileHttpPost('auditor.accreditation.withdraw', [...$payload, 'submission_id' => 'stale-submission'])->assertConflict()
        ->assertJsonPath('code', 'ACCREDITATION_SUBMISSION_STALE');
    profileHttpPost('auditor.accreditation.withdraw', [...$payload, 'request_id' => (string) Str::uuid(), 'submission_id' => $submission])
        ->assertOk()->assertJsonPath('code', 'ACCREDITATION_WITHDRAWN')->assertJsonPath('revision', 2)
        ->assertJsonPath('data.next', ['url' => '/auditor/profile', 'method' => 'get'])->assertJsonPath('allowed_actions', ['accreditation.submit']);
    $this->getJson(route('auditor.operations.show', ['request_id' => $payload['request_id'], 'command' => 'accreditation.withdraw']))
        ->assertConflict()->assertJsonPath('code', 'ACCREDITATION_SUBMISSION_STALE');
    $this->get(route('auditor.profile'))->assertInertia(fn (Assert $page): Assert => $page->where('accreditation.submission', ['status' => 'none']));
});

it('renews over its own route and records the renewal under its own command name', function (): void {
    $fixture = profileHttpApproved();
    $this->actingAs($fixture['user']);
    $request = (string) Str::uuid();
    profileHttpPost('auditor.accreditation.renew', profileHttpSubmission(['expected_revision' => 2, 'request_id' => $request]))->assertOk()
        ->assertJsonPath('code', 'ACCREDITATION_SUBMITTED')->assertJsonPath('allowed_actions', ['accreditation.withdraw', 'availability.update']);
    $this->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.renew']))->assertOk()
        ->assertJsonPath('code', 'ACCREDITATION_SUBMITTED')->assertJsonPath('data.next.url', '/auditor/profile');
    $this->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))->assertNotFound()
        ->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('updates availability in place and keeps a lapsed standing refusal with its own code', function (): void {
    $fixture = profileHttpApproved();
    $this->actingAs($fixture['user']);
    $request = (string) Str::uuid();
    profileHttpPost('auditor.availability.update', ['accepting' => '1', 'identity_context_revision' => 1, 'expected_revision' => 2, 'request_id' => $request])
        ->assertOk()->assertJsonPath('code', 'AVAILABILITY_UPDATED')->assertJsonPath('revision', 3)
        ->assertJsonPath('data.next', ['url' => '/auditor/profile?section=availability', 'method' => 'get']);
    $this->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'availability.update']))->assertOk()
        ->assertJsonPath('data.next.url', '/auditor/profile?section=availability');
    profileHttpPost('auditor.availability.update', ['accepting' => false, 'identity_context_revision' => 1, 'expected_revision' => 3, 'request_id' => (string) Str::uuid()])
        ->assertOk();
    $this->travel(31)->days();
    profileHttpPost('auditor.availability.update', ['accepting' => true, 'identity_context_revision' => 1, 'expected_revision' => 4, 'request_id' => (string) Str::uuid()])
        ->assertForbidden()->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'STANDING_CHECK_REQUIRED')
        ->assertJsonPath('allowed_actions', ['accreditation.renew']);
    profileHttpPost('auditor.availability.update', ['accepting' => 'maybe', 'identity_context_revision' => 1, 'expected_revision' => 4, 'request_id' => (string) Str::uuid()])
        ->assertUnprocessable()->assertJsonValidationErrors(['accepting']);
});

it('looks up only this Party recorded outcomes', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $request = (string) Str::uuid();
    $recorded = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission(['request_id' => $request]))->assertOk();
    $this->getJson(route('auditor.operations.show', ['request_id' => strtoupper($request), 'command' => 'accreditation.submit']))->assertOk()
        ->assertJsonPath('operation_id', $recorded->json('operation_id'))->assertJsonPath('allowed_actions', ['accreditation.withdraw']);
    $this->getJson(route('auditor.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'accreditation.submit']))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND')->assertJsonPath('message', 'OPERATION_NOT_FOUND');
    $this->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'invented.command']))->assertNotFound()
        ->assertJsonPath('code', 'OPERATION_NOT_FOUND');
    $this->getJson(route('auditor.operations.show', ['request_id' => $request]))->assertUnprocessable()->assertJsonValidationErrors(['command']);
    $this->getJson('/auditor/operations/not-a-uuid?command=accreditation.submit')->assertNotFound();
    $other = AuditorFixture::make();
    $this->actingAs($other['user'])->getJson(route('auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('serves only the Auditor own certificate privately under a neutral name', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $submission = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission())->json('data.submission_id');
    $response = $this->get(route('auditor.accreditation.certificates.show', $submission))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'attachment; filename=licence-certificate-'.$submission.'.pdf')
        ->assertHeader('Repr-Digest', 'sha-256=:'.base64_encode(hash('sha256', PROFILE_HTTP_PDF, true)).':');
    expect($response->getContent())->toBe(PROFILE_HTTP_PDF)
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store', 'private');
    $this->getJson(route('auditor.accreditation.certificates.show', str_repeat('0', 26)))->assertNotFound()
        ->assertJsonPath('code', 'ACCREDITATION_CERTIFICATE_NOT_FOUND');
    $this->get('/auditor/accreditation/certificates/..%2F..%2Fsecret')->assertNotFound();
    $other = AuditorFixture::make();
    $this->actingAs($other['user'])->getJson(route('auditor.accreditation.certificates.show', $submission))->assertNotFound()
        ->assertJsonPath('code', 'ACCREDITATION_CERTIFICATE_NOT_FOUND');
    $other['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->getJson(route('auditor.accreditation.certificates.show', $submission))->assertForbidden()->assertJsonPath('code', 'MFA_REQUIRED');
});

it('renders the error page, not raw JSON, when a browser page read is refused', function (): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);

    $this->get(route('auditor.accreditation.certificates.show', str_repeat('0', 26)))->assertNotFound()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')
            ->where('code', 'ACCREDITATION_CERTIFICATE_NOT_FOUND'));

    $this->mock(AuditorProfileStore::class, function (MockInterface $store): void {
        $store->shouldReceive('accreditation')->andThrow(new CommandRejection('ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED'));
    });
    $this->get(route('auditor.profile'))->assertStatus(409)
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')
            ->where('code', 'ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED'));
    $this->getJson(route('auditor.profile'))->assertStatus(409)
        ->assertExactJson(['message' => 'ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED', 'code' => 'ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED']);
});

it('names each image certificate by its verified type', function (string $name, string $content, string $type, string $extension): void {
    $fixture = AuditorFixture::make();
    $this->actingAs($fixture['user']);
    $submission = profileHttpPost('auditor.accreditation.submit', profileHttpSubmission([
        'certificate' => UploadedFile::fake()->createWithContent($name, $content),
    ]))->assertOk()->json('data.submission_id');
    $this->get(route('auditor.accreditation.certificates.show', $submission))->assertOk()->assertHeader('Content-Type', $type)
        ->assertHeader('Content-Disposition', "attachment; filename=licence-certificate-{$submission}.{$extension}");
})->with([
    'png' => ['licence.png', "\x89PNG\r\n\x1a\nsynthetic", 'image/png', 'png'],
    'jpeg' => ['licence.jpeg', "\xff\xd8\xffsynthetic", 'image/jpeg', 'jpg'],
]);

it('offers the same profile commands lookup and certificate over the token API', function (): void {
    $fixture = AuditorFixture::make();
    Sanctum::actingAs($fixture['user'], ['identity:access']);
    $this->getJson(route('api.v1.auditor.profile'))->assertForbidden();
    $this->post(route('api.v1.auditor.accreditation.submit'), profileHttpSubmission(), ['Accept' => 'application/json'])->assertForbidden();
    Sanctum::actingAs($fixture['user'], ['auditor:read', 'auditor:command']);
    $web = null;
    $this->actingAs($fixture['user'], 'web')->get(route('auditor.profile'))->assertInertia(function (Assert $page) use (&$web): Assert {
        $web = $page->toArray()['props'];

        return $page;
    });
    Sanctum::actingAs($fixture['user'], ['auditor:read', 'auditor:command']);
    $api = $this->getJson(route('api.v1.auditor.profile'))->assertOk()->json('data');
    expect(array_keys($api))->toEqual(array_keys(array_intersect_key($web, $api)))
        ->and($api['accreditation'])->toBe($web['accreditation'])->and($api['allowed_actions'])->toBe($web['allowed_actions'])
        ->and($api['actions']['submit']['url'])->toBe('/api/v1/auditor/accreditation')
        ->and($api['availability']['update']['url'])->toBe('/api/v1/auditor/availability')
        ->and($api['links']['operation']['url'])->toBe('/api/v1/auditor/operations/{request_id}')
        ->and($api['links']['profile']['url'])->toBe('/auditor/profile');
    $request = (string) Str::uuid();
    $submitted = $this->post(route('api.v1.auditor.accreditation.submit'), profileHttpSubmission(['request_id' => $request]), ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('code', 'ACCREDITATION_SUBMITTED')->assertJsonPath('data.next.url', '/auditor/profile');
    $this->getJson(route('api.v1.auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))->assertOk()
        ->assertJsonPath('operation_id', $submitted->json('operation_id'));
    $submission = $submitted->json('data.submission_id');
    $this->getJson(route('api.v1.auditor.profile'))->assertJsonPath('data.links.submitted_certificate.url', '/api/v1/auditor/accreditation/certificates/'.$submission);
    $this->get(route('api.v1.auditor.accreditation.certificates.show', $submission))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->postJson(route('api.v1.auditor.accreditation.withdraw'), ['submission_id' => $submission, 'identity_context_revision' => 1,
        'expected_revision' => 1, 'request_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('code', 'ACCREDITATION_WITHDRAWN');
    $this->postJson(route('api.v1.auditor.availability.update'), ['accepting' => true, 'identity_context_revision' => 1,
        'expected_revision' => 2, 'request_id' => (string) Str::uuid()])->assertForbidden()->assertJsonPath('code', 'ACCREDITATION_REQUIRED');
    Sanctum::actingAs($fixture['user'], ['auditor:read']);
    $this->postJson(route('api.v1.auditor.accreditation.withdraw'), [])->assertForbidden();
    $this->get(route('api.v1.auditor.accreditation.certificates.show', $submission))->assertOk();
    $this->getJson(route('api.v1.auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))->assertOk();
    Sanctum::actingAs($fixture['user'], ['auditor:command']);
    $this->getJson(route('api.v1.auditor.operations.show', ['request_id' => $request, 'command' => 'accreditation.submit']))->assertForbidden();
    $this->get(route('api.v1.auditor.accreditation.certificates.show', $submission))->assertForbidden();
});
