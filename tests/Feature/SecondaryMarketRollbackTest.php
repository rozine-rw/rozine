<?php

declare(strict_types=1);

use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Models\PrimaryHolding;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * The secondary market's rollback, kept apart from SecondaryMarketTest: rolling the market back needs
 * the later migrations that redefine the ledger's sources (disbursement payouts) and its reservations
 * taken off first, and those refuse once a disbursement has posted, so this starts before any note
 * is issued.
 */
it('rolls back cleanly before any resale activity, and refuses once a listing exists', function (): void {
    $migration = require database_path('migrations/2026_11_11_060000_create_secondary_market_tables.php');
    $disbursementPayouts = require database_path('migrations/2026_11_19_180000_add_disbursement_payout_entries.php');
    $concurrent = require database_path('migrations/2026_11_19_200000_allow_concurrent_secondary_reservations.php');
    $concurrent->down();
    $disbursementPayouts->down();
    $migration->down();

    expect(DB::table('permissions')->where('name', 'secondary.halt')->exists())->toBeFalse()
        ->and(DB::selectOne("SELECT to_regclass('secondary_listings') AS name")->name)->toBeNull();

    $migration->up();
    $disbursementPayouts->up();
    $concurrent->up();
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $seller = User::query()->where('party_id', $holding->party_id)->sole();
    app(EloquentSecondaryMarket::class)->list($seller->id, 1, $holding->id, '10', '2700', (string) Str::uuid());
    PrimaryHoldingFixture::flushDeferredChecks();

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Recorded resale activity requires a forward migration');
});
