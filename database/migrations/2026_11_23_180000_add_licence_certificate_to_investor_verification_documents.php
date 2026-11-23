<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An identity check made for Auditor access also carries the person's ICPAR licence certificate, so
 * Compliance verifies the person and their licence in one review and approval makes them an Auditor.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_verification_documents DROP CONSTRAINT investor_verification_document_slot;
            ALTER TABLE investor_verification_documents ADD CONSTRAINT investor_verification_document_slot CHECK (slot IN ('front', 'back', 'selfie', 'certificate'));
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE investor_verification_documents DROP CONSTRAINT investor_verification_document_slot;
            ALTER TABLE investor_verification_documents ADD CONSTRAINT investor_verification_document_slot CHECK (slot IN ('front', 'back', 'selfie'));
            SQL);
    }
};
