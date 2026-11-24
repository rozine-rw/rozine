<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Code from \App\Domain\Business\ReadinessRequirements
 * @phpstan-import-type Status from \App\Domain\Business\ReadinessRequirements
 *
 * @property string $id
 * @property string $business_id
 * @property Code $code
 * @property Status $status
 * @property CarbonImmutable $triggered_on
 * @property CarbonImmutable|null $due_on
 * @property CarbonImmutable|null $met_on
 * @property array<string, mixed>|null $confirmation
 * @property string $policy_version
 * @property int $revision
 */
class BusinessReadinessRequirement extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['triggered_on' => 'immutable_date', 'due_on' => 'immutable_date', 'met_on' => 'immutable_date', 'confirmation' => 'array', 'revision' => 'integer'];
    }
}
