<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\IdentityOperatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property bool $enabled
 */
class IdentityOperator extends Model
{
    /** @use HasFactory<IdentityOperatorFactory> */
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var array<string, mixed> */
    protected $attributes = ['enabled' => false];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
