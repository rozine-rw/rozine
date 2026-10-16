<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One audited grant or revocation of a Party's UAT synthetic deposits, at the Party's next revision.
 * Append-only by trigger.
 *
 * @property string $id
 * @property string $party_id
 * @property int $user_id
 * @property int $revision
 * @property string $action
 * @property string $reason
 * @property string $actor_key
 * @property string $request_id
 * @property CarbonImmutable $created_at
 */
class SyntheticWalletTesterChange extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['user_id' => 'integer', 'revision' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
