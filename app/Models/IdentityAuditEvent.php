<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\IdentityAuditEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|null $actor_user_id
 * @property string $reason
 * @property string $policy_version
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 * @property string $request_hash
 * @property array<string, mixed> $result
 */
class IdentityAuditEvent extends Model
{
    /** @use HasFactory<IdentityAuditEventFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'result' => 'array'];
    }
}
