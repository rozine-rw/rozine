<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StagingMailTesterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $email
 * @property int $added_by_user_id
 * @property Carbon $created_at
 */
class StagingMailTester extends Model
{
    /** @use HasFactory<StagingMailTesterFactory> */
    use HasFactory, HasUlids;

    /** @var list<string> */
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['added_by_user_id' => 'integer'];
    }
}
