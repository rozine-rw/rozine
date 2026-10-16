<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only withdrawal outcome evidence from the provider and how it was disposed of.
 *
 * @property string $provider
 * @property string $provider_event_id
 * @property string $withdrawal_id
 * @property string $content_sha256
 * @property string $state
 * @property string $amount
 * @property string $currency
 * @property string $environment
 * @property CarbonImmutable $observed_at
 * @property string $disposition
 * @property array<string, mixed> $evidence
 */
class WalletWithdrawalEvent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['evidence'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'observed_at' => 'immutable_datetime', 'evidence' => 'encrypted:array'];
    }
}
