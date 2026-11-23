<?php

declare(strict_types=1);

use App\Application\Identity\AcceptLegalDocuments;
use App\Application\Identity\Contracts\LegalDocumentLibrary;
use App\Application\Identity\LegalDocuments;
use App\Application\Identity\RegisterIdentity;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\LegalDocument;
use App\Domain\Identity\SandboxNotice;
use App\Models\LegalConsent;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Each document's issued wording, pinned. Changing a file without issuing a new version fails
 * here, so nobody's recorded consent ever points at wording other than what they agreed to.
 */
const ISSUED_LEGAL_DOCUMENTS = [
    'terms' => ['terms-2026-11-23', '43b7fc2e74d758f48da5008e06d39e6e4b1e03dcf2b43de4e9f971f76ed5c893'],
    'privacy' => ['privacy-2026-11-23', '268c15329d9fd92ebe20e34ae19cb37481dd3e4db41bac7edfdcb1ee8943f250'],
    'disclaimers' => ['disclaimers-2026-11-23', '7892c36343133a5e36a64ddffe7932fe40424b50cccc399401632793cac20326'],
];

function personWithoutLegalConsent(): User
{
    return User::factory()->create(['party_id' => Party::factory()->withoutLegalConsent()->create()->id]);
}

it('pins each issued document to its version and the digest of its exact wording', function (): void {
    foreach (LegalDocument::cases() as $document) {
        [$version, $digest] = ISSUED_LEGAL_DOCUMENTS[$document->value];
        $source = (string) file_get_contents(resource_path("legal/{$document->value}.md"));

        expect($document->version())->toBe($version)
            ->and(hash('sha256', $source))->toBe($digest)
            ->and($source)->toContain("Version `{$version}`");
    }
    expect(array_column(app(LegalDocuments::class)->current(), 'key'))->toBe(['terms', 'privacy', 'disclaimers']);
});

it('keeps the decided facts in the published wording', function (): void {
    $terms = (string) file_get_contents(resource_path('legal/terms.md'));
    $privacy = (string) file_get_contents(resource_path('legal/privacy.md'));
    $disclaimers = (string) file_get_contents(resource_path('legal/disclaimers.md'));

    expect($terms)->toContain('We are in talks with GT Bank')->toContain('no agreement has been signed yet')
        ->toContain('more than 50% of any raise')->toContain('3% of the principal raised')->toContain('day 31')
        ->not->toContain('second signatory is required')
        ->and($privacy)->toContain('Germany')->toContain('not stored in Rwanda')->toContain('info@rozine.rw')
        ->not->toContain('biometric')
        ->and($disclaimers)->toContain(SandboxNotice::TEXT)->toContain('Investing carries risk, including loss of capital. Returns are not guaranteed.');
});

it('publishes each document as a public page with its version and digest', function (string $route, string $key): void {
    $this->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page->component('legal/document')
        ->where('document.key', $key)
        ->where('document.version', ISSUED_LEGAL_DOCUMENTS[$key][0])
        ->where('document.sha256', ISSUED_LEGAL_DOCUMENTS[$key][1])
        ->where('document.html', fn (string $html): bool => str_contains($html, '<h1>') && ! str_contains($html, '<script'))
        ->has('documents', 3)
        ->where('documents.0.url', '/terms')
        ->where('review', null));
})->with([['legal.terms', 'terms'], ['legal.privacy', 'privacy'], ['legal.disclaimers', 'disclaimers']]);

it('lists the Rozine way of meeting each of the twelve platform rules in the Terms', function (): void {
    $html = app(LegalDocuments::class)->pageFor('terms')['html'] ?? '';

    foreach (['Investor money.', 'The platform\'s own interest.', 'No guarantees.', 'No investment advice.', 'Terms of the offer.',
        'Eligible businesses and checks.', 'Disclosure.', 'Withdrawal.', 'Investor limits.', 'Selling early.', 'Use of funds and records.', 'Instruments.'] as $rule) {
        expect($html)->toContain('<strong>'.$rule.'</strong>');
    }
    expect(app(LegalDocuments::class)->pageFor('cookies'))->toBeNull();
});

it('points a signed-in person who has not agreed yet to the review page from a document', function (): void {
    $this->actingAs(personWithoutLegalConsent())->get(route('legal.privacy'))
        ->assertInertia(fn (Assert $page) => $page->where('review', '/legal/review'));
});

