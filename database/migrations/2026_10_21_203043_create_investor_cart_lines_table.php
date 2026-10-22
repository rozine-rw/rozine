<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Investor's cart (BRS v2.0 INV-R02): the notes a Party has picked from Deals, one line per
 * campaign with the whole number of RWF 5,000 units it wants there. It is a convenience list, not
 * a financial record: lines change and go, nothing is reserved or posted, and the checkout still
 * decides every purchase under its own locks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_cart_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUlid('business_campaign_id')->constrained('business_campaigns')->restrictOnDelete();
            $table->integer('units');
            $table->timestampTz('created_at', 6);
            $table->timestampTz('updated_at', 6);
            $table->unique(['party_id', 'business_campaign_id'], 'investor_cart_line_once');
        });
        DB::unprepared('ALTER TABLE investor_cart_lines ADD CONSTRAINT investor_cart_line_units CHECK (units BETWEEN 1 AND 99999)');
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_cart_lines');
    }
};
