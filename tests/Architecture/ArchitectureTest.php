<?php

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PulseController;
use App\Infrastructure\Pulse\EloquentPulseSignupRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

arch('application symbols follow their PSR-4 path casing')
    ->expect('App')
    ->toBeCasedCorrectly();

arch('release code excludes debug and termination helpers')
    ->expect(['dd', 'dump', 'die', 'var_dump'])
    ->not->toBeUsed();

arch('controllers use the application controller convention')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toExtend(Controller::class)
    ->toHaveSuffix('Controller')
    ->ignoring(Controller::class);

arch('form requests own transport validation')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class)
    ->toHaveSuffix('Request');

arch('eloquent resources only shape authorized output')
    ->expect('App\Http\Resources')
    ->toExtend(JsonResource::class)
    ->toHaveSuffix('Resource')
    ->not->toUse([
        'App\Actions',
        'App\Application',
        'App\Domain',
        'App\Infrastructure',
        'App\Models',
        'App\Support',
    ]);

arch('pulse controller delegates business behavior to the application layer')
    ->expect(PulseController::class)
    ->not->toUse([
        'App\Domain',
        'App\Infrastructure',
        'App\Models',
        'App\Support',
        'Illuminate\Database',
        'Illuminate\Support\Facades\DB',
    ]);

arch('domain code is independent from frameworks and outer layers')
    ->expect('App\Domain')
    ->not->toUse([
        'App\Application',
        'App\Http',
        'App\Infrastructure',
        'App\Models',
        'Illuminate',
        'Inertia',
    ]);

arch('application code depends on contracts and domain behavior only')
    ->expect('App\Application')
    ->not->toUse([
        'App\Http',
        'App\Infrastructure',
        'App\Models',
        'Illuminate\Database',
        'Inertia',
    ]);

arch('infrastructure stays independent from delivery transports')
    ->expect('App\Infrastructure')
    ->not->toUse([
        'App\Http',
        'Inertia',
    ]);

arch('pulse persistence is reached through an application owned contract')
    ->expect(PulseSignupRepository::class)
    ->toBeInterfaces()
    ->and(EloquentPulseSignupRepository::class)
    ->toImplement(PulseSignupRepository::class);
