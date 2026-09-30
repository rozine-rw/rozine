<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Primary\ExpireReservations;
use Illuminate\Console\Command;

class ExpirePrimaryReservations extends Command
{
    protected $signature = 'primary:expire-reservations {--limit=100 : Maximum overdue holds to examine (1-1000)}';

    protected $description = 'Expire overdue Primary holds and return their retained principal';

    public function handle(ExpireReservations $reservations): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 1000.');

            return self::INVALID;
        }
        $this->info('Expired '.$reservations->handle($limit).' reservations.');

        return self::SUCCESS;
    }
}
