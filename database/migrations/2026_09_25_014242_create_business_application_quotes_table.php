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
        Schema::create('business_application_quotes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_application_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->char('sha256', 64);
            $table->text('payload');
            $table->timestampTz('created_at');
            $table->unique(['business_application_id', 'revision'], 'application_quote_revision_unique');
            $table->unique(['business_application_id', 'id'], 'application_quote_owner_unique');
        });
        Schema::table('business_applications', function (Blueprint $table): void {
            $table->ulid('current_quote_id')->nullable();
            $table->foreign(['id', 'current_quote_id'], 'application_current_quote_owner')
                ->references(['business_application_id', 'id'])->on('business_application_quotes')->restrictOnDelete();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_application_quotes ADD CONSTRAINT application_quote_revision_positive CHECK (revision > 0);
            CREATE OR REPLACE FUNCTION reject_business_application_quote_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'Business application quotes are immutable' USING ERRCODE = '23514';
            END;
            $$;
            CREATE TRIGGER business_application_quotes_immutable
            BEFORE UPDATE OR DELETE ON business_application_quotes
            FOR EACH ROW EXECUTE FUNCTION reject_business_application_quote_mutation();
            SQL);
    }

    public function down(): void
    {
        Schema::table('business_applications', function (Blueprint $table): void {
            $table->dropForeign('application_current_quote_owner');
            $table->dropColumn('current_quote_id');
        });
        Schema::dropIfExists('business_application_quotes');
        DB::unprepared('DROP FUNCTION IF EXISTS reject_business_application_quote_mutation()');
    }
};
