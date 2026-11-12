<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Compliance's halt of the whole market (no note) or of one note (SEC-R08). Append-only; a halt is
 * in force until it has a lift.
 *
 * @property string $id
 * @property string|null $business_campaign_id
 * @property string $reason
 * @property int $staff_user_id
 * @property string $operation_id
 * @property CarbonImmutable $halted_at
 * @property-read SecondaryHaltLift|null $lift
 */
class SecondaryHalt extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return HasOne<SecondaryHaltLift, $this> */
    public function lift(): HasOne
    {
        return $this->hasOne(SecondaryHaltLift::class, 'halt_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['staff_user_id' => 'integer', 'halted_at' => 'immutable_datetime'];
    }
}
