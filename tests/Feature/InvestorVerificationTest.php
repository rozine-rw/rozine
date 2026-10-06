<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\InvestorVerificationStore;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Identity\VerificationDocument;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\InvestorVerification;
use App\Models\InvestorVerificationDocument;
use App\Models\InvestorVerificationVersion;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
    $this->person = User::factory()->create(['party_id' => Party::factory()]);
});

/**
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function kycCommand(array $fields = []): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 0, ...$fields];
}

function kycFile(string $name = 'id.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "\x89PNG\r\n\x1a\nsynthetic identity image");
}

function kycRevision(User $user): int
{
    return (int) InvestorVerification::query()->where('party_id', $user->party_id)->value('revision');
}

/** @param  array<string, mixed>  $answers */
function kycSave(User $user, string $step, array $answers): void
{
    actingAs($user)->post(route('investor.verification.save'), kycCommand(['expected_revision' => kycRevision($user), 'step' => $step, ...$answers]))
        ->assertRedirect(route('investor.verification'))->assertSessionHasNoErrors();
}

function kycUpload(User $user, string $slot): void
{
    actingAs($user)->post(route('investor.verification.upload'), kycCommand(['expected_revision' => kycRevision($user), 'slot' => $slot, 'file' => kycFile()]))
        ->assertRedirect(route('investor.verification'))->assertSessionHasNoErrors();
}

function kycReady(User $user, string $type = 'national_id'): void
{
    kycSave($user, 'personal', ['date_of_birth' => '1/5/1990']);
    kycUpload($user, 'id_front');
    if ($type !== 'passport') {
        kycUpload($user, 'id_back');
    }
    kycSave($user, 'document', ['id_type' => $type, 'id_number' => $type === 'passport' ? 'pc 123456' : '1 1990 8 0012345 6 78']);
    kycUpload($user, 'selfie');
}

test('an unverified person starts an empty individual submission', function (): void {
    $this->actingAs($this->person)->get(route('investor.verification'))
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page) => $page->component('investor/verification')
            ->where('investor_type', 'individual')->where('step', 'personal')->where('status', 'draft')->where('revision', 0)
            ->where('identity_context_revision', 0)->where('decision_reason', null)->where('country', 'Rwanda')
            ->where('date_of_birth', '')->where('id_type', 'national_id')->where('id_number', '')
            ->where('uploads.front.status', 'missing')->where('uploads.selfie.status', 'missing')
            ->where('links.back.url', '/dashboard')
            ->where('actions.save', ['url' => '/investor/verification/steps', 'method' => 'post'])
            ->where('actions.upload', ['url' => '/investor/verification/documents', 'method' => 'post'])
            ->where('actions.submit', ['url' => '/investor/verification/submit', 'method' => 'post']));
});

test('a verified person is sent back to the dashboard', function (): void {
    $verified = User::factory()->create(['party_id' => Party::factory()->verified()]);

    $this->actingAs($verified)->get(route('investor.verification'))->assertRedirect(route('dashboard'));
    $this->actingAs($verified)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => 0]))
        ->assertSessionHasErrors(['form' => 'Your identity is already verified.']);
});

