<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| UI fixture previews (local and testing only)
|--------------------------------------------------------------------------
|
| Renders a Phase 1B page from a deterministic fixture in resources/fixtures/ui
| so the UI can be reviewed before its server Resource exists. Fixtures carry
| synthetic data only. These routes are never registered in any other
| environment, and they are deliberately unnamed so Wayfinder never emits a
| client helper that production builds would lack.
|
*/

if (! app()->environment(['local', 'testing'])) {
    return;
}

Route::get('preview/{fixture}', function (string $fixture) {
    $path = resource_path("fixtures/ui/{$fixture}.json");

    abort_unless(is_file($path), 404);

    /** @var array{component: string, props: array<string, mixed>} $page */
    $page = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    return Inertia::render($page['component'], $page['props']);
})->where('fixture', '[a-z0-9-]+');
