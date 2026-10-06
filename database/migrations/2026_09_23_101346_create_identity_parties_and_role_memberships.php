<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->enum('kind', ['person', 'organization'])->default('person');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUlid('party_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        Schema::create('role_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('party_id')->constrained()->restrictOnDelete();
            $table->enum('role', ['investor', 'business', 'auditor']);
            $table->enum('status', ['pending', 'active', 'suspended', 'revoked'])->default('pending');
            $table->timestamps();
            $table->unique(['party_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_memberships');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['party_id']);
            $table->dropConstrainedForeignId('party_id');
        });

        Schema::dropIfExists('parties');
    }
};
