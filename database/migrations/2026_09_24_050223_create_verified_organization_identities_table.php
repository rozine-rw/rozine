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
        Schema::create('verified_organization_identities', function (Blueprint $table): void {
            $table->char('registry_digest', 64)->primary();
            $table->foreignUlid('party_id')->unique()->constrained()->restrictOnDelete();
            $table->string('evidence_reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verified_organization_identities');
    }
};
