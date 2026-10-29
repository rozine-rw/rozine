<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\StaffPermission;
use Database\Factories\StaffAccountFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $user_id
 * @property Collection<int, Role> $roles
 * @property bool $enabled
 */
class StaffAccount extends Model
{
    /** @use HasFactory<StaffAccountFactory> */
    use HasFactory, HasRoles;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var array<string, mixed> */
    protected $attributes = ['enabled' => false];

    protected string $guard_name = 'staff';

    public function delete(): never
    {
        throw new IdentityViolation('STAFF_ACCOUNT_RETAINED', 409);
    }

    /** @return list<string> */
    public function roleNames(): array
    {
        $this->load('roles');

        return array_values($this->roles->map(fn (Role $role): string => $role->name)->sort()->all());
    }

    /**
     * Fresh role grants for the current authorization transaction; direct grants are not staff authority.
     *
     * @return list<string>
     */
    public function allowedActions(): array
    {
        $this->load('roles.permissions');
        StaffPermission::normalizeRoles(array_values($this->roles->map(fn (Role $role): string => $role->name)->all()));

        return array_values(array_unique(['admin.open', ...$this->getPermissionsViaRoles()->pluck('name')->all()]));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
