<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A single-use staff step-up proof for one approval, stored only as its hash.
 *
 * @property string $purpose
 * @property int $actor_user_id
 * @property string $disbursement_id
 * @property int $revision
 * @property string $amount
 * @property string $destination_sha256
 * @property string $intent_digest
 * @property string $credential_binding
 * @property string $proof_sha256
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 * @property string|null $consumed_operation_id
 */
class DisbursementStepUpProof extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['credential_binding', 'proof_sha256'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'expires_at' => 'immutable_datetime', 'consumed_at' => 'immutable_datetime'];
    }
}
