<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BusinessCampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $live_at
 * @property CarbonImmutable $expires_at
 * @property string $principal
 * @property string $business_application_release_id
 * @property string $business_id
 * @property string $business_application_id
 * @property string $exposure_reservation_id
 * @property int $actor_user_id
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessCampaign extends Model
{
    /** @use HasFactory<BusinessCampaignFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'principal' => 'string', 'live_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
        ];
    }
}
