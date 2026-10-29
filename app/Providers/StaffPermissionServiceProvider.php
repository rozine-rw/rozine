<?php

declare(strict_types=1);

namespace App\Providers;

use Composer\InstalledVersions;
use LogicException;
use Spatie\LaravelPackageTools\Package;
use Spatie\Permission\Commands\CacheResetCommand;
use Spatie\Permission\Commands\ShowCommand;
use Spatie\Permission\PermissionServiceProvider;

/** Staff grants continue through the audited identity boundary, including independent approval. */
class StaffPermissionServiceProvider extends PermissionServiceProvider
{
    public function configurePackage(Package $package): void
    {
        parent::configurePackage($package);
        $package->setBasePath((InstalledVersions::getInstallPath('spatie/laravel-permission')
            ?? throw new LogicException('STAFF_PERMISSION_PACKAGE_REQUIRED')).'/src');
        $package->commands = [CacheResetCommand::class, ShowCommand::class];
    }
}
