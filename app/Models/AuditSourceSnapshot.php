<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditSourceSnapshotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Isolated synthetic source facts bound to one exact accepted assignment. Never a native
 * capture, a D-04 proof or a real Business assertion.
 *
 * @phpstan-import-type Facts from \App\Domain\Auditor\AuditSourceFacts
 *
 * @property string $assignment_id
 * @property string $business_id
 * @property int $assignment_revision
 * @property string $party_id
 * @property int $revision
 * @property string $status
 * @property string $source_kind
 * @property string $source_reference
 * @property string $procedure_version
 * @property string $sha256
 * @property Facts|null $facts
 */
class AuditSourceSnapshot extends Model
{
    /** @use HasFactory<AuditSourceSnapshotFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['facts', 'source_reference', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'assignment_revision' => 'integer', 'facts' => 'encrypted:array',
            'source_reference' => 'encrypted', 'reason' => 'encrypted'];
    }
}
