<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The whole-cart checkout aggregate (#383 step 4, contract cart-checkout-1 §§5, 8). One reserve
 * holds every cart line at once as ordinary primary reservations (the children), under one
 * `cart.reserve` operation (the parent). Each child keeps its own `primary.reserve` receipt, so
 * every existing reservation, receipt and cash constraint still binds it exactly as before.
 *
 * - `investor_cart_checkouts`: the immutable root. It names the cart, the quote the Investor
 *   accepted, the parent operation, the shared confirmation deadline (the earliest child window,
 *   P383-04) and when every child window has closed, which is when expiry can settle.
 * - `investor_cart_checkout_versions`: the append-only state (held, confirmed, released, expired).
 * - `investor_cart_checkout_lines`: one immutable row per child, binding the cart line, campaign,
 *   reservation and the child's own receipt. A reservation belongs to at most one checkout.
 * - `investor_cart_live_checkouts`: at most one held checkout per Party (P383-01). The row goes
 *   when the checkout reaches a terminal state.
 *
 * Deferred constraint triggers check, at commit, that the parent receipt is the Party's completed
 * `cart.reserve` for this cart and checkout, that each child is the Party's reservation in that
 * campaign whose origin receipt names the parent, and that a checkout has lines and a first held
 * version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_cart_checkouts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignUlid('investor_cart_id')->constrained('investor_carts')->restrictOnDelete();
            $table->foreignUlid('investor_cart_quote_id')->constrained('investor_cart_quotes')->restrictOnDelete();
            $table->bigInteger('cart_revision');
            $table->ulid('origin_operation_id')->unique();
            $table->string('principal', 64);
            $table->timestampTz('created_at', 6);
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('settles_at', 6);
            $table->index(['settles_at', 'id'], 'investor_cart_checkout_settlement');
        });
        Schema::create('investor_cart_checkout_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_cart_checkout_id')->constrained('investor_cart_checkouts')->restrictOnDelete();
            $table->integer('revision');
            $table->string('state', 20);
            $table->foreignUlid('investor_cart_quote_id')->constrained('investor_cart_quotes')->restrictOnDelete();
            $table->ulid('operation_id')->nullable();
            $table->timestampTz('created_at', 6);
            $table->unique(['investor_cart_checkout_id', 'revision'], 'investor_cart_checkout_revision');
        });
        Schema::create('investor_cart_checkout_lines', function (Blueprint $table): void {
            $table->foreignUlid('investor_cart_checkout_id')->constrained('investor_cart_checkouts')->restrictOnDelete();
            $table->ulid('investor_cart_line_id');
            $table->foreignUlid('business_campaign_id')->constrained('business_campaigns')->restrictOnDelete();
            $table->foreignUlid('primary_reservation_id')->unique()->constrained('primary_reservations')->restrictOnDelete();
            $table->ulid('child_operation_id')->unique();
            $table->integer('position');
            $table->primary(['investor_cart_checkout_id', 'business_campaign_id']);
            $table->unique(['investor_cart_checkout_id', 'position'], 'investor_cart_checkout_line_position');
        });
        Schema::create('investor_cart_live_checkouts', function (Blueprint $table): void {
            $table->foreignUlid('party_id')->primary()->constrained('parties')->restrictOnDelete();
            $table->foreignUlid('investor_cart_checkout_id')->unique()->constrained('investor_cart_checkouts')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_cart_checkouts
                ADD CONSTRAINT investor_cart_checkout_cart_revision CHECK (cart_revision >= 1),
                ADD CONSTRAINT investor_cart_checkout_principal CHECK (principal ~ '^[1-9][0-9]*$'),
                ADD CONSTRAINT investor_cart_checkout_window CHECK (expires_at > created_at AND settles_at >= expires_at);
            ALTER TABLE investor_cart_checkout_versions
                ADD CONSTRAINT investor_cart_checkout_version_revision CHECK (revision >= 1),
                ADD CONSTRAINT investor_cart_checkout_version_state CHECK (state IN ('held', 'confirmed', 'released', 'expired')),
                ADD CONSTRAINT investor_cart_checkout_version_operation CHECK (operation_id IS NOT NULL OR state = 'expired');
            ALTER TABLE investor_cart_checkout_lines
                ADD CONSTRAINT investor_cart_checkout_line_position_range CHECK (position >= 1);

            CREATE OR REPLACE FUNCTION reject_investor_cart_checkout_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Investor cart checkout evidence is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER investor_cart_checkouts_immutable BEFORE UPDATE OR DELETE ON investor_cart_checkouts
                FOR EACH ROW EXECUTE FUNCTION reject_investor_cart_checkout_mutation();
            CREATE TRIGGER investor_cart_checkout_versions_immutable BEFORE UPDATE OR DELETE ON investor_cart_checkout_versions
                FOR EACH ROW EXECUTE FUNCTION reject_investor_cart_checkout_mutation();
            CREATE TRIGGER investor_cart_checkout_lines_immutable BEFORE UPDATE OR DELETE ON investor_cart_checkout_lines
                FOR EACH ROW EXECUTE FUNCTION reject_investor_cart_checkout_mutation();

            CREATE OR REPLACE FUNCTION require_investor_cart_checkout_binding() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM command_operations o JOIN users u ON u.id = o.actor_user_id
                    WHERE o.id = NEW.origin_operation_id AND o.actor_key = 'party:' || NEW.party_id AND u.party_id = NEW.party_id
                      AND o.command = 'cart.reserve' AND o.target_type = 'investor_cart' AND o.target_id = NEW.investor_cart_id
                      AND o.result @> jsonb_build_object('status', 'completed', 'code', 'CART_RESERVED',
                          'data', jsonb_build_object('checkout_id', NEW.id))) THEN
                    RAISE EXCEPTION 'A cart checkout requires its Party''s completed cart.reserve receipt' USING ERRCODE = '23514';
                END IF;
                IF NOT EXISTS (SELECT 1 FROM investor_carts c WHERE c.id = NEW.investor_cart_id AND c.party_id = NEW.party_id)
                    OR NOT EXISTS (SELECT 1 FROM investor_cart_quotes q WHERE q.id = NEW.investor_cart_quote_id AND q.party_id = NEW.party_id
                        AND q.investor_cart_id = NEW.investor_cart_id AND q.cart_revision = NEW.cart_revision) THEN
                    RAISE EXCEPTION 'A cart checkout must bind its Party''s cart and quote' USING ERRCODE = '23514';
                END IF;
                IF NOT EXISTS (SELECT 1 FROM investor_cart_checkout_lines l WHERE l.investor_cart_checkout_id = NEW.id)
                    OR NOT EXISTS (SELECT 1 FROM investor_cart_checkout_versions v WHERE v.investor_cart_checkout_id = NEW.id
                        AND v.revision = 1 AND v.state = 'held' AND v.operation_id = NEW.origin_operation_id
                        AND v.investor_cart_quote_id = NEW.investor_cart_quote_id) THEN
                    RAISE EXCEPTION 'A cart checkout requires its lines and first held version' USING ERRCODE = '23514';
                END IF;
                IF (SELECT sum(r.principal::numeric) FROM investor_cart_checkout_lines l
                        JOIN primary_reservations r ON r.id = l.primary_reservation_id
                        WHERE l.investor_cart_checkout_id = NEW.id) IS DISTINCT FROM NEW.principal::numeric
                    OR (SELECT min(r.expires_at) FROM investor_cart_checkout_lines l
                        JOIN primary_reservations r ON r.id = l.primary_reservation_id
                        WHERE l.investor_cart_checkout_id = NEW.id) IS DISTINCT FROM NEW.expires_at
                    OR (SELECT max(r.expires_at) FROM investor_cart_checkout_lines l
                        JOIN primary_reservations r ON r.id = l.primary_reservation_id
                        WHERE l.investor_cart_checkout_id = NEW.id) IS DISTINCT FROM NEW.settles_at THEN
                    RAISE EXCEPTION 'A cart checkout''s total and deadlines must match its children' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER investor_cart_checkout_bound AFTER INSERT ON investor_cart_checkouts
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_investor_cart_checkout_binding();

            CREATE OR REPLACE FUNCTION require_investor_cart_checkout_child() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE checkout investor_cart_checkouts%ROWTYPE;
            BEGIN
                SELECT * INTO checkout FROM investor_cart_checkouts WHERE id = NEW.investor_cart_checkout_id;
                IF NOT EXISTS (SELECT 1 FROM primary_reservations r
                        JOIN command_operations o ON o.id = r.origin_operation_id
                        WHERE r.id = NEW.primary_reservation_id AND r.party_id = checkout.party_id
                          AND r.business_campaign_id = NEW.business_campaign_id AND r.origin_operation_id = NEW.child_operation_id
                          AND o.command = 'primary.reserve' AND o.actor_key = 'party:' || checkout.party_id
                          AND o.target_type = 'campaign' AND o.target_id = NEW.business_campaign_id
                          AND o.result @> jsonb_build_object('status', 'completed', 'code', 'RESERVATION_HELD',
                              'data', jsonb_build_object('reservation_id', NEW.primary_reservation_id,
                                  'cart_operation_id', checkout.origin_operation_id))) THEN
                    RAISE EXCEPTION 'A cart checkout child must be the Party''s reservation under the parent receipt' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER investor_cart_checkout_child_bound AFTER INSERT ON investor_cart_checkout_lines
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_investor_cart_checkout_child();

            CREATE OR REPLACE FUNCTION require_investor_cart_live_checkout() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM investor_cart_checkouts c WHERE c.id = NEW.investor_cart_checkout_id AND c.party_id = NEW.party_id)
                    OR (SELECT v.state FROM investor_cart_checkout_versions v WHERE v.investor_cart_checkout_id = NEW.investor_cart_checkout_id
                        ORDER BY v.revision DESC LIMIT 1) IS DISTINCT FROM 'held' THEN
                    RAISE EXCEPTION 'Only the Party''s held cart checkout can be live' USING ERRCODE = '23514';
                END IF;
                RETURN NULL;
            END;
            $$;
            CREATE CONSTRAINT TRIGGER investor_cart_live_checkout_bound AFTER INSERT ON investor_cart_live_checkouts
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_investor_cart_live_checkout();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE investor_cart_checkouts IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM investor_cart_checkouts) THEN
                        RAISE EXCEPTION 'Retained cart checkouts require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                SQL);
            Schema::drop('investor_cart_live_checkouts');
            Schema::drop('investor_cart_checkout_lines');
            Schema::drop('investor_cart_checkout_versions');
            Schema::drop('investor_cart_checkouts');
            DB::unprepared('DROP FUNCTION reject_investor_cart_checkout_mutation(), require_investor_cart_checkout_binding(),
                require_investor_cart_checkout_child(), require_investor_cart_live_checkout()');
        });
    }
};
