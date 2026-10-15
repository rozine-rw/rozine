<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An immutable Investor withdrawal, recorded with its hold before any provider is called.
 *
 * @property string $id
 * @property string $wallet_id
 * @property string $party_id
 * @property string $method_id
 * @property string $operation_id
 * @property string $request_id
 * @property string $terms_version
 * @property string $amount
 * @property string $provider_charge
 * @property string $received
 * @property string $currency
 * @property string $provider
 * @property string $provider_reference
 * @property string $provider_reference_sha256
 * @property string $sha256
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 */
class WalletWithdrawal extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['provider_reference', 'payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'provider_charge' => 'string', 'received' => 'string', 'provider_reference' => 'encrypted',
            'payload' => 'encrypted:array'];
    }
}
