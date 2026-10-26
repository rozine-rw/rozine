<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A business photo may live on the public media disk instead of in the row. It then keeps only its
 * public `url` (pinned by the same `sha256`) and no `content`; every photo has exactly one of the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_photos', function (Blueprint $table): void {
            $table->text('url')->nullable();
            $table->text('content')->nullable()->change();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_location CHECK ((content IS NULL) <> (url IS NULL));
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_url CHECK (url IS NULL OR url LIKE 'https://%' OR url LIKE 'http://%');
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DELETE FROM business_photos WHERE content IS NULL;
            ALTER TABLE business_photos DROP CONSTRAINT business_photo_url;
            ALTER TABLE business_photos DROP CONSTRAINT business_photo_location;
            SQL);
        Schema::table('business_photos', function (Blueprint $table): void {
            $table->text('content')->nullable(false)->change();
            $table->dropColumn('url');
        });
    }
};
