<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditAssignmentVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Assignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type Candidate from \App\Domain\Auditor\AuditorDispatch
 *
 * @property Assignment $snapshot
 * @property array{business_revision: int, mandate_version: int, candidates: list<Candidate>} $selection_basis
 */
class AuditAssignmentVersion extends Model
{
    /** @use HasFactory<AuditAssignmentVersionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['snapshot', 'selection_basis', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'snapshot' => 'encrypted:array', 'selection_basis' => 'encrypted:array', 'reason' => 'encrypted'];
    }
}
