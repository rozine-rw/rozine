<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type State from \App\Domain\Identity\RoleApplicationCase
 *
 * @property string $id
 * @property string $party_id
 * @property 'business'|'auditor' $role
 * @property int $revision
 * @property 'draft'|'submitted'|'changes_requested'|'approved'|'rejected' $status
 * @property State $state
 * @property string|null $search_name
 * @property string|null $business_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $decided_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RoleApplication extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['state'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'state' => 'encrypted:array', 'submitted_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime'];
    }
}
