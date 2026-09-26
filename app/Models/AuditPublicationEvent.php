<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditPublicationEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed> $payload
 * @property string $sha256
 * @property int $publication_revision
 */
class AuditPublicationEvent extends Model
{
    /** @use HasFactory<AuditPublicationEventFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'publication_revision' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
