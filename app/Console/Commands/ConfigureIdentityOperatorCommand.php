<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Identity\ConfigureIdentityOperator;
use App\Domain\Identity\IdentityViolation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConfigureIdentityOperatorCommand extends Command
{
    protected $signature = 'identity:operator {user : Dedicated staff account ID} {--revoke} {--reason= : Required audit reason}';

    protected $description = 'Grant or revoke identity administration for a dedicated verified staff account with MFA';

    public function handle(ConfigureIdentityOperator $action): int
    {
        try {
            $result = $action->handle((int) $this->argument('user'), ! $this->option('revoke'), (string) $this->option('reason'), (string) Str::uuid());
        } catch (IdentityViolation $exception) {
            $this->components->error($exception->reason);

            return self::FAILURE;
        }

        $this->components->info((string) $result['code']);

        return self::SUCCESS;
    }
}
