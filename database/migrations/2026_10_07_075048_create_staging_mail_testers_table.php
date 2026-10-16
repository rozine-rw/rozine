<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The named testers a superadmin lets staging email, on top of the server's STAGING_MAIL_RECIPIENTS
 * domains. Each address is stored lower-cased and once; who added it and every change are kept in the
 * command journal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging_mail_testers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email', 254)->unique();
            $table->foreignId('added_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_mail_testers');
    }
};
