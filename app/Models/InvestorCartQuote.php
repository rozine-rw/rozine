<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * An immutable whole-cart quote (#383): the terms offered for one cart revision. It holds nothing.
 *
 * @property string $id
 * @property string $party_id
 * @property string $investor_cart_id
 * @property int $cart_revision
 * @property string $cart_sha256
 * @property string $contract_version
 * @property array<string, mixed> $payload
 * @property string $disclosure_sha256
 * @property string $sha256
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $expires_at
 */
class InvestorCartQuote extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['cart_revision' => 'integer', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }
}
