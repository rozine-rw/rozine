<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/*
 * The icons derived from the star mark.
 *
 * These assert properties that stay invisible until they are wrong on someone's
 * home screen: a maskable icon whose points get clipped by the launcher's mask,
 * or an Apple touch icon with alpha, which iOS composites onto black rather
 * than honouring. Both had gone wrong once already.
 */

/**
 * Reads an icon, failing loudly rather than letting a false propagate into GD.
 *
 * @return array{width: int, height: int, image: GdImage}
 */
function icon(string $name): array
{
    $path = public_path($name);

    expect(File::exists($path))->toBeTrue("public/{$name} is missing");

    $image = imagecreatefrompng($path);

    if ($image === false) {
        throw new RuntimeException("public/{$name} is not readable as a PNG.");
    }

    return ['width' => imagesx($image), 'height' => imagesy($image), 'image' => $image];
}

/**
 * @return array{red: int, green: int, blue: int, alpha: int}
 */
function pixel(GdImage $image, int $x, int $y): array
{
    $colour = imagecolorat($image, $x, $y);

    if ($colour === false) {
        throw new RuntimeException("Pixel {$x},{$y} could not be read.");
    }

    return imagecolorsforindex($image, $colour);
}

/**
 * The furthest visible pixel from the centre, as a fraction of the icon's width.
 *
 * Background-coloured and transparent pixels are not content: what matters is
 * how far the mark itself reaches, because that is what a mask can cut.
 */
function contentReach(string $name): float
{
    ['width' => $width, 'height' => $height, 'image' => $image] = icon($name);

    $background = pixel($image, 0, 0);
    $centreX = ($width - 1) / 2;
    $centreY = ($height - 1) / 2;
    $furthest = 0.0;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $colour = pixel($image, $x, $y);

            if ($colour['alpha'] > 96) {
                continue;
            }

            $distance = abs($colour['red'] - $background['red'])
                + abs($colour['green'] - $background['green'])
                + abs($colour['blue'] - $background['blue']);

            if ($distance < 40) {
                continue;
            }

            $furthest = max($furthest, hypot($x - $centreX, $y - $centreY));
        }
    }

    return 2 * $furthest / $width;
}

function isFullyOpaque(string $name): bool
{
    ['width' => $width, 'height' => $height, 'image' => $image] = icon($name);

    for ($y = 0; $y < $height; $y += 3) {
        for ($x = 0; $x < $width; $x += 3) {
            if (pixel($image, $x, $y)['alpha'] !== 0) {
                return false;
            }
        }
    }

    return true;
}

/**
 * @return list<array{src: string, sizes: string, type: string, purpose: string}>
 */
function manifestIcons(): array
{
    /** @var array{icons: list<array{src: string, sizes: string, type: string, purpose: string}>} $manifest */
    $manifest = json_decode((string) File::get(public_path('site.webmanifest')), true, 512, JSON_THROW_ON_ERROR);

    return $manifest['icons'];
}

function firstPathData(string $svg): string
{
    if (preg_match('/<path d="([^"]+)"/', $svg, $matches) !== 1) {
        throw new RuntimeException('No path data found in the SVG.');
    }

    return $matches[1];
}

it('keeps maskable icons inside the safe circle a launcher guarantees', function (string $name) {
    // A maskable icon is promised only the middle 80% of its width; a launcher
    // may crop to a circle, a squircle or a rounded square outside that.
    expect(contentReach($name))->toBeLessThanOrEqual(0.80);
})->with(['icon-maskable-192.png', 'icon-maskable-512.png']);

it('leaves no transparency in icons the platform composites itself', function (string $name) {
    // iOS ignores alpha in a touch icon and composites onto black, so a
    // transparent one renders the mark on a black square.
    expect(isFullyOpaque($name))->toBeTrue();
})->with(['apple-touch-icon.png', 'icon-maskable-192.png', 'icon-maskable-512.png']);

it('ships every icon the manifest and document head reference', function () {
    $icons = manifestIcons();

    expect($icons)->not->toBeEmpty();

    foreach ($icons as $entry) {
        ['width' => $width, 'height' => $height] = icon(ltrim($entry['src'], '/'));

        expect("{$width}x{$height}")->toBe($entry['sizes']);
    }

    foreach (['favicon.ico', 'favicon.svg', 'favicon-96.png', 'apple-touch-icon.png'] as $referenced) {
        expect(File::exists(public_path($referenced)))->toBeTrue("public/{$referenced} is missing");
    }
});

it('declares both a maskable and an unmasked icon for each launcher size', function () {
    $purposes = [];

    foreach (manifestIcons() as $entry) {
        $purposes[$entry['purpose']] = ($purposes[$entry['purpose']] ?? 0) + 1;
    }

    expect($purposes)->toHaveKeys(['any', 'maskable'])
        ->and($purposes['maskable'])->toBeGreaterThanOrEqual(2);
});

it('derives the favicon from the approved star mark', function () {
    // Frame 87 is the standalone star from the approved package. If the favicon
    // ever stops matching it, the brand record and the runtime have diverged.
    $favicon = firstPathData((string) File::get(public_path('favicon.svg')));
    $approved = firstPathData((string) File::get(base_path('docs/New Logo/Frame 87.svg')));

    expect($favicon)->toBe($approved);
});
