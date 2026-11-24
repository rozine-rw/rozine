<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A verified person's request for Business or Auditor access, sent from the launcher (#616). Staff
 * act on it with the tools that grant the role: Onboard business, or Auditor accreditation. A Party
 * sends at most one request per role, and a row is never edited or removed once written. Staff read
 * who sent it from the Party's account, so deleting that account leaves the request in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_access_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->string('role', 16);
            $table->string('organization', 160);
            $table->string('reference', 60)->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('note')->nullable();
            $table->ulid('operation_id')->unique();
            $table->timestampTz('created_at', 6);
            $table->unique(['party_id', 'role']);
            $table->index('created_at');
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE role_access_requests ADD CONSTRAINT role_access_request_role CHECK (role IN ('business', 'auditor'));
            CREATE OR REPLACE FUNCTION reject_role_access_request_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Role access requests are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER role_access_requests_immutable
            BEFORE UPDATE OR DELETE ON role_access_requests
            FOR EACH ROW EXECUTE FUNCTION reject_role_access_request_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_access_requests');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_role_access_request_mutation()');
    }
};
