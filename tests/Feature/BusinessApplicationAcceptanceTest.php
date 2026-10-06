<?php

declare(strict_types=1);

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\FindBusinessOperation;
use App\Application\Business\GetBusinessApplicationReview;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Business\SubmitBusinessApplication;
use App\Application\Identity\RecordConsentRelease;
use App\Application\Identity\SelectActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;

it('submits a sole-trader application with immutable pinned terms and an exact recoverable receipt', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $before = BusinessQuoteFixture::review($fixture);
    expect($before['acceptance']['fee_on_approval'])->toBe(['currency' => 'RWF', 'amount' => '0'])
        ->and($before['acceptance']['required_signatures'])->toBe(1)->and($before['acceptance']['signatures_complete'])->toBeFalse();
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request);
    expect($result['code'])->toBe('APPLICATION_SUBMITTED')->and($result['revision'])->toBe(5)
        ->and($result['data']['application']['status'])->toBe('submitted')->and($result['data']['application']['step'])->toBe('submitted')
        ->and($result['data']['acceptance']['signatures_complete'])->toBeTrue()
        ->and($result['data']['acceptance']['signers'][0]['state'])->toBe('signed')
        ->and($result['data']['submission']['note_id'])->toBeNull()
        ->and($result['data']['submission']['timeline'])->toBe([
            ['stage' => 'submitted', 'state' => 'done'], ['stage' => 'under_review', 'state' => 'current'],
            ['stage' => 'approved', 'state' => 'pending'], ['stage' => 'published', 'state' => 'pending'],
        ])
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request))->toBe($result)
        ->and(BusinessQuoteFixture::review($fixture))->toEqual($result['data'])
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($result['data']['quote'])
        ->and(app(FindBusinessOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, 'submit', $request))->toBe($result);
    $signature = BusinessApplicationSignature::query()->firstOrFail();
    $submission = BusinessApplicationSubmission::query()->firstOrFail();
    foreach ([$signature, $submission] as $record) {
        expect($record->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($record->payload)))
            ->and($record->getRawOriginal('payload'))->not->toContain('Synthetic', 'signature_name')
            ->and($record->toArray())->not->toHaveKey('payload');
    }
    expect($signature->payload['signature_name'])->toBe($accepted['signature_name'])
        ->and($submission->payload['agreement']['listing_fee'])->toBe(['currency' => 'RWF', 'amount' => '0', 'basis' => 'CFG-01_MVP_WAIVER'])
        ->and($submission->payload['signatures'])->toBe([['id' => $signature->id, 'sha256' => $signature->sha256]])
        ->and(BusinessApplicationVersion::query()->where('business_application_id', $fixture['application']->id)->where('revision', 5)->firstOrFail()->snapshot['submission_id'] ?? null)->toBe($submission->id);
    expect(fn () => BusinessQuoteFixture::submit($fixture, [...$accepted, 'signature_name' => 'Changed name'], revision: 4, request: $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    expect(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('APPLICATION_NOT_EDITABLE');
    $new = app(CreateBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], 0, (string) Str::uuid());
    expect($new['code'])->toBe('APPLICATION_PENDING_REVIEW');
    $this->assertDatabaseCount('business_applications', 1);
    $this->assertDatabaseCount('business_application_signatures', 1);
    $this->assertDatabaseCount('business_application_submissions', 1);
});

