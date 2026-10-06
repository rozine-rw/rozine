<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditSigningKeyRevocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $audit_signing_key_id
 * @property string $reason
 */
class AuditSigningKeyRevocation extends Model
{
    /** @use HasFactory<AuditSigningKeyRevocationFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['reason' => 'encrypted'];
    }
}
