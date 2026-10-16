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
        Schema::create('business_campaign_expiry_failures', function (Blueprint $table): void {
            $table->foreignUlid('business_campaign_id')->primary()->constrained('business_campaigns')->restrictOnDelete();
            $table->timestampTz('last_attempted_at', 6)->index();
            $table->string('exception_class');
            $table->string('reason_code');
        });
    }

    public function down(): void
    {
        DB::statement('LOCK TABLE business_campaign_expiry_failures IN ACCESS EXCLUSIVE MODE');
        if (DB::table('business_campaign_expiry_failures')->exists()) {
            throw new RuntimeException('Retained campaign expiry failures require a forward migration.');
        }
        Schema::dropIfExists('business_campaign_expiry_failures');
    }
};
