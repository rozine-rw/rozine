<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account closure is a soft delete (Erastus, 22 Nov 2026): the account keeps its row and every
 * record tied to it, because BRS DR-7/CR-9 keep records for the statutory period and GEN-R05 never
 * deletes ledger or audit entries. `closed_at` marks a closed account, which can no longer sign in;
 * the reason is the holder's own optional words, shown to staff on the Deleted accounts list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestampTz('closed_at')->nullable()->index();
            $table->string('closure_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['closed_at', 'closure_reason']);
        });
    }
};
