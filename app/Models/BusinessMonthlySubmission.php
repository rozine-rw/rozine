<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Costs from \App\Domain\Business\MonthlySubmission
 * @phpstan-import-type Changes from \App\Domain\Business\MonthlySubmission
 *
 * @property string $id
 * @property string $business_id
 * @property string $period
 * @property string $money_in
 * @property Costs $costs
 * @property string $costs_total
 * @property string $paid_to_owners
 * @property string $forecast_period
 * @property string $forecast
 * @property string|null $forecast_basis
 * @property string|null $order
 * @property Changes $changes
 * @property string $policy_version
 * @property string $submitted_by_party_id
 * @property string $operation_id
 * @property CarbonImmutable $submitted_at
 */
class BusinessMonthlySubmission extends Model
{
    use HasUlids;

    public $timestamps = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['costs' => 'array', 'changes' => 'array', 'submitted_at' => 'immutable_datetime'];
    }
}
