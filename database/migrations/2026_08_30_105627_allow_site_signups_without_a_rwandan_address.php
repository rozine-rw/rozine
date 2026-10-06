<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The marketing site asks an investor for their country rather than a
     * Rwandan province and district, so those two columns can no longer be
     * required for every signup. Business signups still collect both.
     */
    public function up(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->string('province')->nullable()->change();
            $table->string('district')->nullable()->change();
            $table->string('country')->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->dropColumn('country');
            $table->string('district')->nullable(false)->change();
            $table->string('province')->nullable(false)->change();
        });
    }
};
