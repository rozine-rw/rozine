<?php

declare(strict_types=1);

use App\Application\Business\Contracts\AcceptedApplicationStore;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Identity\RecordConsentRelease;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditReport;
use App\Models\AuditSigningKeyRevocation;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->store = app(BusinessCampaignStore::class);
    $this->release = fn (): array => $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $this->publish = fn (int $revision = 5): array => $this->store->publish($this->fixture['audit']['authority']['users'][0]->id, 1,
        $this->fixture['audit']['business'], $this->fixture['application']->id, $revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->input = fn (): array => app(AcceptedApplicationStore::class)->withReleaseInput($this->fixture['audit']['business'],
        $this->fixture['application']->id, fn (array $input): array => $input);
});

afterEach(function (): void {
    foreach ([AuditReport::class, BusinessExposureReservation::class, BusinessApplicationRelease::class, BusinessCampaign::class,
        BusinessApplicationSubmission::class, CommandOperation::class] as $model) {
        Event::forget('eloquent.retrieved: '.$model);
    }
});

it('keeps independent publication facts after a released or published application becomes stale', function (string $change, bool $published): void {
    ($this->release)();
    if ($published) {
        expect(($this->publish)()['code'])->toBe('LISTING_PUBLISHED');
    }
    if ($change === 'terms') {
        app(RecordConsentRelease::class)->handle($this->fixture['audit']['staff']->id, 1, 'withdrawn', [], [], false,
            'fixture:withdrawn', 'Withdraw the accepted release.', (string) Str::uuid());
    } elseif ($change === 'report') {
        AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $this->fixture['key']->id]);
    } else {
        BusinessCreditFactsFixture::record($this->fixture['audit']['staff'], $this->fixture['audit']['business'], 1,
            facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => $change === 'restriction']);
    }
    $cause = match ($change) {
        'terms' => 'TERMS_CHANGED', 'report' => 'REPORT_NOT_CURRENT', 'restriction' => 'RESTRICTION_ACTIVE', default => 'QUOTE_STALE',
    };
    $parameters = ['business' => $this->fixture['audit']['business'], 'application' => $this->fixture['application']->id];
    $this->actingAs($this->fixture['audit']['authority']['users'][0])->get(route('business.applications.publish.show', $parameters))
        ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('business/publish')
        ->where('release.state', 'released')->where('release.causes', [$cause])->where('allowed_actions', [])
        ->where('prerequisites.0.met', true)->where('prerequisites.1.met', true)
        ->where('prerequisites.2.met', ! in_array($change, ['credit', 'restriction'], true))->where('prerequisites.3.met', $change !== 'terms'));
    $staff = $this->actingAs($this->fixture['audit']['staff'])->getJson(route('staff.applications.show', $parameters))
        ->assertOk()->assertJsonPath('data.release.state', 'released')->assertJsonPath('data.release.allowed_actions', []);
    $gate = $change === 'report' ? 2 : ($change === 'terms' ? 1 : 0);
    $staff->assertJsonPath('data.release.gates.'.$gate.'.cause', $cause);
    if ($change === 'report') {
        $staff->assertJsonPath('data.release.gates.0.state', 'passed')->assertJsonPath('data.release.gates.1.state', 'passed');
    } elseif ($change === 'terms') {
        $staff->assertJsonPath('data.release.gates.0.state', 'passed')->assertJsonPath('data.release.gates.2.cause', 'RELEASE_CHECK_NOT_COMPLETED');
    } else {
        $staff->assertJsonPath('data.release.gates.1.cause', 'RELEASE_CHECK_NOT_COMPLETED');
    }
})->with(['credit', 'terms', 'report', 'restriction'])->with([false, true]);

it('refuses historical accepted submissions without creating a missing exposure reservation', function (): void {
    DB::statement('ALTER TABLE business_exposure_reservations DISABLE TRIGGER business_exposure_reservations_protected');
    BusinessExposureReservation::query()->delete();
    DB::statement('ALTER TABLE business_exposure_reservations ENABLE TRIGGER business_exposure_reservations_protected');
    expect(($this->release)()['code'])->toBe('EXPOSURE_RESERVATION_REQUIRED')
        ->and(BusinessExposureReservation::query()->count())->toBe(0)->and(BusinessApplicationRelease::query()->count())->toBe(0);
});

