<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessExposureReservationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_id
 * @property string $business_application_id
 * @property string $business_application_submission_id
 * @property string $principal
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessExposureReservation extends Model
{
    /** @use HasFactory<BusinessExposureReservationFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['principal' => 'string', 'payload' => 'encrypted:array'];
    }
}
