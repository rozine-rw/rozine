<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only provider-dispatch outbox phase for one Business withdrawal.
 *
 * @property string $withdrawal_id
 * @property string $phase
 */
class BusinessWithdrawalDispatch extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];
}
