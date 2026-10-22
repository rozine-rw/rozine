<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One deal in an Investor's cart (BRS v2.0 INV-R02): a convenience line, never a financial record.
 *
 * @property string $id
 * @property string $party_id
 * @property string $business_campaign_id
 * @property int $units
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class InvestorCartLine extends Model
{
    use HasUlids;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer', 'created_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime'];
    }
}
