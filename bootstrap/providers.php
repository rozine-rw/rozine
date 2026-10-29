<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EnvironmentSafetyServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\StaffPermissionServiceProvider;

return [
    EnvironmentSafetyServiceProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    StaffPermissionServiceProvider::class,
];
