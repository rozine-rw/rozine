<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $investor_verification_id
 * @property 'front'|'back'|'selfie' $slot
 * @property string $filename
 * @property string $media_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string $content
 */
class InvestorVerificationDocument extends Model
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
        return ['filename' => 'encrypted', 'content' => 'encrypted', 'size_bytes' => 'integer'];
    }
}
