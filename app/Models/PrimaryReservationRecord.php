<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrimaryReservationRecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_campaign_id
 * @property string $party_id
 * @property string $origin_operation_id
 * @property string $publication_sha256
 * @property string $principal
 * @property int $units
 * @property CarbonImmutable $expires_at
 * @property array<string, mixed> $payload
 * @property string $sha256
 * @property CarbonImmutable $created_at
 */
class PrimaryReservationRecord extends Model
{
    /** @use HasFactory<PrimaryReservationRecordFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'primary_reservations';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['principal' => 'string', 'units' => 'integer', 'expires_at' => 'immutable_datetime', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
