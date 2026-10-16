<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLedgerOriginalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLedgerOriginal extends Model
{
    /** @use HasFactory<AuditLedgerOriginalFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['content', 'filename'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['content' => 'encrypted', 'filename' => 'encrypted', 'size_bytes' => 'integer', 'report_revision' => 'integer'];
    }
}
