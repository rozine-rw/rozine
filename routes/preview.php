<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\RozineMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

/*
| Mail templates render the same way: every Rozine email, filled from the
| synthetic resources/fixtures/mail.php and addressed to an unsaved account.
*/

Route::get('preview/mail', function (): string {
    /** @var array<string, Closure(): (RozineMail|VerifyEmail|ResetPassword)> $previews */
    $previews = require resource_path('fixtures/mail.php');

    $links = array_map(
        fn (string $template): string => '<li><a href="'.e(url("preview/mail/{$template}")).'">'.e($template).'</a></li>',
        array_keys($previews),
    );

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Rozine mail previews</title></head><body><ul>'.implode('', $links).'</ul></body></html>';
});

Route::get('preview/mail/{template}', function (string $template): MailMessage {
    /** @var array<string, Closure(): (RozineMail|VerifyEmail|ResetPassword)> $previews */
    $previews = require resource_path('fixtures/mail.php');

    abort_unless(isset($previews[$template]), 404);

    $recipient = (new User)->forceFill(['id' => 1, 'name' => 'Aline Uwase', 'email' => 'aline.uwase@example.test']);

    return $previews[$template]()->toMail($recipient);
})->where('template', '[a-z0-9.-]+');

Route::get('preview/{fixture}', function (string $fixture) {
    $path = resource_path("fixtures/ui/{$fixture}.json");

    abort_unless(is_file($path), 404);

    /** @var array{component: string, props: array<string, mixed>} $page */
    $page = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    return Inertia::render($page['component'], $page['props']);
})->where('fixture', '[a-z0-9-]+');
