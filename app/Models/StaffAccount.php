<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StaffAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property bool $enabled
 * @property list<string> $roles
 */
class StaffAccount extends Model
{
    /** @use HasFactory<StaffAccountFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var array<string, mixed> */
    protected $attributes = ['enabled' => false, 'roles' => '[]'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'roles' => 'array'];
    }
}
