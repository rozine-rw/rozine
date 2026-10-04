<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * The one credit receipt of a Business deposit intent's applied success.
 *
 * @property string $intent_id
 * @property string $wallet_id
 * @property string $ledger_entry_id
 * @property string $provider_event_id
 * @property string $operation_id
 * @property string $request_id
 * @property string $amount
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessDepositCredit extends Model
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
        return ['amount' => 'string', 'payload' => 'encrypted:array'];
    }
}
