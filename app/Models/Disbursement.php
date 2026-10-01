<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DisbursementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One funded campaign's payout to its Business: the immutable funding snapshot a disbursement is authorized, approved and dispatched against.
 *
 * @property string $business_campaign_id
 * @property string $business_id
 * @property string $exposure_reservation_id
 * @property string $amount
 * @property string $currency
 * @property string $commitments_digest
 * @property int $commitment_count
 * @property int $term_months
 * @property CarbonImmutable $funded_at
 * @property string $environment
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class Disbursement extends Model
{
    /** @use HasFactory<DisbursementFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'funded_at' => 'immutable_datetime', 'payload' => 'encrypted:array'];
    }
}
