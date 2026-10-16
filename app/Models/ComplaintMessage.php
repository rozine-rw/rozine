<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One message in a complaint's conversation, from the customer or from Rozine staff; a staff
 * message may be the resolution. Its text is personal data, held encrypted. It is never edited or
 * removed.
 *
 * @property string $id
 * @property string $complaint_id
 * @property 'customer'|'staff' $author
 * @property int $author_user_id
 * @property bool $resolves
 * @property string $body
 * @property string $operation_id
 * @property CarbonImmutable $created_at
 */
class ComplaintMessage extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['body'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['author_user_id' => 'integer', 'resolves' => 'boolean', 'body' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }
}
