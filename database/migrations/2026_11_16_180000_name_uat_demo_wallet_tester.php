<?php

declare(strict_types=1);

use App\Application\Environment\UatMoneySimulation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Names the staging demo's resale buyer (qa-kyc3@rozine.rw, a verified Investor holding no Kivu
 * Harvest notes) a wallet tester with a verified synthetic MoMo method, so the demo can fund its
 * wallet and buy resale units without anyone running `staging:wallet` on the box (Erastus, 16 Nov
 * 2026: no SSH asks). It acts only while the UAT money simulation is on; everywhere else, and where
 * the account does not exist, it does nothing. `staging:wallet` is idempotent, and a revocation
 * later is made the usual way with `--revoke`.
 */
return new class extends Migration
{
    public const string EMAIL = 'qa-kyc3@rozine.rw';

    public function up(): void
    {
        if (! app(UatMoneySimulation::class)->active() || ! DB::table('users')->where('email', self::EMAIL)->whereNotNull('party_id')->exists()) {
            return;
        }
        Artisan::call('staging:wallet', ['email' => self::EMAIL,
            '--reason' => 'Staging demo resale buyer, named in code with the UAT money simulation (16 Nov 2026).']);
    }

    public function down(): void
    {
        // A tester is revoked with `staging:wallet --revoke`, never by rolling back.
    }
};
