<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $role_application_id
 * @property int $revision
 * @property string $status
 * @property array<string, mixed> $snapshot
 * @property int $actor_user_id
 * @property string $command
 * @property string|null $reason
 * @property Carbon $created_at
 */
class RoleApplicationVersion extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['snapshot', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'snapshot' => 'encrypted:array', 'reason' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }
}
