<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VerifiedPersonIdentityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @property string $party_id */
class VerifiedPersonIdentity extends Model
{
    /** @use HasFactory<VerifiedPersonIdentityFactory> */
    use HasFactory;

    protected $primaryKey = 'identity_digest';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['identity_digest', 'evidence_reference'];
}
