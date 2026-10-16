<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Drives the middleware directly so each negotiation case is asserted in isolation, then proves the
 * negotiated result actually reaches the browser through both channels the client reads.
 */
function negotiate(?string $acceptLanguage): string
{
    $request = Request::create('/');

    if ($acceptLanguage !== null) {
        $request->headers->set('Accept-Language', $acceptLanguage);
    }

    (new SetLocale)->handle($request, fn (): Response => new Response);

    return app()->getLocale();
}

test('an unstated preference falls back to the configured application locale', function () {
    config()->set('app.locale', 'en');

    expect(negotiate(null))->toBe('en');
});

test('a supported language is honoured exactly', function (string $header, string $expected) {
    expect(negotiate($header))->toBe($expected);
})->with([
    'french' => ['fr', 'fr'],
    'kinyarwanda' => ['rw', 'rw'],
    'english' => ['en', 'en'],
]);

test('a regional variant resolves to its base language rather than to the default', function () {
    expect(negotiate('fr-RW'))->toBe('fr')
        ->and(negotiate('rw-RW'))->toBe('rw');
});

test('an unsupported language falls back rather than serving an absent catalog', function () {
    config()->set('app.locale', 'en');

    expect(negotiate('de-DE'))->toBe('en');
});

test('the first supported language in a weighted list wins', function () {
    expect(negotiate('de-DE,fr;q=0.9,en;q=0.8'))->toBe('fr');
});

test('every negotiable locale has a client catalog to render it', function () {
    foreach (config('i18n.supported') as $locale) {
        expect(resource_path("js/lib/i18n/catalogs/{$locale}.ts"))->toBeFile();
    }
});

test('the negotiated locale reaches the browser through the document and the page props', function () {
    $response = $this->withHeader('Accept-Language', 'fr')->get('/login');

    $response->assertOk();
    $response->assertSee('<html lang="fr"', false);
    $response->assertInertia(fn ($page) => $page->where('locale', 'fr'));
});
