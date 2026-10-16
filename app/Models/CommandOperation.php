<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CommandOperationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $actor_key
 * @property int $actor_user_id
 * @property string $command
 * @property string $request_id
 * @property string $request_hash
 * @property string $target_type
 * @property string $target_id
 * @property array<string, mixed> $result
 */
class CommandOperation extends Model
{
    /** @use HasFactory<CommandOperationFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['result' => 'array', 'retain_until' => 'immutable_datetime'];
    }
}
