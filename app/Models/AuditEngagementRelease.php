<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditEngagementReleaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Documents from \App\Domain\Auditor\AuditEngagementDocuments
 *
 * @property int $revision
 * @property string $status
 * @property string|null $version
 * @property string $procedure_version
 * @property Documents|array{} $documents
 * @property string $sha256
 * @property bool $synthetic
 */
class AuditEngagementRelease extends Model
{
    /** @use HasFactory<AuditEngagementReleaseFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['documents', 'approval_reference', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'documents' => 'encrypted:array', 'synthetic' => 'boolean',
            'approval_reference' => 'encrypted', 'reason' => 'encrypted'];
    }
}
