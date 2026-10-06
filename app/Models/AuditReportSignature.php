<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditReportSignatureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_report_publication_id
 * @property int $publication_revision
 * @property string $actor_party_id
 * @property int $actor_user_id
 * @property array<string, mixed> $payload
 * @property string $sha256
 * @property CarbonImmutable $created_at
 */
class AuditReportSignature extends Model
{
    /** @use HasFactory<AuditReportSignatureFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publication_revision' => 'integer', 'actor_user_id' => 'integer', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
