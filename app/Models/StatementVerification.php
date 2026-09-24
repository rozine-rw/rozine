<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StatementVerificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type VerificationPayload from \App\Application\Evidence\Contracts\StatementStore
 *
 * @property VerificationPayload $payload
 */
class StatementVerification extends Model
{
    /** @use HasFactory<StatementVerificationFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'revision' => 'integer', 'source_revision' => 'integer'];
    }
}
