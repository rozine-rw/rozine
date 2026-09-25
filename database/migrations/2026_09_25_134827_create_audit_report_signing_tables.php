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
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_reports IN SHARE ROW EXCLUSIVE MODE');
            if (DB::table('audit_reports')->where('status', 'sealed')->exists()) {
                throw new RuntimeException('Existing sealed reports require verified signing history before protection.');
            }
            Schema::create('audit_step_up_proofs', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignUlid('actor_party_id')->constrained('parties')->restrictOnDelete();
                $table->foreignUlid('audit_report_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('identity_context_revision');
                $table->unsignedInteger('report_revision');
                $table->char('digest', 64);
                $table->char('credential_binding', 64);
                $table->char('proof_sha256', 64)->unique();
                $table->timestampTz('expires_at');
                $table->timestampTz('consumed_at')->nullable();
                $table->timestampTz('created_at');
            });
            Schema::create('audit_signing_keys', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->text('private_jwk');
                $table->json('public_jwk');
                $table->timestampTz('valid_from');
                $table->timestampTz('rotate_at');
                $table->timestampTz('created_at');
            });
            Schema::create('audit_signing_key_revocations', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('audit_signing_key_id')->unique()->constrained()->restrictOnDelete();
                $table->text('reason');
                $table->timestampTz('created_at');
            });
            Schema::create('audit_report_seals', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('audit_report_id')->unique()->constrained()->restrictOnDelete();
                $table->unsignedInteger('report_revision');
                $table->foreignUlid('audit_signing_key_id')->constrained()->restrictOnDelete();
                $table->foreignUlid('author_party_id')->constrained('parties')->restrictOnDelete();
                $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
                $table->char('digest', 64);
                $table->text('payload');
                $table->text('jws');
                $table->timestampTz('created_at');
            });
            DB::unprepared(<<<'SQL'
                ALTER TABLE audit_signing_keys ADD CONSTRAINT audit_signing_key_rotation
                    CHECK (rotate_at > valid_from AND rotate_at <= valid_from + interval '90 days');
                ALTER TABLE audit_step_up_proofs ADD CONSTRAINT audit_step_up_duration
                    CHECK (expires_at > created_at AND expires_at <= created_at + interval '5 minutes');
                CREATE OR REPLACE FUNCTION protect_audit_step_up() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF ROW(NEW.id, NEW.actor_user_id, NEW.actor_party_id, NEW.audit_report_id, NEW.identity_context_revision,
                        NEW.report_revision, NEW.digest, NEW.credential_binding, NEW.proof_sha256, NEW.expires_at, NEW.created_at)
                        IS DISTINCT FROM ROW(OLD.id, OLD.actor_user_id, OLD.actor_party_id, OLD.audit_report_id, OLD.identity_context_revision,
                        OLD.report_revision, OLD.digest, OLD.credential_binding, OLD.proof_sha256, OLD.expires_at, OLD.created_at)
                        OR OLD.consumed_at IS NOT NULL OR NEW.consumed_at IS NULL
                        OR NEW.consumed_at < NEW.created_at OR NEW.consumed_at >= NEW.expires_at THEN
                        RAISE EXCEPTION 'Audit step-up binding is immutable and single use' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_step_up_protected BEFORE UPDATE ON audit_step_up_proofs
                    FOR EACH ROW EXECUTE FUNCTION protect_audit_step_up();
                CREATE OR REPLACE FUNCTION reject_audit_signing_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    RAISE EXCEPTION 'Audit signing keys, revocations and seals are immutable' USING ERRCODE = '23514';
                END;
                $$;
                CREATE TRIGGER audit_signing_keys_immutable BEFORE UPDATE OR DELETE ON audit_signing_keys
                    FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
                CREATE TRIGGER audit_signing_key_revocations_immutable BEFORE UPDATE OR DELETE ON audit_signing_key_revocations
                    FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
                CREATE TRIGGER audit_report_seals_immutable BEFORE UPDATE OR DELETE ON audit_report_seals
                    FOR EACH ROW EXECUTE FUNCTION reject_audit_signing_mutation();
                CREATE OR REPLACE FUNCTION serialize_audit_key_revocation() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    PERFORM id FROM audit_signing_keys WHERE id = NEW.audit_signing_key_id FOR UPDATE;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_key_revocation_lock BEFORE INSERT ON audit_signing_key_revocations
                    FOR EACH ROW EXECUTE FUNCTION serialize_audit_key_revocation();
                CREATE OR REPLACE FUNCTION validate_audit_seal_authority() RETURNS trigger LANGUAGE plpgsql AS $$
                DECLARE report audit_reports%ROWTYPE;
                BEGIN
                    SELECT * INTO report FROM audit_reports WHERE id = NEW.audit_report_id FOR SHARE;
                    IF NOT FOUND OR report.author_party_id IS DISTINCT FROM NEW.author_party_id OR report.status <> 'draft'
                        OR report.step <> 'seal' OR NEW.report_revision <> report.revision + 1 THEN
                        RAISE EXCEPTION 'Audit seal requires its current author and complete draft revision' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER audit_report_seal_authority BEFORE INSERT ON audit_report_seals
                    FOR EACH ROW EXECUTE FUNCTION validate_audit_seal_authority();
                CREATE OR REPLACE FUNCTION require_audit_report_seal() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NEW.status = 'sealed' AND NOT EXISTS (SELECT 1 FROM audit_report_seals
                        WHERE audit_report_id = NEW.id AND report_revision = NEW.revision AND author_party_id = NEW.author_party_id) THEN
                        RAISE EXCEPTION 'A sealed report requires retained signing history' USING ERRCODE = '23514';
                    END IF;
                    RETURN NEW;
                END;
                $$;
                CREATE TRIGGER zzzz_audit_report_seal_required BEFORE INSERT OR UPDATE ON audit_reports
                    FOR EACH ROW EXECUTE FUNCTION require_audit_report_seal();
                CREATE OR REPLACE FUNCTION require_audit_seal_version() RETURNS trigger LANGUAGE plpgsql AS $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM audit_report_versions AS version JOIN audit_reports AS report
                        ON report.id = version.audit_report_id WHERE report.id = NEW.audit_report_id
                        AND report.revision = NEW.report_revision AND report.status = 'sealed'
                        AND version.revision = NEW.report_revision AND version.status = 'sealed'
                        AND version.actor_party_id = NEW.author_party_id AND version.actor_user_id = NEW.actor_user_id) THEN
                        RAISE EXCEPTION 'Audit seal and terminal report version must commit together' USING ERRCODE = '23514';
                    END IF;
                    RETURN NULL;
                END;
                $$;
                CREATE CONSTRAINT TRIGGER audit_seal_version_required AFTER INSERT ON audit_report_seals
                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_audit_seal_version();
                SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE audit_step_up_proofs, audit_signing_keys, audit_signing_key_revocations, audit_report_seals IN ACCESS EXCLUSIVE MODE');
            foreach (['audit_step_up_proofs', 'audit_signing_keys', 'audit_signing_key_revocations', 'audit_report_seals'] as $table) {
                if (DB::table($table)->exists()) {
                    throw new RuntimeException('Existing audit signing history requires a forward migration.');
                }
            }
            DB::unprepared('DROP TRIGGER zzzz_audit_report_seal_required ON audit_reports; DROP FUNCTION require_audit_report_seal()');
            foreach (['audit_report_seals', 'audit_signing_key_revocations', 'audit_signing_keys', 'audit_step_up_proofs'] as $table) {
                Schema::drop($table);
            }
            DB::unprepared('DROP FUNCTION protect_audit_step_up(); DROP FUNCTION reject_audit_signing_mutation(); DROP FUNCTION serialize_audit_key_revocation(); DROP FUNCTION validate_audit_seal_authority(); DROP FUNCTION require_audit_seal_version()');
        });
    }
};
