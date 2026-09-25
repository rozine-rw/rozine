<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $assignment_id
 * @property string $business_id
 * @property string $application_id
 * @property int $application_revision
 * @property string $application_version_id
 * @property string $submission_id
 * @property string $quote_id
 * @property string|null $amends_id
 * @property int $revision
 * @property string $kind
 * @property string $status
 * @property string $step
 * @property array<string, mixed> $binding
 * @property string $binding_sha256
 * @property array<string, mixed> $draft
 */
class AuditReport extends Model
{
    /** @use HasFactory<AuditReportFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['binding', 'draft'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'application_revision' => 'integer', 'binding' => 'encrypted:array', 'draft' => 'encrypted:array'];
    }
}
