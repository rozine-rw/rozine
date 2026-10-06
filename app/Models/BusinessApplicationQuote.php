<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessApplicationQuoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_application_id
 * @property int $revision
 * @property string $sha256
 * @property array<string, mixed> $payload
 */
class BusinessApplicationQuote extends Model
{
    /** @use HasFactory<BusinessApplicationQuoteFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'payload' => 'encrypted:array'];
    }
}
