<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrimaryCampaignFundingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_campaign_id
 * @property string $business_id
 * @property string $exposure_reservation_id
 * @property string $publication_sha256
 * @property string $principal
 * @property string $sha256
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class PrimaryCampaignFunding extends Model
{
    /** @use HasFactory<PrimaryCampaignFundingFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['principal' => 'string', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
