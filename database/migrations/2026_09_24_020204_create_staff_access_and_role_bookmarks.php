<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_accounts', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('role_bookmarks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->foreignUlid('membership_id')->constrained('role_memberships')->restrictOnDelete();
            $table->unsignedInteger('membership_revision');
            $table->string('route');
            $table->jsonb('parameters');
            $table->jsonb('query');
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_bookmarks');
        Schema::dropIfExists('staff_accounts');
    }
};
