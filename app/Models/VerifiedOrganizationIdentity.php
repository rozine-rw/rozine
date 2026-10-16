<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VerifiedOrganizationIdentityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @property string $party_id */
class VerifiedOrganizationIdentity extends Model
{
    /** @use HasFactory<VerifiedOrganizationIdentityFactory> */
    use HasFactory;

    protected $primaryKey = 'registry_digest';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @var list<string> */
    protected $hidden = ['registry_digest', 'evidence_reference'];
}
