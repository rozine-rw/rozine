<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX business_application_one_draft ON business_applications (business_id) WHERE status = 'draft'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX business_application_one_draft');
    }
};
