<?php

declare(strict_types=1);

use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Infrastructure\Business\RetainedStaffUnderwritingBasis;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use App\Models\StaffAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

afterEach(function (): void {
    foreach ([BusinessApplication::class, BusinessApplicationQuote::class, BusinessApplicationSubmission::class] as $model) {
        Event::forget('eloquent.retrieved: '.$model);
    }
    DB::disableQueryLog();
});

/** @return array<string, mixed> */
function retainedStaffBasisFixture(): array
{
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $application = $fixture['application']->refresh();
    $submission = BusinessApplicationSubmission::query()->findOrFail($application->current_submission_id);
    $quote = BusinessApplicationQuote::query()->findOrFail($submission->business_application_quote_id);

    return [...$fixture, 'application' => $application, 'submission' => $submission, 'quote' => $quote];
}

/**
 * Test-only self-consistent rehashing distinguishes binding refusal from a simple digest mismatch.
 *
 * @param  array<string, mixed>  $payload
 */
function rehashStaffBasisSubmission(BusinessApplicationSubmission $submission, array $payload): void
{
    $submission->binding_sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload['agreement']));
    $payload['binding_sha256'] = $submission->binding_sha256;
    $submission->payload = $payload;
    $submission->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
}

it('selects the exact original scorecard and provenance only for the selected staff review with web API parity', function (): void {
    $fixture = retainedStaffBasisFixture();
    ['application' => $application, 'submission' => $submission, 'quote' => $quote] = $fixture;
    $original = $quote->payload;
    $expected = app(RetainedStaffUnderwritingBasis::class)->project($application, $submission);
    expect($expected['scorecard'])->toEqual($original['result']['scorecard'])
        ->and($expected['quote'])->toBe(['id' => $quote->id, 'revision' => $quote->revision, 'sha256' => $quote->sha256,
            'application_revision' => $original['application_revision'], 'evaluated_at' => $original['evaluated_at']])
        ->and($expected['submission'])->toBe(['id' => $submission->id, 'revision' => $submission->revision,
            'sha256' => $submission->sha256, 'binding_sha256' => $submission->binding_sha256])
        ->and($expected['application'])->toBe(['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $submission->revision])
        ->and($expected['pricing_policy_version'])->toBe($original['policy_version'])
        ->and($expected['calculation_version'])->toBe($original['calculation_version'])
        ->and($expected['acceptance_policy_version'])->toBe($submission->payload['agreement']['policy_version'])
        ->and($original['application_revision'])->toBeLessThan($submission->revision);
    $staff = $fixture['audit']['staff'];
    $this->actingAs($staff);
    $web = $this->get(route('staff.applications.index', ['application' => $application->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('review.underwriting_basis', $expected)
            ->where('review.factors', [])->where('review.audit.state', null)->where('review.reviewer', null)->where('review.trail', [])
            ->missing('applications.0.underwriting_basis')->missing('review.underwriting_basis.inputs')
            ->missing('review.underwriting_basis.result')->missing('review.underwriting_basis.agreement'));
    Sanctum::actingAs($staff, ['staff:applications:read']);
    $this->getJson(route('api.v1.staff.applications.index', ['application' => $application->id]))->assertOk()
        ->assertJsonPath('data.review.underwriting_basis', $web->inertiaPage()['props']['review']['underwriting_basis'])
        ->assertJsonMissingPath('data.applications.0.underwriting_basis')
        ->assertJsonPath('data.review.release.allowed_actions', []);
    $this->getJson(route('api.v1.staff.applications.index'))->assertOk()->assertJsonPath('data.review', null)
        ->assertJsonMissingPath('data.applications.0.underwriting_basis');
});

it('reads the original quote with SELECTs only and does not serialize unselected private fields', function (): void {
    $fixture = retainedStaffBasisFixture();
    $quote = $fixture['quote'];
    $payload = $quote->payload;
    $payload['result']['scorecard']['private'] = 'private-extra';
    $payload['result']['scorecard']['components']['extra'] = ['secret' => 'private-extra'];
    $payload['result']['scorecard']['score']['private'] = 'private-extra';
    $quote->payload = $payload;
    $quote->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
    $submitted = $fixture['submission']->payload;
    $submitted['agreement']['quote_sha256'] = $quote->sha256;
    rehashStaffBasisSubmission($fixture['submission'], $submitted);
    BusinessApplicationQuote::retrieved(fn (BusinessApplicationQuote $record) => $record->setRawAttributes($quote->getAttributes()));
    $fixture['application']->current_quote_id = (string) Str::ulid();
    DB::enableQueryLog();
    $basis = app(RetainedStaffUnderwritingBasis::class)->project($fixture['application'], $fixture['submission']);
    $queries = DB::getQueryLog();
    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toStartWith('select ')->not->toContain('for update')
        ->and($queries[0]['bindings'])->toContain($quote->id)
        ->and(json_encode($basis))->not->toContain('private-extra', 'inputs', 'cash_flow', 'mandate', 'signatures', 'credit_source_reference')
        ->and(array_keys($basis['scorecard']['components']))->toBe(['coverage', 'cfads_margin', 'nocf_stability', 'positive_months', 'history_depth', 'conduct']);
});

it('keeps the historical basis unchanged after current source changes while current release remains held', function (): void {
    $fixture = retainedStaffBasisFixture();
    $staff = $fixture['audit']['staff'];
    $url = route('api.v1.staff.applications.index', ['application' => $fixture['application']->id]);
    $this->actingAs($staff);
    $original = $this->getJson($url)->assertOk()->json('data.review.underwriting_basis');
    BusinessCreditFactsFixture::record($staff, $fixture['audit']['business'], 1,
        facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    $this->getJson($url)->assertOk()->assertJsonPath('data.review.underwriting_basis', $original)
        ->assertJsonPath('data.review.release.allowed_actions', []);
});

it('accepts historical version identifiers and exact ratios without consulting current policy constants', function (): void {
    $fixture = retainedStaffBasisFixture();
    $quote = $fixture['quote'];
    $payload = $quote->payload;
    $payload['policy_version'] = $payload['result']['policy_version'] = 'historical-pricing-policy';
    $payload['calculation_version'] = $payload['result']['calculation_version'] = 'historical-calculation';
    $payload['result']['scorecard']['scorecard_version'] = 'historical-scorecard';
    $payload['result']['scorecard']['components']['coverage'] = ['numerator' => '299', 'denominator' => '3'];
    $quote->payload = $payload;
    $quote->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
    $submitted = $fixture['submission']->payload;
    $submitted['agreement']['quote_sha256'] = $quote->sha256;
    $submitted['agreement']['policy_version'] = 'historical-acceptance-policy';
    $submitted['review']['quote']['policy_version'] = $payload['policy_version'];
    $submitted['review']['quote']['calculation_version'] = $payload['calculation_version'];
    rehashStaffBasisSubmission($fixture['submission'], $submitted);
    BusinessApplicationQuote::retrieved(fn (BusinessApplicationQuote $record) => $record->setRawAttributes($quote->getAttributes()));
    $this->app->bind(ApplicationUnderwriting::class, function (): never {
        throw new RuntimeException('Historical reader must not resolve the current underwriting engine.');
    });
    $basis = app(RetainedStaffUnderwritingBasis::class)->project($fixture['application'], $fixture['submission']);
    expect($basis['pricing_policy_version'])->toBe('historical-pricing-policy')
        ->and($basis['calculation_version'])->toBe('historical-calculation')
        ->and($basis['acceptance_policy_version'])->toBe('historical-acceptance-policy')
        ->and($basis['scorecard']['scorecard_version'])->toBe('historical-scorecard')
        ->and($basis['scorecard']['components']['coverage'])->toBe(['numerator' => '299', 'denominator' => '3']);
});

it('refuses missing corrupt or cross-bound submissions before exposing selected facts', function (string $corruption): void {
    $fixture = retainedStaffBasisFixture();
    BusinessApplicationSubmission::retrieved(function (BusinessApplicationSubmission $submission) use ($corruption): void {
        if ($corruption === 'ciphertext') {
            $submission->setRawAttributes([...$submission->getAttributes(), 'payload' => 'invalid-ciphertext']);

            return;
        }
        if ($corruption === 'hash') {
            $submission->sha256 = str_repeat('0', 64);

            return;
        }
        $payload = $submission->payload;
        if ($corruption === 'partial') {
            unset($payload['review']['application']);
        } elseif ($corruption === 'native_application') {
            $submission->business_application_id = (string) Str::ulid();
        } elseif ($corruption === 'native_revision') {
            $submission->revision++;
        } elseif ($corruption === 'binding_hash') {
            $payload['binding_sha256'] = str_repeat('0', 64);
            $submission->binding_sha256 = $payload['binding_sha256'];
        } else {
            Arr::set($payload, $corruption, in_array($corruption, ['application_revision', 'review.application.revision'], true) ? 999 : (string) Str::ulid());
        }
        if ($corruption !== 'binding_hash') {
            rehashStaffBasisSubmission($submission, $payload);
        } else {
            $submission->payload = $payload;
            $submission->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
        }
    });
    $this->withoutExceptionHandling()->actingAs($fixture['audit']['staff']);
    expect(fn () => $this->getJson(route('api.v1.staff.applications.index', ['application' => $fixture['application']->id])))
        ->toThrow(RuntimeException::class, 'APPLICATION_SUBMISSION_INTEGRITY_FAILED');
})->with(['ciphertext', 'hash', 'partial', 'native_application', 'native_revision', 'binding_hash', 'submission_id', 'application_id',
    'application_revision', 'quote_id', 'agreement.application_id', 'agreement.quote_id', 'review.application.id',
    'review.application.business_id', 'review.application.revision']);

it('refuses inconsistent quote metadata and incomplete selected engine facts even with a consistent digest', function (string $field, mixed $value): void {
    $fixture = retainedStaffBasisFixture();
    $quote = $fixture['quote'];
    $payload = $quote->payload;
    Arr::set($payload, $field, $value);
    $quote->payload = $payload;
    $quote->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
    $submitted = $fixture['submission']->payload;
    $submitted['agreement']['quote_sha256'] = $quote->sha256;
    rehashStaffBasisSubmission($fixture['submission'], $submitted);
    BusinessApplicationQuote::retrieved(fn (BusinessApplicationQuote $record) => $record->setRawAttributes($quote->getAttributes()));
    expect(fn () => app(RetainedStaffUnderwritingBasis::class)->project($fixture['application'], $fixture['submission']))
        ->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
})->with([
    ['quote_id', 'wrong'], ['quote_revision', 999], ['application_id', 'wrong'], ['business_id', 'wrong'],
    ['application_revision', 0], ['application_revision', '2'], ['application_revision', 999], ['draft.title', 'Different retained draft'],
    ['result', null], ['result.eligible', false], ['result.policy_version', 'wrong'], ['result.calculation_version', 'wrong'],
    ['result.scorecard', null], ['result.scorecard.scorecard_version', ''], ['result.scorecard.rating', null], ['result.scorecard.rating', '5.1'],
    ['result.scorecard.band', ''], ['result.scorecard.band', 'Wrong'], ['result.scorecard.reason_codes', null],
    ['result.scorecard.reason_codes', ['private' => 'wrong']], ['result.scorecard.reason_codes', [123]], ['result.scorecard.reason_codes', ['']],
    ['result.scorecard.components.conduct', null], ['result.scorecard.components.coverage.numerator', '1.5'],
    ['result.scorecard.score.denominator', '0'], ['result.scorecard.uncapped_score.numerator', 85], ['evaluated_at', null],
]);

it('refuses missing and damaged original quotes on the selected endpoint', function (string $corruption): void {
    $fixture = retainedStaffBasisFixture();
    if ($corruption === 'missing') {
        BusinessApplicationSubmission::retrieved(function (BusinessApplicationSubmission $submission): void {
            $submission->business_application_quote_id = (string) Str::ulid();
            $payload = $submission->payload;
            $payload['quote_id'] = $payload['agreement']['quote_id'] = $submission->business_application_quote_id;
            rehashStaffBasisSubmission($submission, $payload);
        });
    } else {
        BusinessApplicationQuote::retrieved(function (BusinessApplicationQuote $quote) use ($corruption): void {
            if ($corruption === 'ciphertext') {
                $quote->setRawAttributes([...$quote->getAttributes(), 'payload' => 'invalid-ciphertext']);
            } else {
                $quote->sha256 = str_repeat('0', 64);
            }
        });
    }
    $this->withoutExceptionHandling()->actingAs($fixture['audit']['staff']);
    expect(fn () => $this->getJson(route('api.v1.staff.applications.index', ['application' => $fixture['application']->id])))
        ->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
})->with(['missing', 'ciphertext', 'hash']);

it('refuses cross application original quotes and wrong agreement or review bindings', function (string $binding): void {
    $fixture = retainedStaffBasisFixture();
    if ($binding === 'cross_application') {
        $other = retainedStaffBasisFixture();
        $quote = $other['quote'];
        $fixture['submission']->business_application_quote_id = $quote->id;
        $payload = $fixture['submission']->payload;
        $payload['quote_id'] = $payload['agreement']['quote_id'] = $quote->id;
        $payload['agreement']['quote_sha256'] = $quote->sha256;
    } else {
        $payload = $fixture['submission']->payload;
        Arr::set($payload, $binding, str_ends_with($binding, 'quote_revision') ? 999 : 'wrong');
    }
    rehashStaffBasisSubmission($fixture['submission'], $payload);
    expect(fn () => app(RetainedStaffUnderwritingBasis::class)->project($fixture['application'], $fixture['submission']))
        ->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
})->with(['cross_application', 'agreement.quote_revision', 'agreement.quote_sha256', 'review.quote.quote_id', 'review.quote.quote_revision',
    'review.quote.policy_version', 'review.quote.calculation_version', 'review.quote.rate_pct', 'public_evidence.rating']);

it('requires the existing staff read ability and fresh staff permission for selected underwriting evidence', function (): void {
    $fixture = retainedStaffBasisFixture();
    $url = route('api.v1.staff.applications.index', ['application' => $fixture['application']->id]);
    $this->getJson($url)->assertUnauthorized();
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['staff:applications:read']);
    $this->getJson($url)->assertForbidden()->assertJsonMissingPath('data.review.underwriting_basis');
    foreach ([[], ['staff:applications:review']] as $abilities) {
        Sanctum::actingAs($fixture['audit']['staff'], $abilities);
        $this->getJson($url)->assertForbidden()->assertJsonMissingPath('data.review.underwriting_basis');
    }
    Sanctum::actingAs($fixture['audit']['staff'], ['staff:applications:read']);
    $this->getJson($url)->assertOk()->assertJsonPath('data.review.underwriting_basis.quote.id', $fixture['quote']->id);
});

it('refuses decrypted evidence that is not an array or cannot be canonically authenticated', function (bool $quote, mixed $payload): void {
    $fixture = retainedStaffBasisFixture();
    if ($quote) {
        BusinessApplicationQuote::retrieved(fn (BusinessApplicationQuote $record) => $record->setAttribute('payload', $payload));
    } else {
        $fixture['submission']->setAttribute('payload', $payload);
    }
    expect(fn () => app(RetainedStaffUnderwritingBasis::class)->project($fixture['application'], $fixture['submission']))
        ->toThrow(RuntimeException::class, $quote ? 'APPLICATION_QUOTE_INTEGRITY_FAILED' : 'APPLICATION_SUBMISSION_INTEGRITY_FAILED');
})->with([[false, null], [true, null], [false, ['invalid' => 1.5]], [true, ['invalid' => 1.5]]]);

it('refuses the selected response when staff access is revoked during original quote retrieval', function (bool $disabled): void {
    $fixture = retainedStaffBasisFixture();
    BusinessApplicationQuote::retrieved(function () use ($fixture, $disabled): void {
        StaffAccount::query()->whereKey($fixture['audit']['staff']->id)->firstOrFail()->forceFill($disabled ? ['enabled' => false] : ['roles' => ['analyst']])->save();
    });
    Sanctum::actingAs($fixture['audit']['staff'], ['staff:applications:read']);
    $this->getJson(route('api.v1.staff.applications.index', ['application' => $fixture['application']->id]))->assertForbidden()
        ->assertJsonMissingPath('data.review.underwriting_basis');
})->with([true, false]);

it('adds no writes or financial effects when reading the selected queue', function (): void {
    $fixture = retainedStaffBasisFixture();
    DB::enableQueryLog();
    $page = app(StaffApplicationQueue::class)->page('pending', '', null, 20, $fixture['application']->id);
    expect($page['underwriting_basis']['quote']['id'])->toBe($fixture['quote']->id);
    foreach (DB::getQueryLog() as $query) {
        expect($query['query'])->toStartWith('select ')->not->toContain('for update');
    }
});
