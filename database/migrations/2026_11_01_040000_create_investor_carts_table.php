<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The root of each Investor's cart (#383 step 3a, contract cart-checkout-1 §6): one row per Party,
 * created by the cart's first change, with a revision that rises once per real change to its lines
 * and the sha256 of the canonical line snapshot it describes. A whole-cart quote and checkout bind
 * to this revision and hash, so a change after the quote is detected rather than silently bought.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_carts');
    }
};
