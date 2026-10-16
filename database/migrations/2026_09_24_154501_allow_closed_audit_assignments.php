<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_status;
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_recipient;
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_status CHECK (status IN ('offered', 'accepted', 'operations', 'completed', 'closed'));
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_recipient CHECK ((status IN ('operations', 'closed') AND party_id IS NULL) OR (status NOT IN ('operations', 'closed') AND party_id IS NOT NULL));
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_status;
            ALTER TABLE audit_assignments DROP CONSTRAINT audit_assignment_recipient;
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_status CHECK (status IN ('offered', 'accepted', 'operations', 'completed'));
            ALTER TABLE audit_assignments ADD CONSTRAINT audit_assignment_recipient CHECK ((status = 'operations' AND party_id IS NULL) OR (status <> 'operations' AND party_id IS NOT NULL));
            SQL);
    }
};
