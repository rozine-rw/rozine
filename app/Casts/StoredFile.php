<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An uploaded file's bytes, kept on the configured files disk when there is one. The column then
 * holds `disk:{disk}:{path}` and the object holds what the column used to: the ciphertext. Without a
 * files disk, and for rows written before one, the ciphertext stays in the column. A missing object
 * reads as an empty string, so the stores' digest checks refuse it rather than serving the wrong bytes.
 *
 * @implements CastsAttributes<string, string>
 */
final class StoredFile implements CastsAttributes
{
    public const string PREFIX = 'disk:';

    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $stored = self::read($value);

        return $stored === '' ? '' : Crypt::decryptString($stored);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return self::write($model->getTable(), Crypt::encryptString($value));
    }

    /** The stored value behind a column: the object a disk reference names, or the column itself. */
    public static function read(string $column): string
    {
        if (! str_starts_with($column, self::PREFIX)) {
            return $column;
        }
        [$disk, $path] = explode(':', substr($column, strlen(self::PREFIX)), 2);

        return Storage::disk($disk)->exists($path) ? (string) Storage::disk($disk)->get($path) : '';
    }

    /** Puts a stored value on the files disk and returns the column value that names it. */
    public static function write(string $table, string $stored): string
    {
        $disk = config('filesystems.files_disk');
        if (! is_string($disk)) {
            return $stored;
        }
        $path = $table.'/'.strtolower((string) Str::ulid());
        Storage::disk($disk)->put($path, $stored);

        return self::PREFIX.$disk.':'.$path;
    }
}
