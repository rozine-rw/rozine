<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A business now sizes itself on five figures it reports rather than on a
     * statement it uploads, so the cash flow read off a document gives way to
     * the revenue, costs, sector and trading history it answers with.
     */
    public function up(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->renameColumn('annual_inflow', 'annual_revenue');
        });

        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->dropColumn('statement_path');
            $table->unsignedBigInteger('annual_costs')->nullable()->after('annual_revenue');
            $table->string('sector')->nullable()->after('annual_costs');
            $table->unsignedSmallInteger('registered_year')->nullable()->after('sector');
            $table->decimal('score', 4, 1)->nullable()->after('registered_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->dropColumn(['annual_costs', 'sector', 'registered_year', 'score']);
            $table->string('statement_path')->nullable()->after('blended_yield');
        });

        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->renameColumn('annual_revenue', 'annual_inflow');
        });
    }
};