it('requires all actual company signers and deduplicates a verified Party across logins', function (): void {
    $fixture = BusinessQuoteFixture::ready(3);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $first = BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request);
    expect($first['code'])->toBe('APPLICATION_SIGNATURE_RECORDED')->and($first['revision'])->toBe(5)
        ->and($first['data']['application']['status'])->toBe('draft')->and($first['data']['acceptance']['required_signatures'])->toBe(3)
        ->and($first['data']['acceptance']['signatures_complete'])->toBeFalse()->and($first['data']['submission'])->toBeNull();
    $alias = User::factory()->create(['party_id' => $fixture['audit']['authority']['people'][0]->id]);
    app(SelectActiveRole::class)->handle($alias->id, 'business', 0, (string) Str::uuid());
    $duplicate = app(SubmitBusinessApplication::class)->handle($alias->id, 1, $fixture['audit']['business'], $fixture['application']->id, 5,
        [...$accepted, 'signature_name' => 'Another login name'], (string) Str::uuid());
    expect($duplicate['code'])->toBe('APPLICATION_SIGNATURE_RECORDED')->and($duplicate['revision'])->toBe(5);
    $this->assertDatabaseCount('business_application_signatures', 1);
    expect(BusinessQuoteFixture::submit($fixture, $accepted, 1)['code'])->toBe('APPLICATION_SIGNATURE_RECORDED');
    $last = BusinessQuoteFixture::submit($fixture, $accepted, 2);
    expect($last['code'])->toBe('APPLICATION_SUBMITTED')->and($last['revision'])->toBe(7)
        ->and($last['data']['acceptance']['signatures_complete'])->toBeTrue()
        ->and(array_column($last['data']['acceptance']['signers'], 'state'))->toBe(['signed', 'signed', 'signed'])
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request))->toBe($first);
    $this->assertDatabaseCount('business_application_signatures', 3);
    $this->assertDatabaseCount('business_application_submissions', 1);
});

it('refuses wrong quote principal evidence mandate and document versions without recording a signature', function (string $field): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    if ($field === 'documents' || $field === 'disclosures') {
        $accepted[$field][0]['sha256'] = str_repeat('0', 64);
    } else {
        $accepted[$field] = match ($field) {
            'quote_id' => (string) Str::ulid(), 'quote_revision' => 2, 'mandate_version' => '2',
            'evidence_version' => str_repeat('0', 64), 'accepted_principal' => '5000000',
            default => throw new InvalidArgumentException('Unknown acceptance test field.'),
        };
    }
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::submit($fixture, $accepted, request: $request);
    expect($result['code'])->toBe(in_array($field, ['documents', 'disclosures'], true) ? 'DOCUMENT_VERSION_STALE' : 'QUOTE_STALE')
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, request: $request))->toBe($result);
    $this->assertDatabaseCount('business_application_signatures', 0);
})->with(['quote_id', 'quote_revision', 'mandate_version', 'evidence_version', 'accepted_principal', 'documents', 'disclosures']);

it('returns recorded validation errors and stale revisions without changing the application', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    expect(BusinessQuoteFixture::submit($fixture, $accepted, revision: 3)['code'])->toBe('VERSION_CONFLICT');
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::submit($fixture, [...$accepted, 'terms' => false], request: $request);
    expect($result['code'])->toBe('APPLICATION_ACCEPTANCE_REQUIRED')->and($result['http_status'])->toBe(422)
        ->and($result['field_errors'])->toHaveKey('terms')
        ->and(BusinessQuoteFixture::submit($fixture, [...$accepted, 'terms' => false], request: $request))->toBe($result);
    $this->assertDatabaseCount('business_application_signatures', 0);
});

it('excludes prior-release signatures and requires every signer to accept the new documents', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $accepted);
    app(RecordConsentRelease::class)->handle($fixture['audit']['staff']->id, 1, 'active', ConsentFixture::documents('synthetic-2'),
        ConsentFixture::disclosures('synthetic-2'), true, 'fixture:new-release', 'Updated synthetic release.', (string) Str::uuid());
    expect(array_column(BusinessQuoteFixture::review($fixture)['acceptance']['signers'], 'state'))->toBe(['pending', 'pending'])
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, 1)['code'])->toBe('DOCUMENT_VERSION_STALE');
    $current = BusinessQuoteFixture::acceptance($fixture);
    expect(BusinessQuoteFixture::submit($fixture, $current, 1)['code'])->toBe('APPLICATION_SIGNATURE_RECORDED')
        ->and(BusinessQuoteFixture::submit($fixture, $current)['code'])->toBe('APPLICATION_SUBMITTED');
    $this->assertDatabaseCount('business_application_signatures', 3);
});

