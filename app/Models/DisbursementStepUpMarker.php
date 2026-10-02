<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A keyed marker that one accepted authenticator code has minted its one proof.
 *
 * @property int $actor_user_id
 * @property string $marker
 */
class DisbursementStepUpMarker extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['marker'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}
