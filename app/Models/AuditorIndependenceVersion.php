<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditorIndependenceVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Review from \App\Application\Auditor\Contracts\AuditorIndependenceStore
 *
 * @property Review $snapshot
 */
class AuditorIndependenceVersion extends Model
{
    /** @use HasFactory<AuditorIndependenceVersionFactory> */
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
