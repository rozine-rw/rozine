<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Compliance lifting one halt, with a recorded reason. Append-only.
 *
 * @property string $halt_id
 * @property string $reason
 * @property int $staff_user_id
 * @property string $operation_id
 * @property CarbonImmutable $lifted_at
 */
class SecondaryHaltLift extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'halt_id';

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['staff_user_id' => 'integer', 'lifted_at' => 'immutable_datetime'];
    }
}
