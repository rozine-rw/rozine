<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('checks complete closure membership once and validates cash in proportion to purchases', function (int $purchases): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    for ($index = 0; $index < $purchases; $index++) {
        $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($checkout->release($investor['user']->id, 1, $campaign->id, $reserved['data']['reservation_id'], 1, (string) Str::uuid())['code'])
            ->toBe('RESERVATION_RELEASED');
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('CREATE TEMP TABLE closure_validation_calls (kind varchar NOT NULL)');
    foreach (['membership' => 'check_primary_campaign_closure_returns(character varying,boolean)', 'cash' => 'check_primary_closure_return(character varying)'] as $kind => $function) {
        $definition = DB::scalar('SELECT pg_get_functiondef(?::regprocedure)', [$function]);
        if (! is_string($definition)) {
            throw new RuntimeException('Could not read the retained closure validator.');
        }
        $instrumented = preg_replace('/BEGIN/', "BEGIN INSERT INTO closure_validation_calls VALUES ('".$kind."');", $definition, 1);
        if (! is_string($instrumented)) {
            throw new RuntimeException('Could not instrument the retained closure validator.');
        }
        DB::connection()->getPdo()->exec($instrumented);
    }
    $cash = LedgerLine::query()->orderBy('id')->get()->toJson();
    $result = app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, null, (string) Str::uuid());
    expect($result['code'])->toBe('CAMPAIGN_CANCELLED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(DB::table('closure_validation_calls')->where('kind', 'membership')->count())->toBe(1)
        ->and(DB::table('closure_validation_calls')->where('kind', 'cash')->count())->toBe(2 * $purchases)
        ->and(DB::table('primary_campaign_closure_returns')->count())->toBe($purchases)
        ->and(LedgerLine::query()->orderBy('id')->get()->toJson())->toBe($cash);
})->with([1, 8, 32]);

it('indexes original entry cash lookups and reverses without modifying retained lines', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    PrimaryReservationFixture::investor();
    $entry = LedgerEntry::query()->sole();
    $lines = LedgerLine::query()->orderBy('id')->get()->toJson();
    expect(Schema::hasIndex('ledger_lines', ['entry_id']))->toBeTrue();
    DB::statement('SET LOCAL enable_seqscan = off');
    $plan = DB::select('EXPLAIN (FORMAT JSON) SELECT * FROM ledger_lines WHERE entry_id = ?', [$entry->id]);
    expect(json_encode($plan, JSON_THROW_ON_ERROR))->toContain('ledger_lines_entry_id_index');
    $migration = require database_path('migrations/2026_09_30_171842_index_wallet_ledger_lines_by_entry.php');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->down();
    expect(Schema::hasIndex('ledger_lines', ['entry_id']))->toBeFalse();
    $migration->up();
    expect(Schema::hasIndex('ledger_lines', ['entry_id']))->toBeTrue()
        ->and(LedgerLine::query()->orderBy('id')->get()->toJson())->toBe($lines);
});
