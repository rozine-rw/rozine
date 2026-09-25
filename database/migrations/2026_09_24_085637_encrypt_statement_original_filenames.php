<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statement_originals', function (Blueprint $table): void {
            $table->text('filename')->change();
        });
        $this->convertFilenames(true);
    }

    public function down(): void
    {
        $this->convertFilenames(false);
        Schema::table('statement_originals', function (Blueprint $table): void {
            $table->string('filename', 180)->change();
        });
    }

    /** The migration transaction holds the table lock through conversion and restores immutability. */
    private function convertFilenames(bool $encrypt): void
    {
        DB::statement('ALTER TABLE statement_originals DISABLE TRIGGER statement_originals_immutable');
        DB::table('statement_originals')->orderBy('id')->eachById(function (object $row) use ($encrypt): void {
            DB::table('statement_originals')->where('id', $row->id)->update([
                'filename' => $encrypt ? Crypt::encryptString($row->filename) : Crypt::decryptString($row->filename),
            ]);
        });
        DB::statement('ALTER TABLE statement_originals ENABLE TRIGGER statement_originals_immutable');
    }
};
