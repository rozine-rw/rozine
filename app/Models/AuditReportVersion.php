<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditReportVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_report_id
 * @property int $revision
 * @property string $status
 * @property string $step
 * @property array<string, mixed> $snapshot
 * @property string $sha256
 * @property string $actor_party_id
 * @property int $actor_user_id
 * @property string $command
 */
class AuditReportVersion extends Model
{
    /** @use HasFactory<AuditReportVersionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['snapshot'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'actor_user_id' => 'integer', 'snapshot' => 'encrypted:array'];
    }
}
