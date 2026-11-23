<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A closed account frees what a new account needs (Erastus, 23 Nov 2026: a person who closes an
 * account may sign up again). Closing moves the sign-in email to `users.closed_email`, so the
 * address can register again, and moves the person's verified ID out of `verified_person_identities`
 * into the append-only `released_person_identities`, so the same ID can be verified for the new
 * account. Both are kept, never deleted, for the statutory retention period.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('closed_email')->nullable()->index();
        });

        Schema::create('released_person_identities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('identity_digest', 64)->index();
            $table->foreignUlid('party_id')->constrained()->restrictOnDelete();
            $table->string('evidence_reference');
            $table->foreignId('closed_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('released_at');
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION reject_released_person_identity_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Released person identities are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER released_person_identities_immutable
            BEFORE UPDATE OR DELETE ON released_person_identities
            FOR EACH ROW EXECUTE FUNCTION reject_released_person_identity_mutation();
            SQL);

        $this->releaseAccountsClosedBefore();
    }

    /** Accounts closed before this migration give back their email and ID the same way. */
    private function releaseAccountsClosedBefore(): void
    {
        foreach (DB::table('users')->whereNotNull('closed_at')->whereNull('closed_email')->orderBy('id')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'closed_email' => $user->email, 'email' => sprintf('closed-account-%d@closed.rozine.invalid', $user->id),
            ]);
            $sharedWithOpenAccount = $user->party_id === null || DB::table('users')->where('party_id', $user->party_id)
                ->where('id', '!=', $user->id)->whereNull('closed_at')->exists();
            $identity = $sharedWithOpenAccount ? null : DB::table('verified_person_identities')->where('party_id', $user->party_id)->first();
            if ($identity === null) {
                continue;
            }
            DB::table('released_person_identities')->insert([
                'id' => strtolower((string) Str::ulid()), 'identity_digest' => $identity->identity_digest,
                'party_id' => $identity->party_id, 'evidence_reference' => $identity->evidence_reference,
                'closed_user_id' => $user->id, 'verified_at' => $identity->created_at, 'released_at' => $user->closed_at,
            ]);
            DB::table('verified_person_identities')->where('identity_digest', $identity->identity_digest)->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('released_person_identities');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_released_person_identity_mutation()');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['closed_email']);
            $table->dropColumn('closed_email');
        });
    }
};
