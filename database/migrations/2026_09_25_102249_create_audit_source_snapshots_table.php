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
        Schema::create('audit_source_snapshots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('assignment_id');
            $table->ulid('business_id');
            $table->unsignedInteger('assignment_revision');
            $table->foreignUlid('party_id')->constrained('parties')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('status', 20);
            $table->string('source_kind', 40);
            $table->text('source_reference');
            $table->string('procedure_version', 80);
            $table->text('facts')->nullable();
            $table->char('sha256', 64);
            $table->unsignedBigInteger('actor_user_id');
            $table->text('reason');
            $table->timestampTz('created_at');
            $table->unique(['assignment_id', 'assignment_revision', 'revision'], 'audit_source_snapshot_binding_revision');
            $table->foreign(['assignment_id', 'business_id'], 'audit_source_snapshot_assignment_business')
                ->references(['id', 'business_id'])->on('audit_assignments')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_source_snapshots ADD CONSTRAINT audit_source_snapshot_state
                CHECK (revision > 0 AND assignment_revision > 0 AND source_kind = 'isolated_synthetic'
                    AND ((status = 'available' AND facts IS NOT NULL) OR (status = 'withdrawn' AND facts IS NULL)));
            CREATE OR REPLACE FUNCTION validate_audit_source_snapshot_binding() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE
                assignment audit_assignments%ROWTYPE;
                latest integer;
            BEGIN
                SELECT * INTO assignment FROM audit_assignments WHERE id = NEW.assignment_id FOR SHARE;
                IF NOT FOUND OR assignment.status <> 'accepted' OR assignment.revision <> NEW.assignment_revision
                    OR assignment.party_id IS DISTINCT FROM NEW.party_id OR assignment.business_id <> NEW.business_id THEN
                    RAISE EXCEPTION 'Audit source facts must bind the current accepted Auditor' USING ERRCODE = '23514';
                END IF;
                SELECT max(revision) INTO latest FROM audit_source_snapshots
                    WHERE assignment_id = NEW.assignment_id AND assignment_revision = NEW.assignment_revision;
                IF NEW.revision <> COALESCE(latest, 0) + 1 THEN
                    RAISE EXCEPTION 'Audit source revision must advance once' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER audit_source_snapshots_binding BEFORE INSERT ON audit_source_snapshots
                FOR EACH ROW EXECUTE FUNCTION validate_audit_source_snapshot_binding();
            CREATE OR REPLACE FUNCTION reject_audit_source_snapshot_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Audit source snapshots are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER audit_source_snapshots_immutable BEFORE UPDATE OR DELETE ON audit_source_snapshots
                FOR EACH ROW EXECUTE FUNCTION reject_audit_source_snapshot_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_source_snapshots IN ACCESS EXCLUSIVE MODE');
            if (DB::table('audit_source_snapshots')->exists()) {
                throw new RuntimeException('Existing audit source history requires a forward migration.');
            }
            Schema::dropIfExists('audit_source_snapshots');
            DB::unprepared('DROP FUNCTION IF EXISTS reject_audit_source_snapshot_mutation(); DROP FUNCTION IF EXISTS validate_audit_source_snapshot_binding()');
        });
    }
};
