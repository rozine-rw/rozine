<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessCreditSnapshotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Facts from \App\Domain\Underwriting\BorrowerCreditFacts
 *
 * @property string $business_id
 * @property int $revision
 * @property string $status
 * @property string $source_kind
 * @property string $source_reference
 * @property string $sha256
 * @property Facts|null $facts
 */
class BusinessCreditSnapshot extends Model
{
    /** @use HasFactory<BusinessCreditSnapshotFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['facts', 'source_reference', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'facts' => 'encrypted:array', 'source_reference' => 'encrypted', 'reason' => 'encrypted'];
    }
}
