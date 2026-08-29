<?php

use App\Http\Controllers\Controller;
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
        'App\Infrastructure',
        'App\Support',
    ]);

arch('support calculations stay independent from transports')
    ->expect('App\Support')
    ->not->toUse([
        'App\Http',
        'Illuminate\Http',
        'Inertia',
    ]);
