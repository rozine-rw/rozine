<?php

/*
 * The icons derived from the star mark.
 *
 * These assert properties that are invisible until they are wrong on someone's
 * home screen: a maskable icon whose points get clipped by the launcher's mask,
 * or an Apple touch icon with alpha, which iOS composites onto black rather
 * than honouring. Both had gone wrong once already.
 */

/**
 * @return array{width: int, height: int, pixels: GdImage}
 */
function icon(string $name): array
{
    $path = public_path($name);

    expect(file_exists($path))->toBeTrue("public/{$name} is missing");

    $image = imagecreatefrompng($path);

    expect($image)->not->toBeFalse("public/{$name} is not readable as a PNG");

    return ['width' => imagesx($image), 'height' => imagesy($image), 'pixels' => $image];
}

/**
 * The furthest visible pixel from the centre, as a fraction of the icon's width.
 *
 * Background-coloured and transparent pixels are not content: what matters is
 * how far the mark itself reaches, because that is what a mask can cut.
 */
function contentReach(string $name): float
{
    ['width' => $width, 'height' => $height, 'pixels' => $image] = icon($name);

    $background = imagecolorat($image, 0, 0);
    $centreX = ($width - 1) / 2;
    $centreY = ($height - 1) / 2;
    $furthest = 0.0;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $colour = imagecolorat($image, $x, $y);

            if ($colour === $background) {
                continue;
            }

            $rgba = imagecolorsforindex($image, $colour);

            if ($rgba['alpha'] > 96) {
                continue;
            }

            $furthest = max($furthest, hypot($x - $centreX, $y - $centreY));
        }
    }

    return 2 * $furthest / $width;
}

function isFullyOpaque(string $name): bool
{
    ['width' => $width, 'height' => $height, 'pixels' => $image] = icon($name);

    for ($y = 0; $y < $height; $y += 3) {
        for ($x = 0; $x < $width; $x += 3) {
            if (imagecolorsforindex($image, imagecolorat($image, $x, $y))['alpha'] !== 0) {
                return false;
            }
        }
    }

    return true;
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
    $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest['icons'])->not->toBeEmpty();

    foreach ($manifest['icons'] as $entry) {
        ['width' => $width, 'height' => $height] = icon(ltrim((string) $entry['src'], '/'));

        expect("{$width}x{$height}")->toBe($entry['sizes']);
    }

    foreach (['favicon.ico', 'favicon.svg', 'favicon-96.png', 'apple-touch-icon.png'] as $referenced) {
        expect(file_exists(public_path($referenced)))->toBeTrue("public/{$referenced} is missing");
    }
});

it('declares both a maskable and an unmasked icon for each launcher size', function () {
    $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

    $purposes = collect($manifest['icons'])->groupBy('purpose')->map->count();

    expect($purposes)->toHaveKeys(['any', 'maskable'])
        ->and($purposes['maskable'])->toBeGreaterThanOrEqual(2);
});

it('derives the favicon from the approved star mark', function () {
    $favicon = (string) file_get_contents(public_path('favicon.svg'));

    preg_match('/<path d="([^"]+)"/', $favicon, $faviconPath);
    preg_match('/<path d="([^"]+)"/', (string) file_get_contents(base_path('docs/New Logo/Frame 87.svg')), $approvedPath);

    // Frame 87 is the standalone star from the approved package. If the favicon
    // ever stops matching it, the brand record and the runtime have diverged.
    expect($faviconPath[1])->toBe($approvedPath[1]);
});
