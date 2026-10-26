<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $party_id
 * @property string $version
 * @property string $notice_sha256
 * @property string $sha256
 * @property CarbonImmutable $acknowledged_at
 */
class SandboxNoticeAcknowledgement extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['acknowledged_at' => 'immutable_datetime'];
    }
}
