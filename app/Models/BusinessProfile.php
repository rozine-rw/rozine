<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 *
 * @property string $entity_kind
 * @property string $entity_party_id
 * @property Profile $profile
 * @property int $revision
 * @property int $mandate_version
 */
class BusinessProfile extends Model
{
    /** @use HasFactory<BusinessProfileFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['profile' => 'array', 'revision' => 'integer', 'mandate_version' => 'integer'];
    }
}
