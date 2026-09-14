<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EnvironmentSafetyServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    EnvironmentSafetyServiceProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
];
