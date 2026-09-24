<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessApplicationVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-import-type Application from \App\Application\Business\Contracts\BusinessApplicationStore
 *
 * @property string $business_application_id
 * @property int $revision
 * @property Application $snapshot
 */
class BusinessApplicationVersion extends Model
{
    /** @use HasFactory<BusinessApplicationVersionFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'snapshot' => 'array', 'mandate_version' => 'integer'];
    }
}
