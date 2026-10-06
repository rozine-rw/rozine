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
        Schema::create('pulse_signups', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('name');
            $table->string('contact_method');
            $table->string('contact')->unique();
            $table->string('province');
            $table->string('district');
            $table->string('queue_number');

            // Investor pledges.
            $table->unsignedBigInteger('pledge_amount')->nullable();
            $table->unsignedBigInteger('projected_return')->nullable();
            $table->decimal('blended_yield', 5, 2)->nullable();

            // Business pre-qualifications.
            $table->string('statement_path')->nullable();
            $table->unsignedBigInteger('annual_inflow')->nullable();
            $table->unsignedBigInteger('qualified_amount')->nullable();
            $table->unsignedTinyInteger('term_months')->nullable();
            $table->decimal('flat_rate', 5, 2)->nullable();
            $table->string('rating_band')->nullable();
            $table->decimal('rating_score', 2, 1)->nullable();
            $table->string('loan_number')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_signups');
    }
};