it('refuses release and reports no retained prerequisites for an unsubmitted application', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    expect($this->store->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid())['code'])->toBe('APPLICATION_NOT_SUBMITTED')
        ->and(app(AcceptedApplicationStore::class)->publicationPrerequisites($fixture['audit']['business'], $fixture['application']->id))
        ->toBe(['signatures_retained' => false, 'quote_current' => false, 'terms_current' => false]);
});

it('refuses a changed mandate even with retained historical signatures', function (): void {
    $authority = $this->fixture['audit']['authority'];
    $authority['terms']['effective_at'] = now('UTC')->subSeconds(30)->format('Y-m-d\TH:i:s\Z');
    BusinessAuthorityFixture::configure($authority, 1);
    expect(($this->release)()['code'])->toBe('AUTHORITY_CHANGED');
});

it('refuses missing retained signatures independently of quote and terms freshness', function (): void {
    DB::statement('ALTER TABLE business_application_signatures DISABLE TRIGGER business_application_signatures_immutable');
    BusinessApplicationSignature::query()->delete();
    DB::statement('ALTER TABLE business_application_signatures ENABLE TRIGGER business_application_signatures_immutable');
    expect(($this->release)()['code'])->toBe('SIGNATURES_REQUIRED')
        ->and(app(AcceptedApplicationStore::class)->publicationPrerequisites($this->fixture['audit']['business'], $this->fixture['application']->id))
        ->toBe(['signatures_retained' => false, 'quote_current' => true, 'terms_current' => true]);
});

it('rejects substituted exposure bindings and mismatched accepted principal', function (string $field, string $cause): void {
    Event::listen('eloquent.retrieved: '.BusinessExposureReservation::class, function (BusinessExposureReservation $record) use ($field): void {
        $payload = $record->payload;
        $payload[$field] = $field === 'principal' ? '3000000' : str_repeat('0', 64);
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
        if ($field === 'principal') {
            $record->principal = $payload[$field];
        }
    });
    if ($field === 'principal') {
        expect(($this->release)()['code'])->toBe($cause);
    } else {
        expect(fn () => ($this->input)())->toThrow(RuntimeException::class, $cause);
    }
})->with([['submission_sha256', 'BUSINESS_EXPOSURE_INTEGRITY_FAILED'], ['principal', 'ENGINE_GATE_FAILED']]);

it('refuses a published report whose retained binding no longer matches its seal', function (): void {
    Event::listen('eloquent.retrieved: '.AuditReport::class, function (AuditReport $record): void {
        $record->binding = [...$record->binding, 'changed' => true];
    });
    expect(($this->release)()['code'])->toBe('REPORT_NOT_CURRENT');
});

it('requires an actual required signatory even when another representative has signing permission', function (): void {
    $fixture = AuditSealingFixture::ready(2, requiredSignatories: 1);
    expect(fn () => $this->store->publish($fixture['audit']['authority']['users'][1]->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        $fixture['application']->revision, 'listing-fee-waiver-1', (string) Str::uuid()))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
});

it('records a stale publish revision and never publishes a different business application', function (): void {
    expect(($this->publish)(4)['code'])->toBe('VERSION_CONFLICT');
    $other = BusinessQuoteFixture::ready();
    expect(fn () => $this->store->publish($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'],
        $other['application']->id, $other['application']->revision, 'listing-fee-waiver-1', (string) Str::uuid()))->toThrow(CommandRejection::class, 'APPLICATION_NOT_FOUND')
        ->and(BusinessCampaign::query()->count())->toBe(0);
});

it('refuses a changed retained release binding on reads and Publish', function (): void {
    ($this->release)();
    Event::listen('eloquent.retrieved: '.BusinessApplicationRelease::class, function (BusinessApplicationRelease $record): void {
        $payload = $record->payload;
        $payload['binding']['report']['digest'] = str_repeat('0', 64);
        $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    });
    expect(($this->publish)()['code'])->toBe('STAFF_RELEASE_REQUIRED')
        ->and($this->store->staffPage($this->fixture['audit']['staff']->id, $this->fixture['application']->id)['cause'])->toBe('STAFF_RELEASE_REQUIRED');
});

