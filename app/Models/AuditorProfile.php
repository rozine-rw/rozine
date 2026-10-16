<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditorProfileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AccreditationProfile
 *
 * @property string $party_id
 * @property int $revision
 * @property State $state
 */
class AuditorProfile extends Model
{
    /** @use HasFactory<AuditorProfileFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['state'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'state' => 'encrypted:array'];
    }
}
