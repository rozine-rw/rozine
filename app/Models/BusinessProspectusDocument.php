<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\StoredFile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A quote, invoice or signed contract the Business uploaded as evidence for its prospectus.
 *
 * @property string $id
 * @property string $business_id
 * @property string $business_application_id
 * @property string $kind
 * @property string $filename
 * @property string $media_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string $content
 * @property string $actor_party_id
 * @property string $operation_id
 * @property CarbonImmutable $created_at
 */
class BusinessProspectusDocument extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['content', 'filename'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['content' => StoredFile::class, 'filename' => 'encrypted', 'size_bytes' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
