<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One complaint an Investor or a Business opened (BRS v2.0 CMP-R03), with its reference and the
 * acknowledgement and resolution clocks it was opened with. It is never edited or removed.
 *
 * @property string $id
 * @property string $reference
 * @property string $party_id
 * @property 'investor'|'business' $role
 * @property 'in_app' $channel
 * @property int $opened_by_user_id
 * @property string $operation_id
 * @property CarbonImmutable $acknowledge_by
 * @property CarbonImmutable $resolve_by
 * @property CarbonImmutable $created_at
 */
class Complaint extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return HasMany<ComplaintMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ComplaintMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['opened_by_user_id' => 'integer', 'acknowledge_by' => 'immutable_datetime', 'resolve_by' => 'immutable_datetime',
            'created_at' => 'immutable_datetime'];
    }
}
