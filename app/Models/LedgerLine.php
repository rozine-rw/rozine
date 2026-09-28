<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LedgerLineFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One positive debit or credit on one account within a journal entry.
 *
 * @property string $entry_id
 * @property string $account_id
 * @property string $direction
 * @property string $amount
 */
class LedgerLine extends Model
{
    /** @use HasFactory<LedgerLineFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string'];
    }
}
