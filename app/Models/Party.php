<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $kind
 * @property Carbon|null $verified_at
 * @property-read Collection<int, RoleMembership> $memberships
 * @property-read VerifiedPersonIdentity|null $verifiedIdentity
 */
#[Fillable(['kind'])]
class Party extends Model
{
    /** @return HasOne<VerifiedPersonIdentity, $this> */
    public function verifiedIdentity(): HasOne
    {
        return $this->hasOne(VerifiedPersonIdentity::class);
    }

    /** @use HasFactory<PartyFactory> */
    use HasFactory, HasUlids;

    /** @var array<string, mixed> */
    protected $attributes = ['kind' => 'person'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    /** @return HasMany<RoleMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(RoleMembership::class);
    }
}
