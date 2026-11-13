<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One immutable tick or untick of a visit-ready checklist item (BRS v3 VIS-R03) for the instalment
 * due on `due_on`; an item's latest row is its current state.
 *
 * @property string $id
 * @property string $business_id
 * @property string $due_on
 * @property string $item
 * @property bool $ticked
 * @property string $actor_party_id
 * @property string $operation_id
 */
class BusinessVisitReadyTick extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['due_on' => 'date:Y-m-d', 'ticked' => 'boolean', 'created_at' => 'immutable_datetime'];
    }
}
