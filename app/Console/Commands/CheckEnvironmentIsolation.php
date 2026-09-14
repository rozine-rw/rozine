<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Environment\EnvironmentIsolation;
use Illuminate\Console\Command;
use LogicException;

class CheckEnvironmentIsolation extends Command
{
    protected $signature = 'isolation:check {--expect= : Required deployment profile: production, uat or demo}';

    protected $description = 'Verify the non-live resource boundary and deployment target without reading or changing application records';

    /**
     * Execute the console command.
     */
    public function handle(EnvironmentIsolation $isolation): int
    {
        $expected = $this->option('expect');

        try {
            if (! is_string($expected) || ! in_array($expected, ['production', 'uat', 'demo'], true)) {
                throw new LogicException('ISOLATION_EXPECTED_PROFILE_REQUIRED');
            }

            $isolation->assertSafeConfiguration($expected);
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Environment isolation configuration passes for '.$expected.'.');
        $this->line('This does not certify host IAM, provider accounts, real-money activation or demo journey acceptance.');

        return self::SUCCESS;
    }
}
