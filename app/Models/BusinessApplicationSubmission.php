<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BusinessApplicationSubmissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $business_application_id
 * @property string $business_application_quote_id
 * @property string $binding_sha256
 * @property string $sha256
 * @property int $revision
 * @property array<string, mixed> $payload
 */
class BusinessApplicationSubmission extends Model
{
    /** @use HasFactory<BusinessApplicationSubmissionFactory> */
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
