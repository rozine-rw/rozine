<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX identity_staff_recovery_once ON identity_audit_events (("after"->>'recovery_proposal_id'))
            WHERE action = 'staff.recovery.complete'
            SQL);
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX identity_staff_genesis_once ON identity_audit_events (("after"->>'kind'))
            WHERE action = 'staff.recovery.complete' AND "after"->>'kind' = 'genesis'
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX identity_staff_genesis_once');
        DB::statement('DROP INDEX identity_staff_recovery_once');
    }
};
