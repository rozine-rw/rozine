<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\StoredFile;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $role_application_id
 * @property string $slot
 * @property string $filename
 * @property string $media_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string $content
 * @property Carbon $created_at
 */
class RoleApplicationDocument extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['filename', 'content'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['filename' => 'encrypted', 'content' => StoredFile::class, 'size_bytes' => 'integer'];
    }
}
