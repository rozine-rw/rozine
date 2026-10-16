<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The evidence behind the real staff-person connection source (#96 5956110941, owner decision
 * 5956161592). A staff account never holds a marketplace Party, so two retained facts stand in:
 *
 * - `staff_person_identities`: an identity operator's append-only record linking a staff user to
 *   the person's verified identity digest (the `verified_person_identities` scheme). Each change is
 *   a new revision; the latest revision is current, and a revoked revision resolves nobody.
 * - `disbursement_independence_declarations`: the staff member's own signed statement, made with
 *   their authorize or approve command, that they have no relationship with the Business or its
 *   Investors. It is the only evidence of being unconnected; a visible connection always wins. It
 *   is bound to the command's operation and to the staff-person revision current when it was
 *   signed (null when none was), so it never vouches for another command or a re-linked person.
 *
 * Neither table carries a foreign key to `users` or `disbursements`: a key would take KEY SHARE on
 * those rows at insert, which the disbursement lock order does not otherwise acquire there.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE staff_person_identities (
                id char(26) PRIMARY KEY,
                staff_user_id bigint NOT NULL,
                revision integer NOT NULL CHECK (revision > 0),
                status varchar(10) NOT NULL CHECK (status IN ('active', 'revoked')),
                identity_digest char(64) NULL CHECK (identity_digest ~ '^[0-9a-f]{64}$'),
                evidence_reference varchar(255) NOT NULL CHECK (btrim(evidence_reference) <> ''),
                recorded_by bigint NOT NULL,
                created_at timestamptz NOT NULL,
                CONSTRAINT staff_person_identity_digest_state CHECK ((status = 'active') = (identity_digest IS NOT NULL)),
                CONSTRAINT staff_person_identity_revision UNIQUE (staff_user_id, revision)
            );
            CREATE TABLE disbursement_independence_declarations (
                id char(26) PRIMARY KEY,
                disbursement_id char(26) NOT NULL,
                staff_user_id bigint NOT NULL,
                staff_person_identity_id char(26) NULL,
                command varchar(10) NOT NULL CHECK (command IN ('authorize', 'approve')),
                operation_id char(26) NOT NULL UNIQUE,
                statement_version varchar(40) NOT NULL,
                statement_sha256 char(64) NOT NULL CHECK (statement_sha256 ~ '^[0-9a-f]{64}$'),
                declared_at timestamptz NOT NULL
            );
            CREATE INDEX disbursement_independence_staff ON disbursement_independence_declarations (disbursement_id, staff_user_id);

            CREATE OR REPLACE FUNCTION reject_staff_independence_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Staff independence evidence is append-only' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER staff_person_identities_append_only BEFORE UPDATE OR DELETE ON staff_person_identities
                FOR EACH ROW EXECUTE FUNCTION reject_staff_independence_mutation();
            CREATE TRIGGER disbursement_independence_append_only BEFORE UPDATE OR DELETE ON disbursement_independence_declarations
                FOR EACH ROW EXECUTE FUNCTION reject_staff_independence_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::unprepared(<<<'SQL'
                LOCK TABLE staff_person_identities, disbursement_independence_declarations IN ACCESS EXCLUSIVE MODE;
                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM staff_person_identities) OR EXISTS (SELECT 1 FROM disbursement_independence_declarations) THEN
                        RAISE EXCEPTION 'Recorded staff independence evidence requires a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
                DROP TABLE disbursement_independence_declarations;
                DROP TABLE staff_person_identities;
                DROP FUNCTION reject_staff_independence_mutation();
                SQL);
        });
    }
};
