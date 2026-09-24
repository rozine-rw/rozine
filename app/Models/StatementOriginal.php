<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StatementOriginalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementOriginal extends Model
{
    /** @use HasFactory<StatementOriginalFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['content'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['content' => 'encrypted', 'size_bytes' => 'integer', 'evidence_revision' => 'integer'];
    }
}
