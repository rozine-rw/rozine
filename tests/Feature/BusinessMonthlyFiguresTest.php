<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Models\BusinessMonthlySubmission;
use App\Models\CommandOperation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;

/*
 * The monthly submission (BRS v3.0 VIS-R04): while its monthly audit is open, a Business declares
 * its figures for the audited month once, before the Audit Partner verifies them. The forecast is
 * for the month after, pre-filled with the verified 3-month average.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-10-02 10:00', 'Africa/Kigali'));
    $this->fixture = AuditSealingFixture::ready(kind: 'monthly');
    $this->business = $this->fixture['audit']['business'];
    $this->user = $this->fixture['audit']['authority']['users'][0];
    $this->page = function (User $user): array {
        return $this->actingAs($user)->get(route('business.monthly-figures.show', ['business' => $this->business]))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('business/monthly-figures'))->viewData('page')['props'];
    };
    $this->send = function (array $overrides = []): TestResponse {
        $page = ($this->page)($this->user);

        return $this->actingAs($this->user)->post(route('business.monthly-figures.store', ['business' => $this->business]), [...[
            'request_id' => (string) Str::uuid(), 'identity_context_revision' => $page['identity_context_revision'], 'period' => $page['period'], 'money_in' => '12500000',
            'costs' => ['stock' => '4000000', 'staff' => '2000000', 'premises' => '800000', 'transport' => '300000', 'taxes' => '150000', 'other' => '50000'],
            'paid_to_owners' => '1000000', 'forecast' => '12000000', 'order' => null,
            'changes' => ['owners' => false, 'premises' => false, 'borrowing' => true, 'customers' => false]], ...$overrides]);
    };
    /** Serves these published months for any business. */
    $this->published = function (array $months): void {
        app()->instance(PublishedMonthlyReports::class, new class($months) implements PublishedMonthlyReports
        {
            /** @param array<string, string> $months */
            public function __construct(private array $months) {}

            public function forBusiness(string $businessId): array
            {
                return array_map(fn (string $period, string $inflow): array => ['report_id' => strtolower((string) Str::ulid()), 'period' => $period,
                    'inflow' => $inflow, 'outflow' => '0', 'net' => $inflow, 'operational_status' => 'active', 'differences' => false,
                    'auditor' => ['name' => 'Auditor', 'licence' => 'ICPAR-1', 'sealed_on' => '2026-08-01'], 'auditor_note' => '', 'business_note' => ''],
                    array_keys($this->months), $this->months);
            }
        });
    };
});

