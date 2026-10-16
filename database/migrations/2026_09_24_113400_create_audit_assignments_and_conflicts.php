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
        Schema::create('audit_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->foreignUlid('party_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 30);
            $table->text('state');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('accept_by')->nullable()->index();
            $table->timestampsTz();
            $table->index(['party_id', 'status']);
        });
        Schema::create('audit_assignment_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('assignment_id')->constrained('audit_assignments')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignUlid('party_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 30);
            $table->text('snapshot');
            $table->text('selection_basis');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('command', 80);
            $table->text('reason')->nullable();
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['assignment_id', 'revision']);
            $table->index(['party_id', 'status']);
        });
        Schema::create('audit_conflict_declarations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('assignment_id')->constrained('audit_assignments')->restrictOnDelete();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->foreignUlid('party_id')->constrained()->restrictOnDelete();
            $table->text('kind');
            $table->text('reason');
            $table->unsignedBigInteger('actor_user_id');
            $table->string('policy_version', 80);
            $table->timestampTz('created_at');
            $table->unique(['assignment_id', 'party_id']);
            $table->index(['business_id', 'party_id']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_revision CHECK (revision > 0);
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_status CHECK (status IN ('offered', 'accepted', 'operations', 'completed'));
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_recipient CHECK ((status = 'operations' AND party_id IS NULL) OR (status <> 'operations' AND party_id IS NOT NULL));
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_completion CHECK ((status = 'completed') = (completed_at IS NOT NULL));
            ALTER TABLE audit_assignment_versions ADD CONSTRAINT audit_assignment_version_revision CHECK (revision > 0);
            CREATE UNIQUE INDEX audit_assignment_open_business ON audit_assignments (business_id) WHERE status IN ('offered', 'accepted', 'operations');
            CREATE OR REPLACE FUNCTION reject_assignment_history_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit assignment and conflict history is immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_assignment_versions_immutable BEFORE UPDATE OR DELETE ON audit_assignment_versions FOR EACH ROW EXECUTE FUNCTION reject_assignment_history_mutation();
            CREATE TRIGGER audit_conflict_declarations_immutable BEFORE UPDATE OR DELETE ON audit_conflict_declarations FOR EACH ROW EXECUTE FUNCTION reject_assignment_history_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_conflict_declarations');
        Schema::dropIfExists('audit_assignment_versions');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_assignment_history_mutation()');
        Schema::dropIfExists('audit_assignments');
    }
};
