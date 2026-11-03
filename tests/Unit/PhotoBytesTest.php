<?php

declare(strict_types=1);

use App\Domain\Business\PhotoBytes;
use Tests\Support\PhotoBytesFixture;

/** @param list<array{0: string, 1: string}> $chunks */
function photoPng(array $chunks): string
{
    return "\x89PNG\r\n\x1A\n".implode('', array_map(fn (array $chunk): string => pack('N', strlen($chunk[1])).$chunk[0].$chunk[1]
        .pack('N', crc32($chunk[0].$chunk[1])), $chunks));
}

it('accepts a whole image of the labelled type', function (string $type, string $bytes): void {
    expect(PhotoBytes::whole($bytes, $type))->toBeTrue();
})->with([
    'JPEG' => ['image/jpeg', PhotoBytesFixture::jpeg()],
    'PNG' => ['image/png', PhotoBytesFixture::png()],
    'WebP' => ['image/webp', PhotoBytesFixture::webp()],
    'JPEG with fill bytes before a marker' => ['image/jpeg', "\xFF\xD8\xFF".substr(PhotoBytesFixture::jpeg(), 2)],
    'JPEG with a restart marker' => ['image/jpeg', "\xFF\xD8\xFF\xD0".substr(PhotoBytesFixture::jpeg(), 2)],
    'JPEG with a TEM marker' => ['image/jpeg', "\xFF\xD8\xFF\x01".substr(PhotoBytesFixture::jpeg(), 2)],
]);

it('refuses another type, a signature on garbage and a file cut short', function (string $type, string $bytes): void {
    expect(PhotoBytes::whole($bytes, $type))->toBeFalse();
})->with([
    'GIF label' => ['image/gif', PhotoBytesFixture::png()],
    'text as JPEG' => ['image/jpeg', '<?php echo 1;'],
    'PNG as JPEG' => ['image/jpeg', PhotoBytesFixture::png()],
    'JPEG as PNG' => ['image/png', PhotoBytesFixture::jpeg()],
    'PNG as WebP' => ['image/webp', PhotoBytesFixture::png()],
    'JPEG signature on garbage' => ['image/jpeg', "\xFF\xD8\xFF\xE0".str_repeat('a', 64)."\xFF\xD9"],
    'PNG signature on garbage' => ['image/png', "\x89PNG\r\n\x1A\n".str_repeat('b', 64)],
    'WebP signature on garbage' => ['image/webp', 'RIFF'.pack('V', 68).'WEBP'.str_repeat('c', 60)],
    'JPEG cut short' => ['image/jpeg', substr(PhotoBytesFixture::jpeg(), 0, -3)],
    'JPEG without a frame' => ['image/jpeg', "\xFF\xD8\xFF\xDA".pack('n', 8)."\x01\x01\x00\x00\x3F\x00scan\xFF\xD9"],
    'PNG cut short' => ['image/png', substr(PhotoBytesFixture::png(), 0, -1)],
    'PNG with a bad checksum' => ['image/png', substr_replace(PhotoBytesFixture::png(), 'X', 40, 1)],
    'PNG with bytes after IEND' => ['image/png', PhotoBytesFixture::png().'tail'],
    'WebP cut short' => ['image/webp', substr(PhotoBytesFixture::webp(), 0, -1)],
    'JPEG with a second start of image' => ['image/jpeg', "\xFF\xD8\xFF\xD8".substr(PhotoBytesFixture::jpeg(), 2)],
    'JPEG with a stuffed zero for a marker' => ['image/jpeg', "\xFF\xD8\xFF\x00".substr(PhotoBytesFixture::jpeg(), 2)],
    'JPEG whose segments end before a scan' => ['image/jpeg', "\xFF\xD8\xFF\xE0".pack('n', 4).'ab'.'junk'."\xFF\xD9"],
    'PNG opening with IDAT' => ['image/png', photoPng([['IDAT', 'pixels'], ['IEND', '']])],
    'PNG with a zero width' => ['image/png', photoPng([['IHDR', pack('NNC5', 0, 1, 8, 2, 0, 0, 0)], ['IDAT', 'pixels'], ['IEND', '']])],
    'PNG with a second IHDR' => ['image/png', photoPng([['IHDR', pack('NNC5', 1, 1, 8, 2, 0, 0, 0)], ['IHDR', pack('NNC5', 1, 1, 8, 2, 0, 0, 0)],
        ['IDAT', 'pixels'], ['IEND', '']])],
    'WebP with a lying chunk size' => ['image/webp', 'RIFF'.pack('V', 18).'WEBPVP8L'.pack('V', 60)."\x2Fbits!"],
]);
