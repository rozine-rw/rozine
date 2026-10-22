<?php

declare(strict_types=1);

use App\Models\AuditPartnerCredit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditorFixture;
use Tests\Support\PrimaryIssuanceFixture;

use function Pest\Laravel\actingAs;

/*
 * BRS v2.0 FEE-R02 and the Audit Partner's earnings statement: 40% of the 3% platform fee on each raise
 * the partner audited, credited when it is paid out, shown on their Portfolio and Profile.
 */

beforeEach(function (): void {
    $this->withoutVite();
});

/** @return array<string, mixed> the earnings a page sends to this user */
function auditorEarnings(User $user, string $route): array
{
    $earnings = [];
    actingAs($user)->get(route($route, $route === 'auditor.profile' ? ['section' => 'earnings'] : []))->assertOk()
        ->assertInertia(function (Assert $page) use (&$earnings): Assert {
            $earnings = $page->toArray()['props']['earnings'];

            return $page;
        });

    return $earnings;
}

it('states the platform fee share credited to the partner who sealed the report, on Portfolio and Profile', function (): void {
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $credit = AuditPartnerCredit::query()->sole();
    $auditor = User::query()->where('party_id', $credit->auditor_party_id)->sole();
    $month = $credit->created_at->setTimezone('Africa/Kigali')->format('Y-m');
    $line = ['id' => $credit->id, 'business' => 'Synthetic business', 'district' => 'Gasabo',
        'raise' => ['currency' => 'RWF', 'amount' => '10800000'], 'platform_fee' => ['currency' => 'RWF', 'amount' => '324000'],
        'amount' => ['currency' => 'RWF', 'amount' => '129600'], 'credited_at' => $credit->created_at->toIso8601String()];
    $statement = ['month' => $month.'-01', 'this_month' => ['currency' => 'RWF', 'amount' => '129600'],
        'total' => ['currency' => 'RWF', 'amount' => '129600'], 'lines' => [$line]];

    expect(auditorEarnings($auditor, 'auditor.portfolio.index'))->toBe($statement)
        ->and(auditorEarnings($auditor, 'auditor.profile'))->toBe($statement)
        ->and(auditorEarnings(AuditorFixture::make()['user'], 'auditor.portfolio.index'))->toBe(['month' => $month.'-01',
            'this_month' => ['currency' => 'RWF', 'amount' => '0'], 'total' => ['currency' => 'RWF', 'amount' => '0'], 'lines' => []]);
    // Home's "earned this month" is the same month's credit.
    actingAs($auditor)->get(route('auditor.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('earned_this_month', ['currency' => 'RWF', 'amount' => '129600']));

    $this->travelTo(CarbonImmutable::parse($month.'-01 00:00:00', 'Africa/Kigali')->addMonthNoOverflow());
    expect(auditorEarnings($auditor, 'auditor.profile'))->toBe([...$statement, 'month' => now('Africa/Kigali')->format('Y-m').'-01',
        'this_month' => ['currency' => 'RWF', 'amount' => '0'], 'lines' => []]);
    $this->travelTo(now()->subSecond());
    expect(auditorEarnings($auditor, 'auditor.portfolio.index'))->toBe($statement);
});

it('counts a credit made in the first Kigali hours of a month in that month', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-30T22:30:00+00:00'));
    PrimaryIssuanceFixture::close('2026-09-30T22:30:00+00:00');
    $credit = AuditPartnerCredit::query()->sole();
    $auditor = User::query()->where('party_id', $credit->auditor_party_id)->sole();
    $this->travelTo(CarbonImmutable::parse($credit->created_at)->addHour());

    expect($credit->created_at->setTimezone('Africa/Kigali')->format('Y-m-d'))->toBe('2026-10-01')
        ->and(auditorEarnings($auditor, 'auditor.profile')['this_month'])->toBe(['currency' => 'RWF', 'amount' => '129600']);
});
