<?php

declare(strict_types=1);

namespace Tests\Support;

/** The smallest whole JPEG, PNG and WebP files `PhotoBytes` accepts, built byte by byte. */
final class PhotoBytesFixture
{
    public static function jpeg(): string
    {
        return "\xFF\xD8\xFF\xE0".pack('n', 16)."JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00"
            ."\xFF\xC0".pack('n', 11)."\x08".pack('nn', 1, 1)."\x01\x01\x11\x00"
            ."\xFF\xDA".pack('n', 8)."\x01\x01\x00\x00\x3F\x00".'scan'."\xFF\xD9";
    }

    public static function png(): string
    {
        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1A\n".$chunk('IHDR', pack('NNC5', 1, 1, 8, 2, 0, 0, 0)).$chunk('IDAT', 'pixels').$chunk('IEND', '');
    }

    public static function webp(): string
    {
        $chunk = 'VP8L'.pack('V', 6)."\x2Fbits!";

        return 'RIFF'.pack('V', 4 + strlen($chunk)).'WEBP'.$chunk;
    }
}
