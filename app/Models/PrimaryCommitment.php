<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrimaryCommitmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $primary_reservation_id
 * @property string $primary_reservation_version_id
 * @property string $operation_id
 * @property CarbonImmutable $confirmed_at
 * @property CarbonImmutable $created_at
 */
class PrimaryCommitment extends Model
{
    /** @use HasFactory<PrimaryCommitmentFactory> */
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
        return ['confirmed_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }
}
