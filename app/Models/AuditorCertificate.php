<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditorCertificateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditorCertificate extends Model
{
    /** @use HasFactory<AuditorCertificateFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['filename', 'content'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['filename' => 'encrypted', 'content' => 'encrypted', 'size_bytes' => 'integer'];
    }
}
