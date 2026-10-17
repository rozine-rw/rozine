<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The devices each account has signed in from (BRS v2.0 NTF-R01, Security: new device). A device
 * is known by the hash of a random token held in its browser cookie; the token itself is never
 * stored. A device is recognised, never edited; its rows go only with the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('label', 200);
            $table->timestampTz('created_at', 6);
            $table->index('user_id');
        });
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_account_device_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Account devices are immutable once recorded' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER account_devices_immutable
            BEFORE UPDATE ON account_devices
            FOR EACH ROW EXECUTE FUNCTION reject_account_device_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_devices');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_account_device_mutation();');
    }
};
