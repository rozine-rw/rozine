<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\ReadChanges;
use Illuminate\Console\Command;

/**
 * The only way beacon changes are deleted. It keeps at least the cursor lifetime (24 hours) and
 * each subject's latest revision; the database itself refuses any younger row.
 */
class PruneChangeFeed extends Command
{
    protected $signature = 'changes:prune {--hours=48 : Retention in hours, at least the 24-hour cursor lifetime}';

    protected $description = 'Delete beacon changes older than the retention, keeping each subject\'s latest';

    public function handle(ChangeFeed $changes): int
    {
        $minimum = intdiv(ReadChanges::CURSOR_TTL_SECONDS, 3600);
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum, 'max_range' => 8760]]);
        if ($hours === false) {
            $this->error("The retention must be an integer from {$minimum} to 8760 hours.");

            return self::INVALID;
        }
        $this->info('Pruned '.$changes->prune($hours).' changes.');

        return self::SUCCESS;
    }
}