test('a person completes every step and submits for Compliance review', function (): void {
    kycReady($this->person);

    $this->actingAs($this->person)->get(route('investor.verification'))
        ->assertInertia(fn (Assert $page) => $page->where('step', 'liveness')->where('date_of_birth', '01 / 05 / 1990')
            ->where('id_number', '1199080012345678')->where('uploads.back.status', 'uploaded')->where('uploads.selfie.status', 'uploaded'));

    $this->actingAs($this->person)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => kycRevision($this->person)]))
        ->assertRedirect(route('investor.verification'))->assertSessionHasNoErrors();

    $record = InvestorVerification::query()->where('party_id', $this->person->party_id)->sole();
    expect($record->status)->toBe('submitted')->and($record->revision)->toBe(6)->and($record->submitted_at?->toDateString())->toBe('2026-10-06')
        ->and(InvestorVerificationVersion::query()->where('investor_verification_id', $record->id)->orderBy('revision')->pluck('command')->all())
        ->toBe(['verification.save', 'verification.upload', 'verification.upload', 'verification.save', 'verification.upload', 'verification.submit'])
        ->and(Party::query()->find($this->person->party_id)?->verified_at)->toBeNull();

    $documents = InvestorVerificationDocument::query()->where('investor_verification_id', $record->id)->get();
    expect($documents->pluck('slot')->sort()->values()->all())->toBe(['back', 'front', 'selfie'])
        ->and($documents->first()?->media_type)->toBe('image/png')
        ->and($documents->first()?->sha256)->toBe(hash('sha256', "\x89PNG\r\n\x1a\nsynthetic identity image"))
        ->and(DB::table('investor_verification_documents')->value('content'))->not->toContain('synthetic identity image');

    $this->actingAs($this->person)->post(route('investor.verification.save'), kycCommand(['expected_revision' => 6, 'step' => 'personal', 'date_of_birth' => '1/5/1990']))
        ->assertSessionHasErrors(['form' => 'Your details are with our Compliance team, so they can no longer be changed.']);
});

test('a passport needs only its photo page', function (): void {
    kycReady($this->person, 'passport');

    expect(InvestorVerification::query()->sole()->state['id_number'])->toBe('PC123456')
        ->and((new InvestorVerificationCase)->identityReference(InvestorVerification::query()->sole()->state))->toBe('passport:PC123456')
        ->and((new InvestorVerificationCase)->identityReference([...(new InvestorVerificationCase)->empty(), 'id_number' => '1199080012345678']))->toBe('rw-nid:1199080012345678')
        ->and((new InvestorVerificationCase)->identityReference([...(new InvestorVerificationCase)->empty(), 'id_type' => 'drivers_license', 'id_number' => '1199080012345678']))->toBe('rw-dl:1199080012345678');
});

test('each answer is checked before the step moves on', function (string $step, array $answers, string $field, string $message): void {
    if ($step === 'document') {
        kycSave($this->person, 'personal', ['date_of_birth' => '1/5/1990']);
        kycUpload($this->person, 'id_front');
    }

    $this->actingAs($this->person)->post(route('investor.verification.save'), kycCommand(['expected_revision' => kycRevision($this->person), 'step' => $step, ...$answers]))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'year first' => ['personal', ['date_of_birth' => '1990-05-01'], 'date_of_birth', 'Enter your date of birth as DD / MM / YYYY.'],
    'impossible birth date' => ['personal', ['date_of_birth' => '30 / 02 / 1990'], 'date_of_birth', 'Enter your date of birth as DD / MM / YYYY.'],
    'too old to be real' => ['personal', ['date_of_birth' => '31 / 12 / 1899'], 'date_of_birth', 'Enter your date of birth as DD / MM / YYYY.'],
    'under 18' => ['personal', ['date_of_birth' => '07 / 10 / 2008'], 'date_of_birth', 'You must be at least 18 to invest.'],
    'unknown document' => ['document', ['id_type' => 'voter_card', 'id_number' => '1199080012345678'], 'id_type', 'Choose a national ID, passport or driving licence.'],
    'short ID number' => ['document', ['id_type' => 'national_id', 'id_number' => '1199'], 'id_number', 'Enter the 16-digit number on the card.'],
    'bad passport number' => ['document', ['id_type' => 'passport', 'id_number' => 'P-1'], 'id_number', 'Enter the passport number, 6 to 12 letters or digits.'],
    'missing ID back' => ['document', ['id_type' => 'drivers_license', 'id_number' => '1199080012345678'], 'id_front', 'Upload both sides of your ID, or the passport photo page.'],
]);

test('a person who turns 18 today may continue', function (): void {
    kycSave($this->person, 'personal', ['date_of_birth' => '06.10.2008']);

    expect(InvestorVerification::query()->sole()->state['step'])->toBe('document');
});

