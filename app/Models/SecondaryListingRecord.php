<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A recorded secondary listing (SEC-R02); only `EloquentSecondaryMarket` writes it.
 *
 * @property string $id
 * @property string $seller_party_id
 * @property string $business_campaign_id
 * @property 'primary_holding'|'secondary_transfer' $lot_type
 * @property string $lot_id
 * @property int $listed_units
 * @property int $remaining_units
 * @property string $price
 * @property CarbonImmutable $listed_at
 * @property CarbonImmutable $expires_at
 * @property 'open'|'sold'|'cancelled'|'expired' $state
 * @property 'seller'|'staff'|'halt'|null $cancelled_by
 * @property string|null $cancel_reason
 * @property string $terms_version
 * @property string $operation_id
 * @property CarbonImmutable $updated_at
 */
class SecondaryListingRecord extends Model
{
    public const CREATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'secondary_listings';

    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['listed_units' => 'integer', 'remaining_units' => 'integer', 'price' => 'string', 'listed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime'];
    }
}
