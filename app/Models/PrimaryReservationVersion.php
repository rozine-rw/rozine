<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrimaryReservationVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $primary_reservation_id
 * @property int $revision
 * @property string $state
 * @property string|null $operation_id
 * @property string $sha256
 * @property string|null $previous_sha256
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $created_at
 */
class PrimaryReservationVersion extends Model
{
    /** @use HasFactory<PrimaryReservationVersionFactory> */
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
        return ['revision' => 'integer', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
