<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\CheckpointThreeSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use LogicException;

class PrepareCheckpointThree extends Command
{
    protected $hidden = true;

    protected $signature = 'local:checkpoint-three {--otp= : Show the current authenticator code for a C3 account alias, e.g. auditor-funded}';

    protected $description = 'Prepare or inspect the additive, local-only checkpoint 3 manual test pack';

    public function handle(CheckpointThreeSeeder $seeder): int
    {
        try {
            if ($this->option('otp')) {
                $this->line($seeder->otp($this->option('otp')));

                return self::SUCCESS;
            }
            $pack = $seeder->prepare();
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->components->info('C3 manual test pack ready. Reruns preserve accounts and progress.');
        $this->table(['Account', 'Email', 'MFA'], array_map(fn (array $account): array => [$account['label'], $account['email'], $account['mfa'] ? 'Authenticator' : 'No'], $pack['accounts']));
        $this->line('Synthetic password: '.CheckpointThreeSeeder::PASSWORD);
        $this->line('MFA: php artisan local:checkpoint-three --otp=auditor-funded');
        $this->line('Manifest: '.Storage::disk('local')->path(CheckpointThreeSeeder::MANIFEST));
        foreach ($pack['scenarios'] as $name => $scenario) {
            $this->line($name.': '.$scenario['business_path']);
        }

        return self::SUCCESS;
    }
}
