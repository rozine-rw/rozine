<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Application\Business\RecordReadinessConfirmation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessProfile;
use App\Models\BusinessReadinessRequirement;
use App\Models\PartyNotice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;

/*
 * The readiness requirements (BRS v3.0 RDY-R03, NTF-R01): the hourly sweep settles each entered
 * Business's stage, records the requirements it triggers, turns them Overdue on the exact Kigali
 * day their grace ends and tells the Business. The CPA's visit confirms or lapses them.
 */

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00:00', 'Africa/Kigali'));
    $this->withoutVite();
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::publish($fixture);
    $this->business = BusinessProfile::query()->whereKey($fixture['audit']['business'])->sole();
    $this->user = $fixture['audit']['authority']['users'][0];
    /** Serves these published months for the entered business, newest first as the port answers. */
    $this->published = function (array $months): void {
        app()->instance(PublishedMonthlyReports::class, new class($months) implements PublishedMonthlyReports
        {
            /** @param array<string, string> $months */
            public function __construct(private array $months) {}

            public function forBusiness(string $businessId): array
            {
                $reports = [];
                foreach (array_reverse($this->months, true) as $period => $inflow) {
                    $reports[] = ['report_id' => strtolower((string) Str::ulid()), 'period' => $period, 'inflow' => $inflow, 'outflow' => '0', 'net' => $inflow,
                        'operational_status' => 'active', 'differences' => false, 'auditor' => ['name' => 'Auditor', 'licence' => 'ICPAR-1', 'sealed_on' => '2026-10-01'],
                        'auditor_note' => '', 'business_note' => ''];
                }

                return $reports;
            }
        });
    };
    $this->sweep = function (): void {
        expect(Artisan::call('readiness:evaluate'))->toBe(0)->and(Artisan::output())->toBe("Evaluated readiness for 1 businesses.\n");
    };
    $this->on = fn (string $date) => $this->travelTo(CarbonImmutable::parse($date.' 09:00:00', 'Africa/Kigali'));
    $this->rows = fn (): array => BusinessReadinessRequirement::query()->orderBy('code')->get()
        ->mapWithKeys(fn (BusinessReadinessRequirement $row): array => [$row->code => [$row->status, $row->due_on?->toDateString(), $row->met_on?->toDateString()]])->all();
    $this->notices = fn (string $kind): array => PartyNotice::query()->where('kind', $kind)->orderBy('event_key')->get()
        ->map(fn (PartyNotice $notice): array => [$notice->party_id, $notice->event_key, $notice->facts])->all();
    $this->requirements = fn (): array => array_column($this->actingAs($this->user)->get(route('business.readiness', ['business' => $this->business->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/readiness'))->viewData('page')['props']['requirements'], null, 'code');
});

it('records what the settled stage triggers, tells the Business once, and turns it Overdue on the exact day', function (): void {
    $id = $this->business->id;
    $party = $this->business->entity_party_id;
    ($this->published)(['2026-07' => '12000000', '2026-08' => '12000000', '2026-09' => '12000000']);

    // Before the first sweep the page already shows C1 and F1 met at entry and nothing else in force.
    expect(($this->requirements)())->toMatchArray([
        'C1' => ['code' => 'C1', 'status' => 'met', 'due_on' => null, 'days_left' => null, 'met_on' => null, 'applies_from' => null],
        'F2' => ['code' => 'F2', 'status' => 'not_triggered', 'due_on' => null, 'days_left' => null, 'met_on' => null, 'applies_from' => ['kind' => 'stage', 'value' => 2]],
        'P1' => ['code' => 'P1', 'status' => 'not_triggered', 'due_on' => null, 'days_left' => null, 'met_on' => null, 'applies_from' => ['kind' => 'headcount', 'value' => 10]],
    ]);

    ($this->sweep)();
    ($this->sweep)();
    expect(($this->rows)())->toBe(['C1' => ['met', null, null], 'F1' => ['met', null, null], 'F2' => ['due', '2027-02-08', null]])
        ->and(($this->notices)('business.readiness_requirement_due'))->toEqual([[$party, "readiness-due:{$id}:F2:2026-11-10", ['business_id' => $id, 'code' => 'F2', 'due_on' => '2027-02-08']]])
        // The first reading sets the stage; it is not a change.
        ->and(($this->notices)('business.readiness_stage_changed'))->toBe([])
        ->and(($this->requirements)()['F2'])->toMatchArray(['status' => 'due', 'due_on' => '2027-02-08', 'days_left' => 90]);

    ($this->on)('2027-01-24');
    ($this->sweep)();
    expect(($this->notices)('business.readiness_requirement_closing'))->toBe([]);

    ($this->on)('2027-01-25');
    ($this->sweep)();
    ($this->on)('2027-02-07');
    ($this->sweep)();
    $facts = ['business_id' => $id, 'code' => 'F2', 'due_on' => '2027-02-08'];
    expect(($this->notices)('business.readiness_requirement_closing'))->toEqual([[$party, "readiness-closing:{$id}:F2:2027-02-08", $facts]])
        ->and(app(ReadinessRequirementStore::class)->hasOverdue($id, '2027-02-07'))->toBeFalse();

    // On the day the grace ends the page and the store read Overdue before the sweep records it.
    ($this->on)('2027-02-08');
    expect(($this->requirements)()['F2'])->toMatchArray(['status' => 'overdue', 'due_on' => '2027-02-08', 'days_left' => 0])
        ->and(($this->rows)()['F2'][0])->toBe('due')
        ->and(app(ReadinessRequirementStore::class)->hasOverdue($id, '2027-02-08'))->toBeTrue();
    ($this->sweep)();
    ($this->sweep)();
    expect(($this->rows)()['F2'])->toBe(['overdue', '2027-02-08', null])
        ->and(($this->notices)('business.readiness_requirement_overdue'))->toEqual([[$party, "readiness-overdue:{$id}:F2:2027-02-08", $facts]])
        ->and(app(ReadinessRequirementStore::class)->hasOverdue($id, '2027-02-08'))->toBeTrue();

    $this->actingAs($this->user)->getJson(route('business.notices.index', $id))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'business.readiness_requirement_overdue')
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$id}/readiness", 'method' => 'get']);
});

