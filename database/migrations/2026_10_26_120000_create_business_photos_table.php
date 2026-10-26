<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The captioned photos a business publishes with its deals (design "Photos", deal card banner).
 * Like every other file the platform keeps, the bytes live in Postgres (base64 in `content`,
 * pinned by `sha256`), not on a filesystem disk. A business shows at most six, in `position` order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_photos', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('business_profiles')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('caption', 80);
            $table->string('media_type', 20);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->text('content');
            $table->timestampsTz();
            $table->unique(['business_id', 'position']);
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_position CHECK (position BETWEEN 1 AND 6);
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_caption CHECK (btrim(caption) <> '');
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_media CHECK (media_type IN ('image/jpeg', 'image/png', 'image/webp'));
            ALTER TABLE business_photos ADD CONSTRAINT business_photo_size CHECK (size_bytes > 0 AND size_bytes <= 5242880);
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('business_photos');
    }
};
