<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditStepUpProofFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_user_id
 * @property string $actor_party_id
 * @property int $identity_context_revision
 * @property string $audit_report_id
 * @property int $report_revision
 * @property string $digest
 * @property string $credential_binding
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 */
class AuditStepUpProof extends Model
{
    /** @use HasFactory<AuditStepUpProofFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['proof_sha256', 'credential_binding'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['actor_user_id' => 'integer', 'identity_context_revision' => 'integer', 'report_revision' => 'integer', 'expires_at' => 'immutable_datetime', 'consumed_at' => 'immutable_datetime'];
    }
}
