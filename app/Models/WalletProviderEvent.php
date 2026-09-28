<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WalletProviderEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only provider outcome evidence and how it was disposed of.
 *
 * @property string $provider
 * @property string $provider_event_id
 * @property string $intent_id
 * @property string $content_sha256
 * @property string $state
 * @property string $amount
 * @property string $currency
 * @property string $environment
 * @property CarbonImmutable $observed_at
 * @property string $disposition
 * @property array<string, mixed> $evidence
 */
class WalletProviderEvent extends Model
{
    /** @use HasFactory<WalletProviderEventFactory> */
    use HasFactory, HasUlids;

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