it('opens the month the monthly audit covers with nothing sent and no pre-fill before any published report', function (): void {
    $business = $this->business;
    $props = ($this->page)($this->user);

    expect(array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head', 'cart_count', 'checkout_holds'])))
        ->toBe(['identity_context_revision' => $props['identity_context_revision'], 'period' => '2026-09-01', 'seal_by' => '2026-10-07T21:59:59Z',
            'forecast_period' => '2026-10-01', 'forecast_basis' => null, 'cost_categories' => ['stock', 'staff', 'premises', 'transport', 'taxes', 'other'],
            'sent' => null, 'links' => ['submit' => ['url' => "/business/{$business}/monthly-figures", 'method' => 'post'],
                'close' => ['url' => "/business/{$business}/audit-prep", 'method' => 'get'], 'home' => ['url' => "/business/{$business}", 'method' => 'get'],
                'reports' => ['url' => "/business/{$business}/reports", 'method' => 'get'], 'readiness' => ['url' => "/business/{$business}/readiness", 'method' => 'get'],
                'market' => ['url' => "/business/{$business}/market", 'method' => 'get'], 'profile' => ['url' => "/business/{$business}/profile", 'method' => 'get'],
                'launcher' => ['url' => '/dashboard', 'method' => 'get']]]);
});

it('records the figures once, journals only their digest, and shows what was sent', function (): void {
    ($this->send)()->assertRedirect(route('business.monthly-figures.show', ['business' => $this->business]))->assertSessionHasNoErrors();

    $record = BusinessMonthlySubmission::query()->sole();
    expect($record->only(['business_id', 'period', 'forecast_period', 'policy_version', 'submitted_by_party_id']))->toBe(['business_id' => $this->business,
        'period' => '2026-09', 'forecast_period' => '2026-10', 'policy_version' => 'brs-3.0-vis-r04', 'submitted_by_party_id' => $this->user->party_id])
        ->and(($this->page)($this->user)['sent'])->toBe(['money_in' => '12500000', 'costs' => ['stock' => '4000000', 'staff' => '2000000',
            'premises' => '800000', 'transport' => '300000', 'taxes' => '150000', 'other' => '50000'], 'costs_total' => '7300000',
            'paid_to_owners' => '1000000', 'forecast' => '12000000', 'forecast_basis' => null, 'order' => null,
            'changes' => ['owners' => false, 'premises' => false, 'borrowing' => true, 'customers' => false], 'submitted_at' => '2026-10-02T08:00:00Z'])
        ->and(json_encode(CommandOperation::query()->where('command', 'business.monthly_submission.send')->sole()->getAttributes()))->not->toContain('12500000');

    ($this->send)(['money_in' => '1'])->assertSessionHasErrors(['form' => "This month's figures were already sent."]);
    expect(BusinessMonthlySubmission::query()->count())->toBe(1);
});

it('pre-fills the forecast from the verified average and asks for the signed order past 25%', function (): void {
    ($this->published)(['2026-08' => '10000000', '2026-07' => '11000000', '2026-06' => '12000000', '2026-05' => '90000000']);
    expect(($this->page)($this->user)['forecast_basis'])->toBe('11000000');

    ($this->send)(['forecast' => '14000000'])->assertSessionHasErrors(['order' => 'This forecast is more than 25% away from your verified average. Name the signed order behind it.']);
    ($this->send)(['forecast' => '14000000', 'order' => 'Order 114 from Simba Supermarket'])->assertSessionHasNoErrors();

    expect(BusinessMonthlySubmission::query()->sole()->only(['forecast', 'forecast_basis', 'order']))
        ->toBe(['forecast' => '14000000', 'forecast_basis' => '11000000', 'order' => 'Order 114 from Simba Supermarket']);
});

it('refuses figures that are not whole amounts, and anything once the window has closed', function (): void {
    ($this->send)(['money_in' => '12.5', 'changes' => ['owners' => true]])
        ->assertSessionHasErrors(['money_in' => 'Enter a whole amount in Rwandan francs, 0 or more.', 'changes.premises' => 'Answer yes or no.']);
    expect(BusinessMonthlySubmission::query()->count())->toBe(0);

    $page = ($this->page)($this->user);
    AuditSealingFixture::seal($this->fixture);
    $this->actingAs($this->user)->get(route('business.monthly-figures.show', ['business' => $this->business]))
        ->assertRedirect(route('business.show', ['business' => $this->business]));
    $this->actingAs($this->user)->post(route('business.monthly-figures.store', ['business' => $this->business]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $page['identity_context_revision'], 'period' => $page['period'], 'money_in' => '1', 'costs' => [], 'paid_to_owners' => '0', 'forecast' => '0',
        'changes' => []])->assertSessionHasErrors(['form' => 'The window for this month has closed, so the figures can no longer be sent.']);
});

it('refuses a form left open for an earlier month instead of recording it against the open one', function (): void {
    $open = ($this->page)($this->user)['period'];
    $earlier = CarbonImmutable::parse($open)->subMonth()->toDateString();

    ($this->send)(['period' => $earlier])
        ->assertSessionHasErrors(['form' => "This form was opened for an earlier month. Reload the page to send this month's figures."]);
    expect(BusinessMonthlySubmission::query()->count())->toBe(0);
    ($this->send)()->assertSessionHasNoErrors();
    expect(BusinessMonthlySubmission::query()->sole()->period)->toBe(substr($open, 0, 7));
});

it('lets nobody without the Business authority read or send its figures', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('business.monthly-figures.show', ['business' => $this->business]))->assertForbidden();
    $this->actingAs($stranger)->post(route('business.monthly-figures.store', ['business' => $this->business]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 0, 'period' => '2026-10-01', 'money_in' => '1', 'costs' => [], 'paid_to_owners' => '0', 'forecast' => '0', 'changes' => []])
        ->assertNotFound();
    expect(BusinessMonthlySubmission::query()->count())->toBe(0);
});
