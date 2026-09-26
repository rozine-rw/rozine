<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\CheckpointTwoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use LogicException;

class PrepareCheckpointTwo extends Command
{
    protected $signature = 'local:checkpoint-two {--otp= : Show the current authenticator code for a C2 account alias, e.g. auditor-seal}';

    protected $description = 'Prepare or inspect the additive, local-only checkpoint 2 manual test pack';

    public function handle(CheckpointTwoSeeder $seeder): int
    {
        try {
            $seeder->assertLocal();
            if ($this->option('otp')) {
                $this->line($seeder->otp($this->option('otp')));

                return self::SUCCESS;
            }
            $pack = $seeder->prepare();
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->components->info('C2 manual test pack ready. Reruns preserve accounts and progress.');
        $this->table(['Account', 'Email', 'MFA'], array_map(fn (array $account): array => [$account['label'], $account['email'], $account['mfa'] ? 'Authenticator' : 'No'], $pack['accounts']));
        $this->line('Synthetic password: '.CheckpointTwoSeeder::PASSWORD);
        $this->line('MFA: php artisan local:checkpoint-two --otp=auditor-seal');
        $this->line('Manifest: '.Storage::disk('local')->path(CheckpointTwoSeeder::MANIFEST));
        foreach ($pack['scenarios'] as $name => $scenario) {
            $this->line($name.': '.$scenario['business_path'].' | '.$scenario['auditor_path']);
        }

        return self::SUCCESS;
    }
}