it('gives the sign-up form each document with its digest and link', function (): void {
    $this->get(route('register'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/register')
        ->has('legalDocuments', 3)
        ->where('legalDocuments.0', ['key' => 'terms', 'title' => 'Terms and Conditions', 'version' => 'terms-2026-11-23',
            'sha256' => ISSUED_LEGAL_DOCUMENTS['terms'][1], 'url' => '/terms'])
        ->where('legalDocuments.2.url', '/disclaimers'));
});

it('records each document, its version, its digest and the time when a person signs up', function (): void {
    $this->freezeTime();
    $this->post(route('register.store'), ['name' => 'Aline Uwase', 'email' => 'aline@example.test', 'password' => 'password',
        'password_confirmation' => 'password', 'sandbox_notice_version' => SandboxNotice::VERSION,
        'sandbox_notice_sha256' => SandboxNotice::sha256(), ...legalConsentFields()])->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'aline@example.test')->sole();
    $consents = LegalConsent::query()->orderBy('document')->get();
    $at = now('UTC')->toDateTimeImmutable();

    expect($consents)->toHaveCount(3)
        ->and($consents->pluck('document')->all())->toBe(['disclaimers', 'privacy', 'terms']);
    foreach ($consents as $consent) {
        [$version, $digest] = ISSUED_LEGAL_DOCUMENTS[$consent->document];
        expect($consent->party_id)->toBe($user->party_id)
            ->and($consent->version)->toBe($version)
            ->and($consent->document_sha256)->toBe($digest)
            ->and($consent->source)->toBe('sign_up')
            ->and($consent->accepted_at->equalTo($at))->toBeTrue()
            ->and($consent->sha256)->toBe(hash('sha256', implode("\n", ['legal-consent-1', $consent->id, $user->party_id, $consent->document,
                $version, $digest, 'sign_up', $at->format('Y-m-d\TH:i:s.u\Z')])));
    }
});

it('refuses a sign-up that did not tick every document, or ticked an older wording, and creates nothing', function (array $fields, string $error): void {
    $this->post(route('register.store'), ['name' => 'Aline Uwase', 'email' => 'aline@example.test', 'password' => 'password',
        'password_confirmation' => 'password', 'sandbox_notice_version' => SandboxNotice::VERSION,
        'sandbox_notice_sha256' => SandboxNotice::sha256(), ...array_replace_recursive(legalConsentFields(), $fields)])
        ->assertSessionHasErrors([$error]);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('legal_consents', 0);
    $this->assertGuest();
})->with([
    'terms not ticked' => [['legal_agreements' => ['terms' => '0']], 'legal_agreements.terms'],
    'disclaimers not ticked' => [['legal_agreements' => ['disclaimers' => '']], 'legal_agreements.disclaimers'],
    'an older privacy notice' => [['legal_documents' => ['privacy' => str_repeat('a', 64)]], 'legal_documents.privacy'],
]);

it('refuses a sign-up with no tick boxes at all', function (): void {
    $this->post(route('register.store'), ['name' => 'Aline Uwase', 'email' => 'aline@example.test', 'password' => 'password',
        'password_confirmation' => 'password', 'sandbox_notice_version' => SandboxNotice::VERSION, 'sandbox_notice_sha256' => SandboxNotice::sha256()])
        ->assertSessionHasErrors(['legal_documents' => 'These documents have changed. Read the new versions before you continue.',
            'legal_agreements' => 'Tick each box to agree before you continue.']);
});

it('refuses to register anyone through the application without the current digests', function (array $digests): void {
    expect(fn () => app(RegisterIdentity::class)->handle('Aline Uwase', 'aline@example.test', 'password',
        SandboxNotice::VERSION, SandboxNotice::sha256(), $digests))->toThrow(IdentityViolation::class, 'LEGAL_CONSENT_REQUIRED');

    $this->assertDatabaseCount('parties', 0);
})->with([
    'none' => [[]],
    'one missing' => [['terms' => 'x', 'privacy' => 'y']],
    'one stale' => [fn (): array => [...legalDigests(), 'terms' => str_repeat('b', 64)]],
    'not text' => [fn (): array => [...legalDigests(), 'terms' => 7]],
]);

