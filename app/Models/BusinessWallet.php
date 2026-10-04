<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessWalletFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One per Business: the wallet is the lock gate for every Business balance change.
 *
 * @property string $id
 * @property string $business_id
 * @property string $currency
 */
class BusinessWallet extends Model
{
    /** @use HasFactory<BusinessWalletFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];
}