it('keeps submitted terms historical after consent credit calendar and mandate changes', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $result = BusinessQuoteFixture::submit($fixture, $accepted);
    app(RecordConsentRelease::class)->handle($fixture['audit']['staff']->id, 1, 'withdrawn', [], [], false,
        'fixture:withdrawn', 'Withdraw old synthetic documents.', (string) Str::uuid());
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1,
        facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    $authority = $fixture['audit']['authority'];
    $authority['profile']['name'] = 'Updated legal name';
    BusinessAuthorityFixture::configure($authority, 1);
    $this->travel(1)->months();
    expect(BusinessQuoteFixture::review($fixture))->toEqual($result['data'])
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($result['data']['quote']);
});

it('refuses unavailable or withdrawn legal text and does not manufacture acceptance', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    app(RecordConsentRelease::class)->handle($fixture['audit']['staff']->id, 1, 'withdrawn', [], [], false,
        'fixture:withdrawn', 'Withdraw old synthetic documents.', (string) Str::uuid());
    expect(BusinessQuoteFixture::review($fixture)['acceptance']['documents'])->toBe([])
        ->and(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('CONSENT_DOCUMENTS_UNAVAILABLE');
    $this->assertDatabaseCount('business_application_signatures', 0);
});

it('invalidates unsigned or partially signed agreements when their quote or evidence ceases to be current', function (string $change): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $accepted);
    if ($change === 'credit') {
        BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1,
            facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    } elseif ($change === 'draft') {
        app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
            $fixture['application']->id, 5, BusinessApplicationFixture::fields('5000000'), 'raise', (string) Str::uuid());
    } else {
        BusinessQuoteFixture::evaluate($fixture, 5, '5000000');
    }
    expect(BusinessQuoteFixture::submit($fixture, $accepted, 1)['code'])->toBe($change === 'draft' ? 'APPLICATION_STEP_INVALID' : 'QUOTE_STALE');
    $review = BusinessQuoteFixture::review($fixture);
    if ($change === 'quote') {
        expect(array_column($review['acceptance']['signers'], 'state'))->toBe(['pending', 'pending']);
    } else {
        expect($review['quote'])->toBeNull()->and(array_column($review['acceptance']['signers'], 'state'))->toBe(['pending', 'pending']);
    }
    $this->assertDatabaseCount('business_application_signatures', 1);
    $this->assertDatabaseCount('business_application_submissions', 0);
})->with(['credit', 'draft', 'quote']);

it('requires the current signer role and entity scope for signing reading and operation recovery', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $other = BusinessApplicationFixture::make();
    $owner = $fixture['audit']['authority']['users'][0];
    expect(fn () => app(GetBusinessApplicationReview::class)->handle($other['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['application']->id))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $request = (string) Str::uuid();
    BusinessQuoteFixture::submit($fixture, $accepted, request: $request);
    RoleMembership::query()->where('party_id', $owner->party_id)->where('role', 'business')->update(['status' => 'revoked']);
    expect(fn () => BusinessQuoteFixture::review($fixture))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED')
        ->and(fn () => BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED')
        ->and(fn () => app(FindBusinessOperation::class)->handle($owner->id, 1, 'submit', $request))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED');
});

it('protects signatures submissions and their application ownership in PostgreSQL', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    foreach (['business_application_signatures', 'business_application_submissions'] as $table) {
        foreach (['update', 'delete'] as $mutation) {
            expect(fn () => DB::transaction(fn () => $mutation === 'update'
                ? DB::table($table)->update(['sha256' => str_repeat('0', 64)])
                : DB::table($table)->delete()))->toThrow(QueryException::class, 'Business application acceptances are immutable');
        }
    }
    $other = BusinessApplicationFixture::make();
    $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $other['application']->id]);
    expect(fn () => DB::transaction(fn () => $other['application']->forceFill(['status' => 'submitted', 'step' => 'submitted',
        'current_quote_id' => $quote->id, 'current_submission_id' => $fixture['application']->refresh()->current_submission_id])->save()))
        ->toThrow(QueryException::class, 'application_current_submission_owner');
    expect(BusinessApplicationSignature::factory()->create(['consent_release_id' => BusinessApplicationSignature::query()->firstOrFail()->consent_release_id])->payload['source'])->toBe('unsupported-fixture')
        ->and(BusinessApplicationSubmission::factory()->create()->payload['source'])->toBe('unsupported-fixture');
});

