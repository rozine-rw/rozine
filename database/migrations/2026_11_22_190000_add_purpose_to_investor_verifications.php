<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Which app a person verified their identity for. Every Business and Auditor onboarding starts with
 * the same identity check an Investor makes, but only an Investor case may grant Investor access on
 * approval: an Auditor keeps no other role (AUD-R01), so a check made for Auditor access must leave
 * the person free to ask for it. Every existing case was started for Investor access.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_verifications ADD COLUMN purpose varchar(20) NOT NULL DEFAULT 'investor';
            ALTER TABLE investor_verifications ADD CONSTRAINT investor_verification_purpose CHECK (purpose IN ('investor', 'business', 'auditor'));
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_verifications DROP CONSTRAINT IF EXISTS investor_verification_purpose;
            ALTER TABLE investor_verifications DROP COLUMN IF EXISTS purpose;
            SQL);
    }
};
