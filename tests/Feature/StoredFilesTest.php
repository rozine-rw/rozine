<?php

declare(strict_types=1);

use App\Casts\StoredFile;
use App\Models\AuditorCertificate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->root = storage_path('framework/testing/stored-files-'.uniqid());
    config(['filesystems.disks.files-test' => ['driver' => 'local', 'root' => $this->root, 'throw' => true]]);
});

afterEach(function (): void {
    File::deleteDirectory($this->root);
});

it('keeps a file in its column, encrypted, when no files disk is configured', function (): void {
    $certificate = AuditorCertificate::factory()->create();
    $column = DB::table('auditor_certificates')->where('id', $certificate->id)->value('content');

    expect($column)->not->toStartWith(StoredFile::PREFIX)
        ->and(Crypt::decryptString($column))->toBe($certificate->content)
        ->and($certificate->fresh()?->content)->toBe("%PDF-1.7\nSynthetic certificate fixture\n%%EOF");
});

it('puts a file on the files disk, encrypted, and keeps only a reference in its column', function (): void {
    config(['filesystems.files_disk' => 'files-test']);
    $certificate = AuditorCertificate::factory()->create();
    $column = (string) DB::table('auditor_certificates')->where('id', $certificate->id)->value('content');
    $path = substr($column, strlen(StoredFile::PREFIX.'files-test:'));

    expect($column)->toStartWith(StoredFile::PREFIX.'files-test:auditor_certificates/')
        ->and(Crypt::decryptString((string) Storage::disk('files-test')->get($path)))->toBe("%PDF-1.7\nSynthetic certificate fixture\n%%EOF")
        ->and($certificate->fresh()?->content)->toBe("%PDF-1.7\nSynthetic certificate fixture\n%%EOF");

    Storage::disk('files-test')->delete($path);

    expect($certificate->fresh()?->content)->toBe('');
});

it('reads a null file as null and writes null as null', function (): void {
    $cast = new StoredFile;
    $model = new AuditorCertificate;

    expect($cast->get($model, 'content', null, []))->toBeNull()
        ->and($cast->set($model, 'content', null, []))->toBeNull();
});

it('moves the files kept in columns onto the files disk and restores immutability', function (): void {
    $certificate = AuditorCertificate::factory()->create();
    config(['filesystems.files_disk' => 'files-test']);

    expect(Artisan::call('files:move', ['--dry-run' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('auditor_certificates: 1')
        ->and(DB::table('auditor_certificates')->where('id', $certificate->id)->value('content'))->not->toStartWith(StoredFile::PREFIX);

    expect(Artisan::call('files:move'))->toBe(0)
        ->and(Artisan::output())->toContain('auditor_certificates: 1', 'statement_originals: 0', 'No media disk is configured')
        ->and(DB::table('auditor_certificates')->where('id', $certificate->id)->value('content'))->toStartWith(StoredFile::PREFIX.'files-test:')
        ->and($certificate->fresh()?->content)->toBe("%PDF-1.7\nSynthetic certificate fixture\n%%EOF");

    expect(Artisan::call('files:move'))->toBe(0)
        ->and(Artisan::output())->toContain('auditor_certificates: 0')
        ->and(fn () => DB::table('auditor_certificates')->where('id', $certificate->id)->update(['content' => 'changed']))
        ->toThrow(QueryException::class);
});

it('moves nothing without a files disk', function (): void {
    AuditorCertificate::factory()->create();

    expect(Artisan::call('files:move'))->toBe(0)
        ->and(Artisan::output())->toContain('No files disk is configured', 'No media disk is configured')
        ->and(DB::table('auditor_certificates')->where('content', 'like', StoredFile::PREFIX.'%')->count())->toBe(0);
});

it('refuses to move files in production', function (): void {
    config(['filesystems.files_disk' => 'files-test']);
    app()->detectEnvironment(fn (): string => 'production');

    try {
        expect(Artisan::call('files:move'))->toBe(1)
            ->and(Artisan::output())->toContain('Files stay in the database in production.');
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }
});
