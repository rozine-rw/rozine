<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A whole-cart checkout (#383 step 4): every cart line held at once under one `cart.reserve`.
 * `expires_at` is the shared confirmation deadline, the earliest child window; `settles_at` is when
 * the last child window closes, from which expiry can settle every child.
 *
 * @property string $id
 * @property string $party_id
 * @property string $investor_cart_id
 * @property string $investor_cart_quote_id
 * @property int $cart_revision
 * @property string $origin_operation_id
 * @property string $principal
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $settles_at
 */
class InvestorCartCheckout extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['cart_revision' => 'integer', 'created_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime', 'settles_at' => 'immutable_datetime'];
    }
}
