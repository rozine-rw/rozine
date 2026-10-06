<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessMandateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 *
 * @property string $business_id
 * @property int $version
 * @property Terms $terms
 * @property Profile $profile
 */
class BusinessMandate extends Model
{
    /** @use HasFactory<BusinessMandateFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'terms' => 'array', 'profile' => 'array'];
    }
}
