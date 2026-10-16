<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\IdentityViolation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConfigureStaffAccessCommand extends Command
{
    protected $signature = 'identity:staff {user : Dedicated staff account ID} {--revoke} {--role=* : Explicit staff role, repeatable} {--reason= : Required audit reason}';

    protected $description = 'Grant or revoke scoped staff access for a dedicated verified account with MFA';

    public function handle(ConfigureStaffAccess $action): int
    {
        try {
            /** @var list<string> $roles */
            $roles = $this->option('role');
            $result = $action->handle((int) $this->argument('user'), ! $this->option('revoke'), (string) $this->option('reason'), (string) Str::uuid(), $roles);
        } catch (IdentityViolation $exception) {
            $this->components->error($exception->reason);

            return self::FAILURE;
        }

        $this->components->info((string) $result['code']);

        return self::SUCCESS;
    }
}
