<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Environment\ResetDemoFixtures;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use LogicException;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset {--confirm= : Must equal pulse-foundation-v1; resets only that synthetic fixture set}';

    protected $description = 'Restore reserved synthetic Pulse foundation fixtures in a dedicated demo database only';

    /**
     * Execute the console command.
     */
    public function handle(ResetDemoFixtures $reset): int
    {
        if ($this->option('confirm') !== ResetDemoFixtures::VERSION) {
            $this->components->error('DEMO_FIXTURE_CONFIRMATION_REQUIRED');

            return self::FAILURE;
        }

        try {
            $count = $reset->handle();
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (QueryException) {
            $this->components->error('DEMO_FIXTURE_DATABASE_REJECTED: no fixture changes committed.');

            return self::FAILURE;
        }

        $this->components->info('Restored '.$count.' synthetic fixtures ('.ResetDemoFixtures::VERSION.').');
        $this->line('Other records, accounts, files, queues and environments were not reset. No real-money activity is enabled.');

        return self::SUCCESS;
    }
}
