<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryFunding;
use App\Models\BusinessCampaign;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('refuses funding a retained earlier issued closing so creation order cannot skip completeness', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed(fund: false);
    PrimaryHoldingFixture::issuedClosing($campaign);
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(fn () => DB::transaction(function () use ($campaign): void {
        app(PrimaryFunding::class)->lock($campaign->id, fn (): array => PrimaryHoldingFixture::admission($campaign));
        DB::statement('SET CONSTRAINTS primary_funded_closing_complete IMMEDIATE');
    }))->toThrow(QueryException::class, 'An issued closing must issue a Holding for every funded commitment');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(DB::table('primary_campaign_fundings')->count())->toBe(0)
        ->and(DB::table('disbursement_closings')->count())->toBe(1);
});

it('refuses zero Holdings even when every original principal has an issue posting', function (bool $postCash): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    expect(fn () => DB::transaction(function () use ($campaign, $commitments, $postCash): void {
        $closing = PrimaryHoldingFixture::issuedClosing($campaign);
        if ($postCash) {
            foreach ($commitments as $commitment) {
                PrimaryHoldingFixture::issue($commitment->id, $closing);
            }
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'An issued closing must issue a Holding for every funded commitment');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(DB::table('disbursement_closings')->count())->toBe(0)
        ->and(DB::table('primary_holdings')->count())->toBe(0)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(0);
})->with([false, true]);

it('accepts the complete exact Holding and cash set in either order at outer commit', function (bool $holdingsFirst): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['1080', '600', '480']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $holdings = function () use ($commitments, $closing): void {
        foreach ($commitments as $commitment) {
            PrimaryHoldingFixture::insert($commitment->id, $closing);
        }
    };
    $postings = function () use ($commitments, $closing): void {
        foreach ($commitments as $commitment) {
            PrimaryHoldingFixture::issue($commitment->id, $closing);
        }
    };
    ($holdingsFirst ? $holdings : $postings)();
    ($holdingsFirst ? $postings : $holdings)();
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(DB::table('primary_holdings')->where('disbursement_closing_id', $closing)->count())->toBe(3)
        ->and(DB::table('primary_holdings')->sum('principal'))->toEqual($campaign->principal);
})->with([false, true]);

it('keeps failed closings and campaigns without retained Primary funding outside this guard', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $failed = PrimaryHoldingFixture::issuedClosing($campaign, kind: 'failed_closing');
    $unfunded = PrimaryHoldingFixture::issuedClosing(BusinessCampaign::factory()->create());
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(DB::table('disbursement_closings')->where('id', $failed)->value('kind'))->toBe('failed_closing')
        ->and(DB::table('disbursement_closings')->where('id', $unfunded)->value('kind'))->toBe('issued');
});

it('audits retained zero-Holding closings without repair and restores its exact empty schema', function (): void {
    $migration = require database_path('migrations/2026_09_30_234802_require_complete_primary_holdings_for_issued_closings.php');
    $shape = fn (): array => [
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE tgrelid = 'disbursement_closings'::regclass AND NOT tgisinternal ORDER BY tgname"),
        DB::select("SELECT proname, pg_get_functiondef(oid) AS definition FROM pg_proc WHERE proname IN ('check_primary_issued_closing_complete', 'require_primary_issued_closing_complete', 'require_primary_funded_closing_complete') ORDER BY proname")];
    $installed = $shape();
    $migration->down();
    $removed = $shape();
    expect(array_column($removed[0], 'tgname'))->not->toContain('primary_issued_closing_complete')->and($removed[1])->toBe([]);
    $migration->up();
    expect($shape())->toEqual($installed);
    $migration->down();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    PrimaryHoldingFixture::flushDeferredChecks();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'An issued closing must issue a Holding for every funded commitment');
    expect($shape())->toEqual($removed)->and(DB::table('disbursement_closings')->where('id', $closing)->exists())->toBeTrue()
        ->and(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(0);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    $migration->up();
    expect($shape())->toEqual($installed);
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Issued Primary closings require a forward migration');
    expect($shape())->toEqual($installed)->and(DB::table('primary_holdings')->count())->toBe(2);
});
