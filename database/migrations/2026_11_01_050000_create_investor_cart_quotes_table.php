<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Whole-cart quotes (#383 step 3b, contract cart-checkout-1 §5): the terms the server offered for
 * the entire saved cart at one cart revision and snapshot hash, with every line, the totals, the
 * common INV-R04 band and the held-capital revision it was priced from, encrypted at rest as the
 * reservation payloads are. A quote holds no units or cash. It is append-only: a new quote is a new row, and nothing is updated or deleted, so a later
 * reserve can authenticate exactly what the Investor was shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_cart_quotes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUlid('investor_cart_id')->constrained('investor_carts')->restrictOnDelete();
            $table->bigInteger('cart_revision');
            $table->char('cart_sha256', 64);
            $table->string('contract_version', 40);
            $table->text('payload');
            $table->char('disclosure_sha256', 64);
            $table->char('sha256', 64);
            $table->timestampTz('created_at', 6);
            $table->timestampTz('expires_at', 6);
            $table->index(['party_id', 'created_at'], 'investor_cart_quotes_by_party');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_cart_quotes
                ADD CONSTRAINT investor_cart_quote_revision CHECK (cart_revision >= 1),
                ADD CONSTRAINT investor_cart_quote_digests CHECK (cart_sha256 ~ '^[0-9a-f]{64}$'
                    AND disclosure_sha256 ~ '^[0-9a-f]{64}$' AND sha256 ~ '^[0-9a-f]{64}$'),
                ADD CONSTRAINT investor_cart_quote_window CHECK (expires_at > created_at);
            CREATE OR REPLACE FUNCTION reject_investor_cart_quote_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Investor cart quotes are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER investor_cart_quotes_immutable
            BEFORE UPDATE OR DELETE ON investor_cart_quotes
            FOR EACH ROW EXECUTE FUNCTION reject_investor_cart_quote_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_cart_quotes');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_investor_cart_quote_mutation()');
    }
};
