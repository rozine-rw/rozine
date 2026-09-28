<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A ledger account: an Investor bucket or a system clearing/fee account.
 *
 * @property string|null $wallet_id
 * @property string $kind
 * @property string $currency
 */
class LedgerAccount extends Model
{
    /** @use HasFactory<LedgerAccountFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];
}
