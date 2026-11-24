<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The landing page hands every waitlist signup a link to share. The code in
     * that link is stored on the signup that owns it, and a signup that arrived
     * through someone's link keeps the code it came with, so referrals can be
     * counted later. Both are nullable: Pulse signups and older site signups
     * never had a link.
     */
    public function up(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->string('referral_code', 64)->nullable()->unique()->after('queue_number');
            $table->string('referred_by', 64)->nullable()->index()->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('pulse_signups', function (Blueprint $table) {
            $table->dropIndex(['referred_by']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
