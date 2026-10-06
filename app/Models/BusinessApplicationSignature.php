<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessApplicationSignatureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_application_id
 * @property string $business_application_quote_id
 * @property string $consent_release_id
 * @property string $actor_party_id
 * @property string $binding_sha256
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessApplicationSignature extends Model
{
    /** @use HasFactory<BusinessApplicationSignatureFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