it('refuses corrupted signature history instead of counting it toward submission', function (string $fault): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $accepted);
    $event = 'eloquent.retrieved: '.BusinessApplicationSignature::class;
    Event::listen($event, function (BusinessApplicationSignature $record) use ($fault): void {
        $payload = $record->payload;
        if ($fault === 'digest') {
            $record->sha256 = str_repeat('0', 64);
        } else {
            $payload[$fault] = 'corrupted';
            $record->payload = $payload;
            $record->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
        }
    });
    try {
        expect(fn () => BusinessQuoteFixture::submit($fixture, $accepted, 1))->toThrow(RuntimeException::class, 'APPLICATION_SIGNATURE_INTEGRITY_FAILED');
        $this->assertDatabaseCount('business_application_submissions', 0);
    } finally {
        Event::forget($event);
    }
})->with(['digest', 'signature_id', 'application_id', 'quote_id', 'consent_release_id', 'actor_party_id', 'binding_sha256', 'agreement']);

it('refuses missing or corrupted immutable submission records', function (string $fault): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $event = 'eloquent.retrieved: '.($fault === 'missing' ? BusinessApplication::class : BusinessApplicationSubmission::class);
    if ($fault === 'missing') {
        Event::listen($event, function (BusinessApplication $record): void {
            $record->current_submission_id = null;
        });
    } else {
        Event::listen($event, function (BusinessApplicationSubmission $record) use ($fault): void {
            $payload = $record->payload;
            if ($fault === 'digest') {
                $record->sha256 = str_repeat('0', 64);
            } else {
                $payload[$fault] = 'corrupted';
                $record->payload = $payload;
                $record->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
            }
        });
    }
    try {
        expect(fn () => BusinessQuoteFixture::review($fixture))->toThrow(RuntimeException::class, 'APPLICATION_SUBMISSION_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['missing', 'digest', 'submission_id', 'application_id', 'application_revision', 'quote_id', 'binding_sha256', 'agreement']);

it('keeps the saved pointer on omitted-step autosave while invalidating old signed terms', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $accepted);
    $saved = app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, 5, BusinessApplicationFixture::fields('5000000'), null, (string) Str::uuid());
    expect($saved['data']['application']['step'])->toBe('review')
        ->and($saved['revision'])->toBe(6)->and(BusinessQuoteFixture::quote($fixture))->toBeNull()
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, 1)['code'])->toBe('QUOTE_STALE');
    $this->assertDatabaseCount('business_application_signatures', 1);
    $this->assertDatabaseCount('business_application_submissions', 0);
});

it('rolls the signature and journal back when final submission fails and retries once', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $event = 'eloquent.creating: '.BusinessApplicationSubmission::class;
    Event::listen($event, fn () => throw new RuntimeException('Simulated submission storage failure.'));
    try {
        expect(fn () => BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request))->toThrow(RuntimeException::class, 'Simulated submission storage failure.');
        $this->assertDatabaseCount('business_application_signatures', 0);
        $this->assertDatabaseCount('business_application_submissions', 0);
        expect($fixture['application']->refresh()->revision)->toBe(4);
    } finally {
        Event::forget($event);
    }
    expect(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request)['code'])->toBe('APPLICATION_SUBMITTED');
    $this->assertDatabaseCount('business_application_signatures', 1);
});

it('does not let a permitted representative replace a required company signatory', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $authority = $fixture['audit']['authority'];
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => BusinessQuoteFixture::submit($fixture, [], 1))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
    $this->assertDatabaseCount('business_application_signatures', 0);
});

it('requires a ready quote and Review transition before signature capture', function (): void {
    $fixture = BusinessQuoteFixture::make(false);
    ConsentFixture::record($fixture['audit']['staff']);
    expect(BusinessQuoteFixture::review($fixture)['quote'])->toBeNull();
    BusinessQuoteFixture::evaluate($fixture);
    expect(BusinessQuoteFixture::review($fixture)['quote']['status'])->toBe('refused');
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business']);
    BusinessQuoteFixture::evaluate($fixture, 3);
    expect(BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture))['code'])->toBe('APPLICATION_STEP_INVALID');
    $this->assertDatabaseCount('business_application_signatures', 0);
});
