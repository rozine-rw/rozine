<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BusinessFundingMethodFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A registered Business funding method. Its reference is encrypted and never serialized.
 *
 * @property string $business_id
 * @property string $kind
 * @property string $label
 * @property string $masked
 * @property string $reference
 * @property string $verification_source
 * @property CarbonImmutable|null $verified_at
 * @property CarbonImmutable|null $revoked_at
 */
class BusinessFundingMethod extends Model
{
    /** @use HasFactory<BusinessFundingMethodFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['reference'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['reference' => 'encrypted', 'verified_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }
}
