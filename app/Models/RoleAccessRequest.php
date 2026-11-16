<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One immutable request for Business or Auditor access, sent by a verified person from the launcher
 * (#616). Staff act on it with the tools that grant the role.
 *
 * @property string $id
 * @property string $party_id
 * @property string $role
 * @property string $organization
 * @property string|null $reference
 * @property string|null $phone
 * @property string|null $note
 * @property string $operation_id
 * @property CarbonImmutable $created_at
 */
class RoleAccessRequest extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
