<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Infrastructure\Primary\EloquentInvestorCart;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The root of each Investor's cart (#383 step 3a, contract cart-checkout-1 §6): one row per Party,
 * created by the cart's first change, with a revision that rises once per real change to its lines
 * and the sha256 of the canonical line snapshot it describes. A whole-cart quote and checkout bind
 * to this revision and hash, so a change after the quote is detected rather than silently bought.
 * A cart that already has lines gets its root here, at revision 1 with the hash of those lines, so
 * every non-empty cart can be quoted without a read creating its root.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_carts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique('investor_cart_once')->constrained('parties')->restrictOnDelete();
            $table->bigInteger('revision');
            $table->char('sha256', 64);
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);
        });
        DB::unprepared("ALTER TABLE investor_carts ADD CONSTRAINT investor_cart_revision CHECK (revision >= 1),
            ADD CONSTRAINT investor_cart_sha256 CHECK (sha256 ~ '^[0-9a-f]{64}$')");
        // The migration runs in one transaction; holding the lines in SHARE mode until it commits keeps
        // any cart writer out between the backfill and the root table going live.
        DB::unprepared('LOCK TABLE investor_cart_lines IN SHARE MODE');
        $json = app(CanonicalJson::class);
        $at = now('UTC')->format('Y-m-d H:i:s.u');
        foreach (DB::table('investor_cart_lines')->orderBy('party_id')->orderBy('created_at')->orderBy('id')->get()->groupBy('party_id') as $partyId => $rows) {
            $lines = $rows->map(fn (object $row): array => ['campaign_id' => (string) $row->business_campaign_id, 'units' => (int) $row->units])->values()->all();
            DB::table('investor_carts')->insert(['id' => strtolower((string) Str::ulid()), 'party_id' => $partyId, 'revision' => 1,
                'sha256' => EloquentInvestorCart::snapshotSha256($json, $lines), 'created_at' => $at, 'updated_at' => $at]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_carts');
    }
};
