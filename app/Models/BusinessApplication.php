<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessApplicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Fields from \App\Domain\Business\ApplicationDraft
 *
 * @property string $business_id
 * @property int $revision
 * @property string $status
 * @property string $step
 * @property Fields $draft
 * @property int $mandate_version
 * @property string|null $current_quote_id
 * @property string|null $current_submission_id
 */
class BusinessApplication extends Model
{
    /** @use HasFactory<BusinessApplicationFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'draft' => 'array', 'mandate_version' => 'integer'];
    }
}
