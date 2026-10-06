<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\VerifiedAuditLocation
 *
 * @property string|null $office_party_id
 * @property string|null $business_id
 * @property int $revision
 * @property State $state
 */
class AuditLocation extends Model
{
    /** @use HasFactory<AuditLocationFactory> */
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
