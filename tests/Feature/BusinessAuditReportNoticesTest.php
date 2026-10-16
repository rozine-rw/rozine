<?php

declare(strict_types=1);

use App\Models\AuditReportPublication;
use App\Models\BusinessProfile;
use App\Models\PartyNotice;
use Database\Factories\Scenarios\AuditSealingFixture as Fixture;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/*
 * The Business's audit-report notices (BRS v2.0 NTF-R01): a sealed report awaiting its approval,
 * and the 24-hour review window closing, each held by the Business's entity Party.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    Cache::forget('monthly-audit-review-sweep-cursor');
});

it('tells the Business a sealed monthly report awaits its approval, with the window\'s close', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $business = BusinessProfile::query()->whereKey($fixture['audit']['business'])->sole();
    $publication = AuditReportPublication::query()->sole();

    $notice = PartyNotice::query()->where('kind', 'business.report_sealed')->sole();
    expect([$notice->party_id, $notice->event_key])->toBe([$business->entity_party_id, 'business-report-sealed:'.$publication->id])
        ->and($notice->facts)->toMatchArray(['auditor' => $fixture['user']->name, 'due_at' => now('UTC')->addHours(24)->format('Y-m-d\TH:i:s\Z'),
            'business_id' => $business->id, 'report_id' => $fixture['report']->id])
        ->and($notice->facts['period'])->toBeString();

    $this->actingAs($fixture['audit']['authority']['users'][0])->getJson(route('business.notices.index', $business->id))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'business.report_sealed')
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$business->id}/audit-reports/{$fixture['report']->id}", 'method' => 'get']);
});

it('tells the Business a sealed flash report awaits its approval, with no window to state', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);

    expect(PartyNotice::query()->where('kind', 'business.report_sealed')->sole()->facts)
        ->toMatchArray(['period' => null, 'due_at' => null, 'report_id' => $fixture['report']->id]);
});

it('reminds the Business once as its monthly review window closes, and never after it co-signs', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->sole();
    $closing = fn () => PartyNotice::query()->where('kind', 'business.report_review_closing');

    $this->travel(21)->hours();
    expect(Artisan::call('audits:advance-reviews'))->toBe(0)->and(Artisan::output())->toContain('Reminded 0 closing reviews')
        ->and($closing()->count())->toBe(0);

    $this->travel(90)->minutes();
    Artisan::call('audits:advance-reviews');
    Artisan::call('audits:advance-reviews');
    expect(Artisan::output())->toContain('Reminded 1 closing reviews');
    $notice = $closing()->sole();
    expect([$notice->event_key, $notice->facts])->toEqual(['business-report-review-closing:'.$publication->id,
        ['due_at' => $publication->due_at->utc()->format('Y-m-d\TH:i:s\Z'), 'business_id' => $fixture['audit']['business'], 'report_id' => $fixture['report']->id]]);

    $second = Fixture::ready(kind: 'monthly');
    Fixture::seal($second);
    Fixture::cosign($second);
    $this->travel(23)->hours();
    Artisan::call('audits:advance-reviews');
    expect($closing()->count())->toBe(1);
});
