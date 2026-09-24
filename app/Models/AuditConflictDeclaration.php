<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditConflictDeclarationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_id
 * @property string $party_id
 */
class AuditConflictDeclaration extends Model
{
    /** @use HasFactory<AuditConflictDeclarationFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['kind', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['kind' => 'encrypted', 'reason' => 'encrypted'];
    }
}
