<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login alerts (the design's Security setting, BRS v2.0 NTF-R01): whether the account is also
 * e-mailed when a new device signs in. On by default, as in the design; the in-app notice is
 * recorded either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('login_alerts')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('login_alerts');
        });
    }
};
