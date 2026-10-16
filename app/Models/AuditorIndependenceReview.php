<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditorIndependenceReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AuditorIndependence
 *
 * @property string $business_id
 * @property string $party_id
 * @property int $revision
 * @property State $state
 */
class AuditorIndependenceReview extends Model
{
    /** @use HasFactory<AuditorIndependenceReviewFactory> */
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
