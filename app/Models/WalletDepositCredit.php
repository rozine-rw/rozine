<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WalletDepositCreditFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The DEPOSIT_CREDITED receipt: one balanced entry for one applied success.
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
class WalletDepositCredit extends Model
{
    /** @use HasFactory<WalletDepositCreditFactory> */
    use HasFactory, HasUlids;

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
