<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('primary_expiry_failures', function (Blueprint $table): void {
            $table->foreignUlid('primary_reservation_id')->primary()->constrained('primary_reservations')->restrictOnDelete();
            $table->timestampTz('last_attempted_at', 6)->index();
            $table->string('exception_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('primary_expiry_failures');
    }
};
