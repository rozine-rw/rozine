<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type State from \App\Domain\Identity\InvestorVerificationCase
 *
 * @property string $id
 * @property string $party_id
 * @property int $revision
 * @property 'draft'|'submitted'|'approved'|'rejected' $status
 * @property State $state
 * @property Carbon|null $submitted_at
 */
class InvestorVerification extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['state'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'state' => 'encrypted:array', 'submitted_at' => 'immutable_datetime'];
    }
}
