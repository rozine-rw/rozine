<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConsentReleaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Document from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type Disclosure from \App\Domain\Identity\ConsentDocuments
 *
 * @property int $revision
 * @property string $status
 * @property bool $synthetic
 * @property list<Document> $documents
 * @property list<Disclosure> $disclosures
 */
class ConsentRelease extends Model
{
    /** @use HasFactory<ConsentReleaseFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'synthetic' => 'boolean', 'documents' => 'array', 'disclosures' => 'array'];
    }
}
