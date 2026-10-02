<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * PROPOSED for S3-C review: an issued Holding, written only by the FundedCampaigns adapter. It
 * copies `HoldingSource::facts` of its retained commitment; the database compares the plaintext
 * facts and the digest pins, and `HoldingSource::verify` compares the decrypted rights and terms.
 *
 * @property string $business_campaign_id
 * @property string $commitment_id
 * @property string $primary_reservation_id
 * @property string $reservation_sha256
 * @property string $confirmation_version_id
 * @property int $confirmation_revision
 * @property string $confirmation_sha256
 * @property string $party_id
 * @property string $disbursement_closing_id
 * @property int $units
 * @property string $principal
 * @property list<array{first: int, last: int}> $ordinals
 * @property array<string, mixed> $rights
 * @property array<string, mixed> $terms
 * @property list<array<string, mixed>> $schedule
 * @property CarbonImmutable $issued_at
 * @property CarbonImmutable $disbursement_effective_at
 * @property CarbonImmutable $effective_date
 * @property string $receipt_id
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class PrimaryHolding extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['principal' => 'string', 'ordinals' => 'array', 'rights' => 'array', 'terms' => 'array', 'schedule' => 'array', 'issued_at' => 'immutable_datetime', 'disbursement_effective_at' => 'immutable_datetime', 'effective_date' => 'immutable_date', 'payload' => 'encrypted:array'];
    }
}
