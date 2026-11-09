<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One child of a whole-cart checkout (#383 step 4): the cart line, its campaign, the primary
 * reservation that holds it and that reservation's own `primary.reserve` receipt.
 *
 * @property string $investor_cart_checkout_id
 * @property string $investor_cart_line_id
 * @property string $business_campaign_id
 * @property string $primary_reservation_id
 * @property string $child_operation_id
 * @property int $position
 */
class InvestorCartCheckoutLine extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'investor_cart_checkout_id';

    protected $keyType = 'string';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
