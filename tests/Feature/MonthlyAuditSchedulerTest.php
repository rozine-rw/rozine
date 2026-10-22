<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Application\Auditor\RequestMonthlyAudits;
use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Domain\Auditor\AuditReportWindow;
use App\Infrastructure\Auditor\EloquentMonthlyAuditBusinesses;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\PartyNotice;
use App\Models\User;
use App\Notifications\Business\ReportingWindowOpen;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    Notification::fake();
});

/** @param list<string> $businessIds */
function dueBusinesses(array $businessIds): void
{
    app()->instance(MonthlyAuditBusinesses::class, new class($businessIds) implements MonthlyAuditBusinesses
    {
        /** @param list<string> $businessIds */
        public function __construct(private array $businessIds) {}

        public function due(DateTimeImmutable $now): array
        {
            return $this->businessIds;
        }
    });
}

it('dispatches each funded business its monthly audit once, in the report window of the month just ended', function (): void {
    $fixture = Fixture::ready();
    $business = $fixture['audit']['business'];
    dueBusinesses([$business, 'missing-business']);
    Log::shouldReceive('error')->times(3)->with('AUDIT_MONTHLY_REQUEST_FAILED', Mockery::on(fn (array $context): bool => $context['business_id'] === 'missing-business'));
    $this->travelTo(CarbonImmutable::now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHours(6));

    expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0)
        ->and(AuditAssignment::query()->where('business_id', $business)->count())->toBe(1);

    Fixture::seal($fixture);
    Fixture::cosign($fixture);
    expect(Artisan::call('audits:request-monthly'))->toBe(0)->and(Artisan::output())->toContain('Requested 1 monthly audits.');
    $flash = $fixture['assignment']->refresh();
    $routine = AuditAssignment::query()->where('business_id', $business)->whereKeyNot($flash->id)->sole();
    $version = AuditAssignmentVersion::query()->where('assignment_id', $routine->id)->sole();
    expect($flash->status)->toBe('completed')->and($routine->state['kind'])->toBe('routine')
        ->and($version->command)->toBe('audit.assignment.schedule')->and($version->actor_user_id)->toBeNull()
        ->and($version->reason)->toBe('Monthly audit for '.now('Africa/Kigali')->subMonthNoOverflow()->format('Y-m').'.')
        ->and(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0);
    $period = now('Africa/Kigali')->subMonthNoOverflow()->format('Y-m');
    $notice = PartyNotice::query()->where('kind', 'business.reporting_window_open')->sole();
    expect($notice->facts)->toMatchArray(['business_id' => $business, 'period' => $period, 'closes_at' => app(AuditReportWindow::class)->dueAt($period)]);
    expect(app(BusinessNoticeAudience::class)->recipients($business))->not->toBeEmpty();
    Notification::assertSentTo(User::query()->whereIn('id', app(BusinessNoticeAudience::class)->recipients($business))->get(), ReportingWindowOpen::class,
        fn (ReportingWindowOpen $mail): bool => $mail->period === CarbonImmutable::parse($period.'-01')->format('F Y')
            && $mail->url === route('business.audit-prep', ['business' => $business]));
    $this->actingAs($fixture['audit']['authority']['users'][0])->getJson(route('business.notices.index', $business))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'business.reporting_window_open')
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$business}/audit-prep", 'method' => 'get']);

    $this->travelTo(now('Africa/Kigali')->startOfMonth()->addDays(8));
    expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0);
});

it('dispatches nothing more in a month that already has a routine audit', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    Fixture::cosign($fixture);
    dueBusinesses([$fixture['audit']['business']]);
    $this->travelTo(CarbonImmutable::now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHours(6));
    expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(1);

    $routine = AuditAssignment::query()->where('business_id', $fixture['audit']['business'])->where('status', '<>', 'completed')->sole();
    $routine->forceFill(['status' => 'completed', 'completed_at' => now(), 'state' => [...$routine->state, 'status' => 'completed']])->save();

    expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0);
});

it('owes a monthly audit only for notes that lived through the month and still have instalments due', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(now('UTC')->toIso8601String());
    PrimaryHoldingFixture::flushDeferredChecks();
    $businesses = app(EloquentMonthlyAuditBusinesses::class);
    $last = CarbonImmutable::parse($instruction->dueDates[count($instruction->dueDates) - 1], 'Africa/Kigali');

    expect($businesses->due(now()->toDateTimeImmutable()))->toBe([]);
    $this->travelTo(CarbonImmutable::now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHour());
    expect($businesses->due(now()->toDateTimeImmutable()))->toBe([$campaign->businessId]);
    $this->travelTo($last->startOfMonth()->addHour());
    expect($businesses->due(now()->toDateTimeImmutable()))->toBe([$campaign->businessId]);
    $this->travelTo($last->addMonthNoOverflow()->startOfMonth()->addHour());
    expect($businesses->due(now()->toDateTimeImmutable()))->toBe([]);
});
