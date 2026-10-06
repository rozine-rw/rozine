<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only step in a disbursement's life: its revision is its position in the log.
 *
 * @property string $disbursement_id
 * @property int $revision
 * @property string $kind
 * @property int|null $actor_user_id
 * @property string|null $operation_id
 * @property string|null $request_id
 * @property string|null $binding_sha256
 * @property string|null $destination_sha256
 * @property string $sha256
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class DisbursementEvent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