it('tells the Business on the report that moves its stage, and keeps a requirement in force once the stage falls', function (): void {
    $id = $this->business->id;
    ($this->published)(['2026-03' => '12000000', '2026-04' => '12000000', '2026-05' => '12000000', '2026-06' => '40000000', '2026-07' => '41000000', '2026-08' => '42000000']);
    ($this->sweep)();
    expect(array_keys(($this->rows)()))->toBe(['C1', 'F1', 'F2'])
        ->and(($this->notices)('business.readiness_stage_changed'))->toBe([]);

    ($this->published)(['2026-03' => '12000000', '2026-04' => '12000000', '2026-05' => '12000000', '2026-06' => '40000000', '2026-07' => '41000000', '2026-08' => '42000000', '2026-09' => '43000000']);
    ($this->sweep)();
    ($this->sweep)();
    expect(($this->rows)())->toMatchArray(['F3' => ['due', '2027-03-10', null], 'G1' => ['due', '2027-03-10', null]])
        ->and(($this->notices)('business.readiness_stage_changed'))->toEqual([[$this->business->entity_party_id, "readiness-stage:{$id}:2026-09:3",
            ['business_id' => $id, 'stage' => '3', 'previous' => '2', 'period' => '2026-09']]]);

    ($this->published)(['2026-07' => '1000000', '2026-08' => '1000000', '2026-09' => '1000000']);
    ($this->sweep)();
    expect(array_keys(($this->rows)()))->toBe(['C1', 'F1', 'F2', 'F3', 'G1']);
});

it('meets a requirement on the CPA\'s confirmation and lapses it with 60 days to meet it again', function (): void {
    $id = $this->business->id;
    $record = app(RecordReadinessConfirmation::class);
    ($this->published)(['2026-07' => '12000000', '2026-08' => '12000000', '2026-09' => '12000000']);
    ($this->sweep)();

    $record->confirm($id, 'F2', ['accountant' => 'ICPAR-77', 'software' => 'QuickBooks'], '2026-11-12');
    $row = BusinessReadinessRequirement::query()->where('code', 'F2')->sole();
    expect([$row->status, $row->due_on, $row->met_on?->toDateString(), $row->confirmation, $row->revision])
        ->toEqual(['met', null, '2026-11-12', ['accountant' => 'ICPAR-77', 'software' => 'QuickBooks'], 2])
        ->and(($this->requirements)()['F2'])->toMatchArray(['status' => 'met', 'due_on' => null, 'days_left' => null, 'met_on' => '2026-11-12']);

    $record->lapse($id, 'F2', '2026-12-01');
    expect(($this->rows)()['F2'])->toBe(['lapsed', '2027-01-30', '2026-11-12'])
        ->and(($this->requirements)()['F2'])->toMatchArray(['status' => 'lapsed', 'due_on' => '2027-01-30', 'days_left' => 81]);

    ($this->on)('2027-01-30');
    ($this->sweep)();
    expect(($this->rows)()['F2'])->toBe(['overdue', '2027-01-30', '2026-11-12']);

    $record->confirm($id, 'F2', ['accountant' => 'ICPAR-78'], '2027-02-02');
    expect(($this->rows)()['F2'])->toBe(['met', null, '2027-02-02']);

    expect(fn () => $record->confirm($id, 'F3', [], '2027-02-02'))->toThrow(CommandRejection::class, 'READINESS_REQUIREMENT_NOT_TRIGGERED')
        ->and(fn () => $record->lapse($id, 'F3', '2027-02-02'))->toThrow(CommandRejection::class, 'READINESS_REQUIREMENT_NOT_MET');
});

it('sweeps no Business that has not entered the platform', function (): void {
    AuditSealingFixture::ready(kind: 'monthly');
    ($this->published)([]);

    // Only the entered Business is evaluated; with no reading its stage is 0 and only C1 and F1 apply.
    ($this->sweep)();
    expect(BusinessReadinessRequirement::query()->distinct()->pluck('business_id')->all())->toBe([$this->business->id])
        ->and(array_keys(($this->rows)()))->toBe(['C1', 'F1']);
});
