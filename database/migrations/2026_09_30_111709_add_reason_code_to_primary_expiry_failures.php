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
        Schema::table('primary_expiry_failures', function (Blueprint $table): void {
            $table->string('reason_code')->nullable();
        });
    }

    public function down(): void
    {
        DB::statement('LOCK TABLE primary_expiry_failures IN ACCESS EXCLUSIVE MODE');
        if (DB::table('primary_expiry_failures')->whereNotNull('reason_code')->exists()) {
            throw new RuntimeException('Retained expiry failure reasons require a forward migration.');
        }
        Schema::table('primary_expiry_failures', function (Blueprint $table): void {
            $table->dropColumn('reason_code');
        });
    }
};
