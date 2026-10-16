<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One authenticated provider observation of a payout, kept whatever its disposition.
 *
 * @property string $intent_id
 * @property string $provider
 * @property string $provider_event_id
 * @property string $content_sha256
 * @property string $source
 * @property string $state
 * @property string|null $amount
 * @property string|null $currency
 * @property string|null $environment
 * @property string|null $observed_operation_id
 * @property string|null $provider_reference_sha256
 * @property string|null $destination_sha256
 * @property CarbonImmutable $observed_at
 * @property CarbonImmutable|null $effective_at
 * @property string $disposition
 * @property list<string> $mismatches
 * @property array<string, mixed> $evidence
 * @property CarbonImmutable $created_at
 */
class DisbursementProviderEvent extends Model
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
        return ['amount' => 'string', 'observed_at' => 'immutable_datetime', 'effective_at' => 'immutable_datetime', 'mismatches' => 'array', 'evidence' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
