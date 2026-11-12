<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A buyer's five-minute reservation on a listing (SEC-R05); only `EloquentSecondaryMarket` writes it.
 *
 * @property string $id
 * @property string $listing_id
 * @property string $buyer_party_id
 * @property string $business_campaign_id
 * @property int $units
 * @property string $price
 * @property string $gross
 * @property CarbonImmutable $reserved_at
 * @property CarbonImmutable $expires_at
 * @property string $operation_id
 * @property 'settled'|'released'|null $ended
 * @property 'settled'|'lapsed'|'halted'|null $end_reason
 * @property CarbonImmutable|null $ended_at
 */
class SecondaryReservationRecord extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'secondary_reservations';

    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer', 'price' => 'string', 'gross' => 'string', 'reserved_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime'];
    }
}
