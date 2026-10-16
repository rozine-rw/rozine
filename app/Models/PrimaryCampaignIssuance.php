<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_id
 * @property string $funding_id
 * @property string $exposure_reservation_id
 * @property string $disbursement_closing_id
 * @property string $principal
 * @property int $commitment_count
 * @property string $holdings_sha256
 * @property string $sha256
 * @property CarbonImmutable $created_at
 * @property array<string, mixed> $payload
 */
class PrimaryCampaignIssuance extends Model
{
    protected $primaryKey = 'business_campaign_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['principal' => 'string', 'commitment_count' => 'integer', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