it('holds every app action until the person agrees to the current wording, then returns them where they were going', function (): void {
    Route::middleware(['web', 'auth'])->get('investor/legal-gate-probe', fn () => 'investor page')->name('investor.legal-gate-probe');
    Route::middleware(['web', 'auth'])->post('business/legal-gate-probe', fn () => 'business command')->name('business.legal-gate-probe');
    Route::middleware(['web', 'auth'])->get('admin/legal-gate-probe', fn () => 'staff page')->name('staff.legal-gate-probe');
    $user = personWithoutLegalConsent();
    $this->actingAs($user);

    $this->get('/investor/legal-gate-probe')->assertRedirect('/legal/review');
    $this->postJson('/business/legal-gate-probe')->assertStatus(409)
        ->assertExactJson(['message' => 'Agree to the current Terms, Privacy Notice and Disclaimers before you continue.',
            'code' => 'LEGAL_CONSENT_REQUIRED', 'review' => '/legal/review']);
    $this->post('/business/legal-gate-probe')->assertRedirect('/legal/review');
    $this->get('/admin/legal-gate-probe')->assertOk()->assertSee('staff page');
    $this->get(route('legal.terms'))->assertOk();

    $this->get(route('legal.review'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('legal/review')
        ->has('documents', 3)->where('documents.1.sha256', ISSUED_LEGAL_DOCUMENTS['privacy'][1])->where('accept', '/legal/review'));

    $this->get('/investor/legal-gate-probe');
    $this->post(route('legal.accept'), legalConsentFields())->assertRedirect(url('/investor/legal-gate-probe'));

    expect(LegalConsent::query()->where('party_id', $user->party_id)->pluck('source')->unique()->all())->toBe(['review']);
    $this->get('/investor/legal-gate-probe')->assertOk()->assertSee('investor page');
    $this->post('/business/legal-gate-probe')->assertOk();
    $this->get(route('legal.review'))->assertRedirect(route('dashboard', absolute: false));
});

it('asks again when the wording changes', function (): void {
    Route::middleware(['web', 'auth'])->get('auditor/legal-gate-probe', fn () => 'auditor page')->name('auditor.legal-gate-probe');
    $user = User::factory()->create(['party_id' => Party::factory()->create()->id]);
    $this->actingAs($user)->get('/auditor/legal-gate-probe')->assertOk();

    app()->instance(LegalDocumentLibrary::class, new class implements LegalDocumentLibrary
    {
        public function text(LegalDocument $document): string
        {
            return $document === LegalDocument::Terms ? "# Terms\n\nNew wording." : (string) file_get_contents(resource_path("legal/{$document->value}.md"));
        }
    });

    $this->get('/auditor/legal-gate-probe')->assertRedirect('/legal/review');
    $this->post(route('legal.accept'), legalConsentFields())->assertRedirect('/auditor/legal-gate-probe');
    expect(LegalConsent::query()->where('party_id', $user->party_id)->count())->toBe(4);
});

it('refuses an agreement to wording other than the current one on the review page', function (): void {
    $user = personWithoutLegalConsent();

    $this->actingAs($user)->from(route('legal.review'))->post(route('legal.accept'),
        array_replace_recursive(legalConsentFields(), ['legal_documents' => ['disclaimers' => str_repeat('f', 64)]]))
        ->assertSessionHasErrors(['legal_documents.disclaimers' => 'There is a newer version of the Disclaimers. Read it before you continue.']);

    $this->assertDatabaseCount('legal_consents', 0);
});

it('refuses an agreement the application cannot attribute or match', function (): void {
    $user = personWithoutLegalConsent();
    $accept = app(AcceptLegalDocuments::class);

    expect(fn () => $accept->handle($user->id, ['terms' => 'x']))->toThrow(IdentityViolation::class, 'LEGAL_DOCUMENTS_STALE')
        ->and(fn () => $accept->handle(User::factory()->create(['party_id' => null])->id, legalDigests()))->toThrow(IdentityViolation::class, 'PARTY_REQUIRED')
        ->and($accept->satisfied(User::factory()->create(['party_id' => null])->id))->toBeTrue();

    $accept->handle($user->id, legalDigests());
    $accept->handle($user->id, legalDigests());
    expect(LegalConsent::query()->count())->toBe(3);
});

it('keeps every consent permanent', function (): void {
    $consent = LegalConsent::query()->where('party_id', Party::factory()->create()->id)->firstOrFail();

    expect(fn () => DB::transaction(fn () => DB::table('legal_consents')->where('id', $consent->id)->update(['source' => 'review'])))
        ->toThrow(QueryException::class, 'Legal consents are immutable')
        ->and(fn () => DB::transaction(fn () => DB::table('legal_consents')->where('id', $consent->id)->delete()))
        ->toThrow(QueryException::class, 'Legal consents are immutable');
});

it('gives organisations no consent of their own; their people agree', function (): void {
    Party::factory()->create(['kind' => 'organization']);

    $this->assertDatabaseCount('legal_consents', 0);
});
