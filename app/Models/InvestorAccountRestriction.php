<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\InvestorAccountRestrictionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only synthetic account case with its own cause, scope and window.
 *
 * @property string $party_id
 * @property string $cause
 * @property list<string> $scope
 * @property string $source
 * @property CarbonImmutable $effective_at
 * @property CarbonImmutable|null $expires_at
 */
class InvestorAccountRestriction extends Model
{
    /** @use HasFactory<InvestorAccountRestrictionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['scope' => 'array', 'effective_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }
}
