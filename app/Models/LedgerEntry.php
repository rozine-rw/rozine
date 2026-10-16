<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An immutable, balanced journal entry. Its lines commit with it or never.
 *
 * @property string $wallet_id
 * @property string $kind
 * @property string $source_type
 * @property string $source_id
 * @property string|null $origin_operation_id
 * @property string|null $cause_type
 * @property string|null $cause_id
 * @property string $currency
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
