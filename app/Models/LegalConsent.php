<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One person's agreement to one exact wording of the Terms, the Privacy Notice or the Disclaimers.
 *
 * @property string $id
 * @property string $party_id
 * @property string $document
 * @property string $version
 * @property string $document_sha256
 * @property string $source
 * @property string $sha256
 * @property CarbonImmutable $accepted_at
 */
class LegalConsent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['accepted_at' => 'immutable_datetime'];
    }
}
