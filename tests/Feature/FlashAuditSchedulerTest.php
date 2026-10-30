<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Application\Auditor\RequestFlashAudits;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\PartyNotice;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Lottery;
use Carbon\CarbonImmutable;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\AuditAssignmentFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    Notification::fake();
});

afterEach(function (): void {
    Lottery::determineResultNormally();
});

/** @param list<string> $businessIds */
function flashDueBusinesses(array $businessIds): void
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

it('sends a funded business an unannounced Flash audit when the hourly draw wins, and none while it is open', function (): void {
    $fixture = Fixture::make(1);
    flashDueBusinesses([$fixture['business']]);
    Lottery::alwaysWin();

    expect(Artisan::call('audits:request-flash'))->toBe(0)->and(Artisan::output())->toContain('Requested 1 Flash audits.');
    $assignment = AuditAssignment::query()->sole();
    $version = AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->sole();
    expect($assignment->business_id)->toBe($fixture['business'])->and($assignment->state['kind'])->toBe('flash')
        ->and($assignment->status)->toBe('offered')->and($assignment->party_id)->toBe($fixture['partners'][0]['party']->id)
        ->and($assignment->state['complete_by'])->toBe(now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z'))
        ->and($version->command)->toBe('audit.assignment.schedule')->and($version->actor_user_id)->toBeNull()
        ->and($version->reason)->toBe('Flash audit drawn at random.')
        ->and(PartyNotice::query()->where('kind', 'business.reporting_window_open')->exists())->toBeFalse()
        ->and(PartyNotice::query()->where('kind', 'auditor.job_offered')->count())->toBe(1);

    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0)
        ->and(AuditAssignment::query()->count())->toBe(1);
});

it('sends nothing when the draw loses or no business is funded', function (): void {
    $fixture = Fixture::make(1);
    Lottery::alwaysLose();
    flashDueBusinesses([$fixture['business']]);
    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0);

    Lottery::alwaysWin();
    flashDueBusinesses([]);
    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0)
        ->and(AuditAssignment::query()->exists())->toBeFalse();
});

it('logs a drawn business that cannot be audited and sends nothing', function (): void {
    flashDueBusinesses(['missing-business']);
    Lottery::alwaysWin();
    Log::shouldReceive('error')->once()->with('AUDIT_FLASH_REQUEST_FAILED', Mockery::on(fn (array $context): bool => $context['business_id'] === 'missing-business'));

    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0)
        ->and(AuditAssignment::query()->exists())->toBeFalse();
});

it('draws only from businesses owed a monthly audit, so a note issued this month joins the draw next month', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(now('UTC')->toIso8601String());
    PrimaryHoldingFixture::flushDeferredChecks();
    Lottery::alwaysWin();

    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(0);
    $this->travelTo(CarbonImmutable::now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHour());
    expect(app(RequestFlashAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(1)
        ->and(AuditAssignment::query()->sole()->business_id)->toBe($campaign->businessId);
});
