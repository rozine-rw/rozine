<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $business_id
 * @property string $module
 * @property int $attempt
 * @property list<int> $questions
 * @property array<int, int> $answers
 * @property int $score
 * @property bool $passed
 * @property string $policy_version
 * @property string $submitted_by_party_id
 * @property string $operation_id
 * @property CarbonImmutable $submitted_at
 */
class BusinessAcademyAttempt extends Model
{
    use HasUlids;

    public $timestamps = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['questions' => 'array', 'answers' => 'array', 'attempt' => 'integer', 'score' => 'integer', 'passed' => 'boolean',
            'submitted_at' => 'immutable_datetime'];
    }
}
