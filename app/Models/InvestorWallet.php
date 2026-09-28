<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvestorWalletFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One per Party: the wallet is the lock gate for every balance change.
 *
 * @property string $party_id
 * @property string $currency
 */
class InvestorWallet extends Model
{
    /** @use HasFactory<InvestorWalletFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];
}
