<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only revision of a staff account's link to a verified person. The latest revision
 * is current; a revoked revision carries no digest and resolves nobody.
 *
 * @property string $id
 * @property int $staff_user_id
 * @property int $revision
 * @property string $status
 * @property string|null $identity_digest
 * @property string $evidence_reference
 * @property int $recorded_by
 * @property CarbonImmutable $created_at
 */
class StaffPersonIdentity extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['identity_digest', 'evidence_reference'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
