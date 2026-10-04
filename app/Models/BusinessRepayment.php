<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One immutable `repayment.pay` from a Business wallet, debited by exactly one ledger entry.
 *
 * @property string $id
 * @property string $wallet_id
 * @property string $business_id
 * @property string $party_id
 * @property string $note_id
 * @property string $operation_id
 * @property string $request_id
 * @property string $option
 * @property string $amount
 * @property int $servicing_revision
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessRepayment extends Model
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
        return ['amount' => 'string', 'servicing_revision' => 'integer', 'payload' => 'encrypted:array'];
    }
}
