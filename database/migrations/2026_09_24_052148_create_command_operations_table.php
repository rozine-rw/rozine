<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_operations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('actor_key', 80);
            $table->unsignedBigInteger('actor_user_id');
            $table->string('command', 100);
            $table->uuid('request_id');
            $table->char('request_hash', 64);
            $table->string('target_type', 80);
            $table->string('target_id');
            $table->jsonb('result');
            $table->timestampTz('retain_until');
            $table->timestampTz('created_at');
            $table->unique(['actor_key', 'command', 'request_id']);
            $table->index(['target_type', 'target_id']);
        });
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_command_operation_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Command outcomes are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER command_operations_immutable
            BEFORE UPDATE OR DELETE ON command_operations
            FOR EACH ROW EXECUTE FUNCTION reject_command_operation_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('command_operations');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_command_operation_mutation()');
    }
};
