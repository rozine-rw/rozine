<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One immutable version of a Business's prospectus for an accepted application: a `draft` save, or
 * the one `signed` version the official representative locked under its fingerprint.
 *
 * @phpstan-import-type Inputs from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type Facts from \App\Domain\Business\ProspectusGates
 *
 * @property string $id
 * @property string $business_id
 * @property string $business_application_id
 * @property int $version
 * @property string $kind
 * @property string $actor_party_id
 * @property string $operation_id
 * @property array{inputs: Inputs, document?: array{facts: Facts}&array<string, mixed>} $payload
 * @property string $sha256
 * @property string|null $fingerprint
 * @property CarbonImmutable $created_at
 */
class BusinessProspectusVersion extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'payload' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }
}
