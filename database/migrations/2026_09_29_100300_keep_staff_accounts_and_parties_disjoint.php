<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Defence in depth for the disbursement lock order (#176 pre-review). A staff account never
 * acquires a marketplace Party, and a staff account row is never deleted, so the worker's
 * Business → recorded maker/checker user locks can never meet checkout's user → Party → Business
 * order on the same user row, even after a raw SQL change.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION keep_staff_user_without_party() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                IF NEW.party_id IS NOT NULL AND EXISTS (SELECT 1 FROM staff_accounts WHERE user_id = NEW.id) THEN
                    RAISE EXCEPTION 'A staff account never holds a marketplace Party' USING ERRCODE = '23514';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER users_staff_without_party BEFORE INSERT OR UPDATE OF party_id ON users
                FOR EACH ROW EXECUTE FUNCTION keep_staff_user_without_party();

            CREATE OR REPLACE FUNCTION retain_staff_account() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Staff accounts are disabled, never deleted' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER staff_accounts_retained BEFORE DELETE ON staff_accounts
                FOR EACH ROW EXECUTE FUNCTION retain_staff_account();
            SQL);
    }

    /** Guards carry no rows of their own. */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER staff_accounts_retained ON staff_accounts; DROP FUNCTION retain_staff_account();
            DROP TRIGGER users_staff_without_party ON users; DROP FUNCTION keep_staff_user_without_party();');
    }
};
