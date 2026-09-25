<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditReportSealFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_report_id
 * @property int $report_revision
 * @property string $audit_signing_key_id
 * @property string $step_up_proof_id
 * @property string $author_party_id
 * @property int $actor_user_id
 * @property string $digest
 * @property array<string, mixed> $payload
 * @property string $jws
 * @property CarbonImmutable $created_at
 */
class AuditReportSeal extends Model
{
    /** @use HasFactory<AuditReportSealFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload', 'jws'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['report_revision' => 'integer', 'actor_user_id' => 'integer', 'payload' => 'encrypted:array', 'jws' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }
}
