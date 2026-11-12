<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A settled resale (SEC-R06): the lowest ordinals of the seller's lot, now the buyer's lot under this
 * id. Immutable; only `EloquentSecondaryMarket` writes it.
 *
 * @property string $id
 * @property string $reservation_id
 * @property string $listing_id
 * @property string $business_campaign_id
 * @property string $seller_party_id
 * @property string $buyer_party_id
 * @property 'primary_holding'|'secondary_transfer' $source_lot_type
 * @property string $source_lot_id
 * @property int $units
 * @property list<array{first: string, last: string}> $ordinals
 * @property string $price
 * @property string $gross
 * @property string $seller_fee
 * @property string $seller_receives
 * @property string $terms_version
 * @property string $operation_id
 * @property CarbonImmutable $settled_at
 */
class SecondaryTransfer extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer', 'ordinals' => 'array', 'price' => 'string', 'gross' => 'string', 'seller_fee' => 'string',
            'seller_receives' => 'string', 'settled_at' => 'immutable_datetime'];
    }
}
