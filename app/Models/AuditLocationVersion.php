<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLocationVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Location from \App\Application\Auditor\Contracts\AuditLocationStore
 *
 * @property Location $snapshot
 */
class AuditLocationVersion extends Model
{
    /** @use HasFactory<AuditLocationVersionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['snapshot', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'snapshot' => 'encrypted:array', 'reason' => 'encrypted'];
    }
}
