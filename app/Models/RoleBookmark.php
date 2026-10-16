<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoleBookmarkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $user_id
 * @property string $role
 * @property string $membership_id
 * @property int $membership_revision
 * @property string $route
 * @property array<string, mixed> $parameters
 * @property array<string, mixed> $query
 */
class RoleBookmark extends Model
{
    /** @use HasFactory<RoleBookmarkFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['parameters' => 'array', 'query' => 'array', 'membership_revision' => 'integer'];
    }
}
