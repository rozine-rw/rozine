<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DepositPolicyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A versioned deposit policy. S3-B rows are synthetic by database constraint.
 *
 * @property string $version
 * @property bool $synthetic
 * @property string $status
 * @property string|null $fee
 * @property string|null $minimum
 * @property string|null $maximum
 * @property CarbonImmutable $effective_at
 */
class DepositPolicy extends Model
{
    /** @use HasFactory<DepositPolicyFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['synthetic' => 'boolean', 'fee' => 'string', 'minimum' => 'string', 'maximum' => 'string', 'effective_at' => 'immutable_datetime'];
    }
}
