<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The institution an Investor account invests for, recorded when Compliance approves the institution's
 * KYB (BRS v3 KYC-R02, 26 Nov 2026).
 *
 * @property string $id
 * @property string $party_id
 * @property string $role_application_id
 * @property string $legal_name
 * @property string $rdb_code
 * @property string $institution_type
 * @property int $monthly_commitment
 * @property int $approved_by_user_id
 * @property Carbon $approved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class InstitutionalInvestor extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['monthly_commitment' => 'integer', 'approved_by_user_id' => 'integer', 'approved_at' => 'immutable_datetime'];
    }
}
