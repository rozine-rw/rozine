<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditEngagementAcceptanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_engagement_release_id
 * @property int $release_revision
 * @property string $release_sha256
 * @property string $party_id
 * @property array<string, mixed> $payload
 * @property string $sha256
 */
class AuditEngagementAcceptance extends Model
{
    /** @use HasFactory<AuditEngagementAcceptanceFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['release_revision' => 'integer', 'payload' => 'encrypted:array'];
    }
}
