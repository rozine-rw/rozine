<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One phase of an intent's outbox: queued, claimed, sent, unsent or recheck_failed.
 *
 * @property string $intent_id
 * @property string $phase
 * @property CarbonImmutable $created_at
 */
class DisbursementDispatch extends Model
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
