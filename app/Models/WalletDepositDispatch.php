<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WalletDepositDispatchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only provider-dispatch outbox phase for one deposit intent.
 *
 * @property string $intent_id
 * @property string $phase
 */
class WalletDepositDispatch extends Model
{
    /** @use HasFactory<WalletDepositDispatchFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];
}
