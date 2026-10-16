<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\GetRetainedNoteSchedule;
use App\Application\Primary\RetainedNoteSchedule;
use Illuminate\Support\Facades\DB;

/** Opens the transaction `GetRetainedNoteSchedule` reads in; the read itself stays SELECT-only. */
final readonly class TransactionalRetainedNoteSchedules implements RetainedNoteSchedules
{
    public function __construct(private GetRetainedNoteSchedule $schedule) {}

    public function find(string $campaignId): ?RetainedNoteSchedule
    {
        return DB::transaction(fn (): ?RetainedNoteSchedule => $this->schedule->find($campaignId));
    }
}
