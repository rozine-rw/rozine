<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * The Audit Partner's share of one disbursement's platform fee (BRS v2.0 FEE-R02), bound to the
 * ledger entry that credited it. A credit with no report or party is unattributed.
 *
 * @property string $disbursement_id
 * @property string $ledger_entry_id
 * @property string|null $audit_report_id
 * @property string|null $auditor_party_id
 * @property string $kind
 * @property string $amount
 * @property string $fee_version
 * @property CarbonImmutable $created_at
 */
class AuditPartnerCredit extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount' => 'string', 'created_at' => 'immutable_datetime'];
    }
}
