<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One call made to the payout provider: the send-count and query-count evidence.
 *
 * @property string $intent_id
 * @property string $kind
 * @property string $source
 * @property string|null $operation_id
 * @property CarbonImmutable $created_at
 */
class DisbursementProviderCall extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
