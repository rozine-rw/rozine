<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BusinessCampaignClosureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_campaign_id
 * @property string $business_id
 * @property string $exposure_reservation_id
 * @property string $principal
 * @property string $phase
 * @property int|null $actor_user_id
 * @property CarbonImmutable $closed_at
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessCampaignClosure extends Model
{
    /** @use HasFactory<BusinessCampaignClosureFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'principal' => 'string', 'closed_at' => 'immutable_datetime'];
    }
}
