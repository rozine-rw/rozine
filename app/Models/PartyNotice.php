<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $party_id
 * @property string $kind
 * @property string $event_key
 * @property array<string, mixed> $facts
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $read_at
 */
class PartyNotice extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['facts' => 'array', 'created_at' => 'immutable_datetime', 'read_at' => 'immutable_datetime'];
    }
}
