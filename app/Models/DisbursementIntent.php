<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * The durable payout intent and its provider identity, recorded before any send.
 *
 * @property string $disbursement_id
 * @property string $operation_id
 * @property string $request_id
 * @property int $revision
 * @property string $amount
 * @property string $currency
 * @property string $destination_id
 * @property int $destination_revision
 * @property string $destination_sha256
 * @property string $commitments_digest
 * @property string $binding_sha256
 * @property string $intent_digest
 * @property string $provider
 * @property string $provider_reference
 * @property string $provider_reference_sha256
 * @property string $environment
 * @property bool $idempotent_sends
 * @property int $maker_user_id
 * @property int $checker_user_id
 * @property string $sha256
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class DisbursementIntent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['provider_reference', 'payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'idempotent_sends' => 'boolean', 'provider_reference' => 'encrypted', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
