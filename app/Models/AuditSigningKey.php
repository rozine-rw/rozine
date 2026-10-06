<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AuditSigningKeyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed> $private_jwk
 * @property array<string, mixed> $public_jwk
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable $rotate_at
 */
class AuditSigningKey extends Model
{
    /** @use HasFactory<AuditSigningKeyFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['private_jwk'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['private_jwk' => 'encrypted:array', 'public_jwk' => 'array', 'valid_from' => 'immutable_datetime', 'rotate_at' => 'immutable_datetime'];
    }
}
