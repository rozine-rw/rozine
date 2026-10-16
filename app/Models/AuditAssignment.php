<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditEngagementState
 *
 * @property string $business_id
 * @property string|null $party_id
 * @property int $revision
 * @property string $status
 * @property State $state
 */
class AuditAssignment extends Model
{
    /** @use HasFactory<AuditAssignmentFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['state'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'state' => 'encrypted:array', 'completed_at' => 'immutable_datetime'];
    }
}
