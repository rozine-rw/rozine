<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLedgerExtractionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @property list<string> $reason_codes */
class AuditLedgerExtraction extends Model
{
    /** @use HasFactory<AuditLedgerExtractionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['text'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['text' => 'encrypted', 'reason_codes' => 'array', 'revision' => 'integer', 'record_count' => 'integer'];
    }
}
