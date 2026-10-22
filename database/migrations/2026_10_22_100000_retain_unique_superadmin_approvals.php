<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX identity_staff_approval_once ON identity_audit_events ((\"after\"->>'proposal_id')) WHERE action = 'staff.configure' AND jsonb_exists(\"after\", 'proposal_id')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX identity_staff_approval_once');
    }
};