test('steps cannot be skipped and submission rechecks the selfie', function (): void {
    $this->actingAs($this->person)->post(route('investor.verification.save'), kycCommand(['expected_revision' => 0, 'step' => 'document', 'id_type' => 'passport', 'id_number' => 'PC123456']))
        ->assertSessionHasErrors(['form' => 'Finish the earlier steps first.']);

    kycSave($this->person, 'personal', ['date_of_birth' => '1/5/1990']);
    kycUpload($this->person, 'id_front');
    kycSave($this->person, 'document', ['id_type' => 'passport', 'id_number' => 'PC123456']);

    $this->actingAs($this->person)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => kycRevision($this->person)]))
        ->assertSessionHasErrors(['selfie' => 'Take a selfie to finish.']);
    expect(InvestorVerification::query()->sole()->status)->toBe('draft');
});

test('the final step is submitted, never saved', function (): void {
    $this->actingAs($this->person)->post(route('investor.verification.save'), kycCommand(['expected_revision' => 0, 'step' => 'liveness']))
        ->assertSessionHasErrors('step');

    expect(fn () => (new InvestorVerificationCase)->save([...(new InvestorVerificationCase)->empty(), 'step' => 'liveness'], 'draft', 'liveness', [], now()->toDateTimeImmutable()))
        ->toThrow(CommandRejection::class, 'VERIFICATION_STEP_INVALID')
        ->and(fn () => (new InvestorVerificationCase)->save((new InvestorVerificationCase)->empty(), 'draft', 'review', [], now()->toDateTimeImmutable()))
        ->toThrow(CommandRejection::class, 'VERIFICATION_STEP_INVALID')
        ->and(fn () => (new InvestorVerificationCase)->upload((new InvestorVerificationCase)->empty(), 'draft', 'proof_of_address', 'x'))
        ->toThrow(CommandRejection::class, 'VERIFICATION_SLOT_INVALID');
});

test('uploads are identified by signature and size', function (string $name, string $content, string $message): void {
    $this->actingAs($this->person)->post(route('investor.verification.upload'), kycCommand(['expected_revision' => 0, 'slot' => 'id_front',
        'file' => UploadedFile::fake()->createWithContent($name, $content)]))->assertSessionHasErrors(['file' => $message]);

    expect(InvestorVerificationDocument::query()->count())->toBe(0);
})->with([
    'disguised file' => ['id.png', 'GIF89a', 'Upload a PDF, PNG or JPEG.'],
    'empty file' => ['id.pdf', '', 'The file must be nonempty and at most 10 MiB.'],
]);

test('document descriptions refuse unsafe names, oversize files and accept PDF and JPEG', function (): void {
    $documents = new VerificationDocument;

    expect(fn () => $documents->describe("bad\nname.pdf", '%PDF-1.7'))->toThrow(CommandRejection::class, 'VERIFICATION_FILENAME_INVALID')
        ->and(fn () => $documents->describe('big.pdf', '%PDF-1.7'.str_repeat('x', 10 * 1024 * 1024)))->toThrow(CommandRejection::class, 'VERIFICATION_FILE_SIZE_INVALID')
        ->and($documents->describe('id.pdf', '%PDF-1.7 x')['media_type'])->toBe('application/pdf')
        ->and($documents->describe('id.JPG', "\xff\xd8\xff\xe0x")['media_type'])->toBe('image/jpeg');
});

