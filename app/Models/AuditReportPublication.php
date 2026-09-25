<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditReportPublicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_report_id
 * @property string $business_id
 * @property int $mandate_version
 * @property string $digest
 * @property int $report_revision
 * @property int $revision
 * @property string $status
 * @property CarbonImmutable|null $published_at
 */
class AuditReportPublication extends Model
{
    /** @use HasFactory<AuditReportPublicationFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['mandate_version' => 'integer', 'report_revision' => 'integer', 'revision' => 'integer', 'published_at' => 'immutable_datetime'];
    }
}
