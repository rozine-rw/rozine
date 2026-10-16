<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A disbursement's single terminal closing: issued, or failed closing with refunds.
 *
 * @property string $disbursement_id
 * @property string|null $intent_id
 * @property string|null $reconciliation_id
 * @property string $kind
 * @property string $cause
 * @property list<string> $causes
 * @property string|null $operation_id
 * @property int|null $actor_user_id
 * @property string|null $request_id
 * @property CarbonImmutable|null $effective_at
 * @property CarbonImmutable|null $effective_date
 * @property list<string>|null $due_dates
 * @property string $sha256
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class DisbursementClosing extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['causes' => 'array', 'effective_at' => 'immutable_datetime', 'effective_date' => 'immutable_date', 'due_dates' => 'array', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