test('a stale revision, a changed context and a reused request are refused', function (): void {
    kycSave($this->person, 'personal', ['date_of_birth' => '1/5/1990']);

    $this->actingAs($this->person)->post(route('investor.verification.save'), kycCommand(['expected_revision' => 0, 'step' => 'personal', 'date_of_birth' => '01 / 01 / 1991']))
        ->assertSessionHasErrors(['form' => 'This form changed in another window. Check your answers and try again.']);

    $this->actingAs($this->person)->postJson(route('investor.verification.save'), kycCommand(['identity_context_revision' => 5, 'expected_revision' => 1, 'step' => 'personal', 'date_of_birth' => '01 / 01 / 1991']))
        ->assertStatus(409);

    $replayed = kycCommand(['expected_revision' => 1, 'step' => 'personal', 'date_of_birth' => '01 / 01 / 1992']);
    $this->actingAs($this->person)->post(route('investor.verification.save'), $replayed)->assertSessionHasNoErrors();
    $this->actingAs($this->person)->post(route('investor.verification.save'), $replayed)->assertSessionHasNoErrors();
    expect(kycRevision($this->person))->toBe(2)
        ->and(CommandOperation::query()->where('request_id', $replayed['request_id'])->count())->toBe(1);

    $this->actingAs($this->person)->post(route('investor.verification.save'), [...$replayed, 'date_of_birth' => '01 / 01 / 1993'])
        ->assertSessionHasErrors(['form' => 'That request was already used for different details. Try again.']);
});

test('a refusal without its own words gets the generic message', function (): void {
    $this->mock(InvestorVerificationStore::class, function ($mock): void {
        $mock->shouldReceive('submit')->andReturn(['status' => 'rejected', 'code' => 'SOMETHING_ELSE', 'field_errors' => []]);
    });

    $this->actingAs($this->person)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => 0]))
        ->assertSessionHasErrors(['form' => 'We could not save this step. Try again.']);
});

test('a rejected submission reopens as a draft when the person edits it', function (): void {
    kycReady($this->person);
    $this->actingAs($this->person)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => 5]))->assertSessionHasNoErrors();
    $record = InvestorVerification::query()->sole();
    $state = $record->state;
    $state['decision'] = ['outcome' => 'rejected', 'reason' => 'The ID photo is blurred.', 'decided_at' => now()->toIso8601String()];
    $record->forceFill(['status' => 'rejected', 'state' => $state])->save();

    $this->actingAs($this->person)->get(route('investor.verification'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'rejected')->where('decision_reason', 'The ID photo is blurred.'));

    kycUpload($this->person, 'id_front');
    expect(InvestorVerification::query()->sole())->status->toBe('draft')->submitted_at->toBeNull()->state->decision->toBeNull();

    $this->actingAs($this->person)->post(route('investor.verification.submit'), kycCommand(['expected_revision' => 7]))->assertSessionHasNoErrors();
    expect(InvestorVerification::query()->sole()->status)->toBe('submitted');
});

test('approved uploads read as verified', function (): void {
    kycReady($this->person);
    InvestorVerification::query()->sole()->forceFill(['status' => 'approved', 'submitted_at' => now()])->save();

    $this->actingAs($this->person)->get(route('investor.verification'))
        ->assertInertia(fn (Assert $page) => $page->where('uploads.front.status', 'verified')->where('status', 'approved'));
});

test('only a person with their own Party may submit', function (): void {
    $this->post(route('investor.verification.submit'), kycCommand(['expected_revision' => 0]))->assertRedirect(route('login'));
    $staff = User::factory()->create();
    $organization = User::factory()->create(['party_id' => Party::factory()->state(['kind' => 'organization'])]);

    $this->actingAs($staff)->getJson(route('investor.verification'))->assertForbidden()->assertJsonPath('code', 'IDENTITY_NOT_LINKED');
    $this->actingAs($staff)->get(route('investor.verification'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'IDENTITY_NOT_LINKED'));
    $this->actingAs($organization)->getJson(route('investor.verification'))->assertForbidden()->assertJsonPath('code', 'PARTY_AUTHORITY_REQUIRED');
    $this->actingAs(User::factory()->unverified()->create(['party_id' => Party::factory()]))->get(route('investor.verification'))
        ->assertRedirect(route('verification.notice'));
});

test('history and documents are immutable and the schema refuses invalid rows', function (): void {
    kycReady($this->person);

    expect(fn () => DB::table('investor_verification_documents')->update(['slot' => 'back']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('investor_verification_versions')->delete())->toThrow(QueryException::class)
        ->and(fn () => DB::table('investor_verifications')->update(['status' => 'submitted']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('investor_verifications')->update(['status' => 'pending']))->toThrow(QueryException::class);
});
