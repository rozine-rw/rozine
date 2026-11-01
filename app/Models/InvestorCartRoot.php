<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * The root of an Investor's cart (#383): its revision and the hash of the line snapshot it names.
 *
 * @property string $id
 * @property string $party_id
 * @property int $revision
 * @property string $sha256
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class InvestorCartRoot extends Model
{
    use HasUlids;

    protected $table = 'investor_carts';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision' => 'integer', 'created_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime'];
    }
}
