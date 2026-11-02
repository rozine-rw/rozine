<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only state of a whole-cart checkout (#383 step 4): held, confirmed, released or expired.
 *
 * @property string $id
 * @property string $investor_cart_checkout_id
 * @property int $revision
 * @property string $state
 * @property string $investor_cart_quote_id
 * @property string|null $operation_id
 * @property CarbonImmutable $created_at
 */
class InvestorCartCheckoutVersion extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
