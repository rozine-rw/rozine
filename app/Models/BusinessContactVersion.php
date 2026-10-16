<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One immutable version of a Business's contact details and registered address, written by a
 * `business.profile.update`; the highest version is the current one.
 *
 * @property string $id
 * @property string $business_id
 * @property int $version
 * @property string $actor_party_id
 * @property string $operation_id
 * @property array{email: string, phone: string, province: string, district: string, sector: string, cell: string, street: string} $payload
 * @property string $sha256
 */
class BusinessContactVersion extends Model
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
