<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institutional investors (Erastus and Robert, 26 Nov 2026; BRS v3 KYC-R02): a verified Investor may apply
 * to invest for an institution, such as a pension fund or a VC, with the institution's KYB as for a
 * business (KYC-R03) plus an authorised signatory. One approval records the institution the account
 * invests for. The person keeps the Investor role, so an account still holds one role at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE role_applications DROP CONSTRAINT role_application_role;
            ALTER TABLE role_applications ADD CONSTRAINT role_application_role CHECK (role IN ('business', 'auditor', 'institution'));
        SQL);
        Schema::create('institutional_investors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignUlid('role_application_id')->unique()->constrained()->restrictOnDelete();
            $table->string('legal_name', 180);
            $table->string('rdb_code', 9);
            $table->string('institution_type', 40);
            $table->unsignedBigInteger('monthly_commitment');
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('approved_at');
            $table->timestampsTz();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE institutional_investors ADD CONSTRAINT institutional_investor_commitment CHECK (monthly_commitment >= 30000000);
            ALTER TABLE institutional_investors ADD CONSTRAINT institutional_investor_rdb_code CHECK (rdb_code ~ '^[0-9]{9}$');
            ALTER TABLE institutional_investors ADD CONSTRAINT institutional_investor_type CHECK (institution_type IN
                ('pension_fund', 'venture_capital', 'asset_manager', 'insurer', 'bank', 'corporate_treasury', 'other'));
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_investors');
        // History rows are append-only, so an institution application blocks the rollback rather than losing its history.
        DB::unprepared(<<<'SQL'
            ALTER TABLE role_applications DROP CONSTRAINT role_application_role;
            ALTER TABLE role_applications ADD CONSTRAINT role_application_role CHECK (role IN ('business', 'auditor'));
        SQL);
    }
};