it('fails closed on corrupt persisted release campaign and submitted page records', function (string $model, string $cause): void {
    ($this->release)();
    $published = ($this->publish)();
    Event::listen('eloquent.retrieved: '.$model, function (Model $record): void {
        $record->setAttribute('sha256', str_repeat('0', 64));
    });
    $read = $model === BusinessCampaign::class
        ? fn (): array => $this->store->campaign($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'], $published['data']['campaign_id'])
        : fn (): array => $this->store->staffPage($this->fixture['audit']['staff']->id, $this->fixture['application']->id);
    expect($read)->toThrow(RuntimeException::class, $cause);
})->with([[BusinessApplicationRelease::class, 'APPLICATION_RELEASE_INTEGRITY_FAILED'], [BusinessCampaign::class, 'CAMPAIGN_INTEGRITY_FAILED'],
    [BusinessApplicationSubmission::class, 'APPLICATION_SUBMISSION_INTEGRITY_FAILED']]);

it('renders the Inertia denial page on the new Business reads after role withdrawal', function (string $name): void {
    $user = $this->fixture['audit']['authority']['users'][0];
    RoleMembership::query()->where('party_id', $user->party_id)->where('role', 'business')->update(['status' => 'revoked']);
    $this->actingAs($user)->get(route($name, ['business' => $this->fixture['audit']['business'], 'application' => $this->fixture['application']->id,
        'campaign' => (string) Str::ulid()]))->assertForbidden()->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied'));
})->with(['business.applications.publish.show', 'business.campaigns.show']);

it('renders the staff Inertia denial page without a current staff grant', function (): void {
    $this->actingAs(User::factory()->create())->get(route('staff.applications.show', ['application' => $this->fixture['application']->id]))
        ->assertForbidden()->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied'));
});

it('refuses a campaign identifier outside the current business', function (): void {
    expect(fn () => $this->store->campaign($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'], (string) Str::ulid()))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FOUND');
});

it('refuses malformed release and publication receipt targets', function (string $command, string $target): void {
    $staff = $this->fixture['audit']['staff'];
    $user = $this->fixture['audit']['authority']['users'][0];
    $request = (string) Str::uuid();
    $release = $command === 'application.release';
    app(OperationJournal::class)->execute($release ? 'staff:'.$staff->id : 'party:'.$user->party_id, $release ? $staff->id : $user->id,
        $command, $request, $target, (string) Str::ulid(), [], fn () => null, fn (): OperationResult => new OperationResult('RECORDED', [], 1));
    expect(fn () => $release ? $this->store->findRelease($staff->id, $request) : $this->store->findPublication($user->id, 1, $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
})->with([['application.release', 'business'], ['application.publish', 'business'], ['application.publish', 'application']]);

it('reports authority withdrawal without inventing passed gates or current prerequisites', function (): void {
    $this->fixture['audit']['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    $page = $this->store->staffPage($this->fixture['audit']['staff']->id, $this->fixture['application']->id);
    expect($page['cause'])->toBe('AUTHORITY_CHANGED')->and($page['gates']['authority'])->toBe('AUTHORITY_CHANGED')
        ->and($page['gates']['engine'])->toBe('RELEASE_CHECK_NOT_COMPLETED')
        ->and($page['prerequisites'])->toBe(['signatures_retained' => false, 'quote_current' => false, 'terms_current' => false]);
});

it('refuses publication lookup after identity relinking wins between discovery and authorization', function (): void {
    $fixture = AuditSealingFixture::ready(2, requiredSignatories: 1);
    $user = $fixture['audit']['authority']['users'][0];
    $replacement = $fixture['audit']['authority']['users'][1]->refresh();
    $request = (string) Str::uuid();
    $this->store->publish($user->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        $fixture['application']->revision, 'listing-fee-waiver-1', $request);
    Event::listen('eloquent.retrieved: '.CommandOperation::class, function () use ($user, $replacement): void {
        $user->forceFill(['party_id' => $replacement->party_id, 'context_revision' => 2,
            'active_membership_id' => $replacement->active_membership_id, 'active_membership_revision' => 1])->save();
    });
    expect(fn () => $this->store->findPublication($user->id, 2, $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('refuses unknown staff application identifiers and unlinked receipt actors', function (): void {
    expect(fn () => $this->store->staffPage($this->fixture['audit']['staff']->id, (string) Str::ulid()))->toThrow(CommandRejection::class, 'APPLICATION_NOT_FOUND')
        ->and(fn () => $this->store->findPublication(User::factory()->create()->id, 1, (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});
