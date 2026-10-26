<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $business_id
 * @property int $position
 * @property string $caption
 * @property 'image/jpeg'|'image/png'|'image/webp' $media_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string|null $content base64 of the image bytes, when they are kept in the row
 * @property string|null $url the public media URL, when the bytes are on the media disk
 */
class BusinessPhoto extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['content'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer', 'size_bytes' => 'integer'];
    }
}
