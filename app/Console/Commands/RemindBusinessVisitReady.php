<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Business\RemindVisitReady;
use Illuminate\Console\Command;

class RemindBusinessVisitReady extends Command
{
    /** @var string */
    protected $signature = 'business:remind-visit-ready';

    /** @var string */
    protected $description = 'Tell Businesses their visit-ready checklist is open at D−14, and remind them of unticked items at D−11';

    public function handle(RemindVisitReady $reminders): int
    {
        $sent = $reminders->handle();
        $this->info('Opened '.$sent['opened'].' visit-ready checklists and sent '.$sent['reminded'].' reminders.');

        return self::SUCCESS;
    }
}
