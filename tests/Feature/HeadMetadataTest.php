<?php

use App\Providers\AppServiceProvider;

test('the home page renders SEO and social head metadata', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('name="description"', false)
        ->assertSee('Earn up to 18% lending to profitable Rwandan businesses. Buy 3 to 6 month debt notes in audited private businesses, from just RWF 5,000.', false)
        ->assertSee('name="author"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('og:type', false)
        ->assertSee('og:site_name', false)
        ->assertSee('og:title', false)
        ->assertSee('og:image', false)
        ->assertSee('og-image.png', false)
        ->assertSee('og:image:width', false)
        ->assertSee('og:locale', false)
        ->assertSee('twitter:card', false)
        ->assertSee('summary_large_image', false)
        ->assertSee('/favicon.svg', false)
        ->assertSee('/apple-touch-icon.png', false)
        ->assertSee('rel="manifest"', false)
        ->assertSee('/site.webmanifest', false)
        ->assertSee('name="theme-color"', false);
});

test('the site is not indexable outside production', function () {
    $this->get(route('home'))->assertSee('content="noindex, nofollow"', false);
});

test('the site is indexable in production', function () {
    // Head defaults are evaluated when the provider boots, so re-boot under the
    // production environment to exercise the indexable robots branch.
    app()->detectEnvironment(fn () => 'production');
    (new AppServiceProvider(app()))->boot();

    $this->get(route('home'))->assertSee('content="index, follow"', false);
});
