<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A staff member's signed statement, made with their authorize or approve command, that they have
 * no relationship with the disbursement's Business or its Investors.
 *
 * @property string $id
 * @property string $disbursement_id
 * @property int $staff_user_id
 * @property string|null $staff_person_identity_id
 * @property string $command
 * @property string $operation_id
 * @property string $statement_version
 * @property string $statement_sha256
 * @property CarbonImmutable $declared_at
 */
class DisbursementIndependenceDeclaration extends Model
{
    use HasUlids;

    public $timestamps = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['declared_at' => 'immutable_datetime'];
    }
}
