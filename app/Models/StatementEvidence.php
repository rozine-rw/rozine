<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StatementEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @property int $revision */
class StatementEvidence extends Model
{
    /** @use HasFactory<StatementEvidenceFactory> */
    use HasFactory, HasUlids;

    protected $table = 'statement_evidence';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }
}
