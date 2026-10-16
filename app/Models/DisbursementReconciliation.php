<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One recorded reconciliation decision for an intent, with what it compared.
 *
 * @property string $intent_id
 * @property string|null $provider_event_id
 * @property string $decision
 * @property list<string> $causes
 * @property array<string, mixed> $comparison
 * @property CarbonImmutable $created_at
 */
class DisbursementReconciliation extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['causes' => 'array', 'comparison' => 'array', 'created_at' => 'immutable_datetime'];
    }
}
