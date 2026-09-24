<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\IdentityViolation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConfigureStaffAccessCommand extends Command
{
    protected $signature = 'identity:staff {user : Dedicated staff account ID} {--revoke} {--reason= : Required audit reason}';

    protected $description = 'Grant or revoke Admin entry for a dedicated verified staff account with MFA';

    public function handle(ConfigureStaffAccess $action): int
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
