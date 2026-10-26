<?php

declare(strict_types=1);

namespace App\Domain\Business;

/**
 * Whether a file is a whole image of the type it is labelled as, read from its structure alone (no
 * image library): a JPEG's marker segments from the start of image through a frame header and the
 * scan to the end-of-image marker, a PNG's checksummed chunks from the header through IDAT to IEND,
 * and a WebP's RIFF chunks, opening with VP8, VP8L or VP8X, that carry an image and fill the file. A known signature on top of
 * garbage, or a file cut short, is not a whole image. Pixels are not decoded.
 */
final class PhotoBytes
{
    private const string PNG_SIGNATURE = "\x89PNG\r\n\x1A\n";

    public static function whole(string $bytes, string $mediaType): bool
    {
        return match ($mediaType) {
            'image/jpeg' => self::jpeg($bytes),
            'image/png' => self::png($bytes),
            'image/webp' => self::webp($bytes),
            default => false,
        };
    }

    private static function jpeg(string $bytes): bool
    {
        $length = strlen($bytes);
        if ($length < 4 || ! str_starts_with($bytes, "\xFF\xD8") || substr($bytes, -2) !== "\xFF\xD9") {
            return false;
        }
        $offset = 2;
        $framed = false;
        while ($offset + 4 <= $length && $bytes[$offset] === "\xFF") {
            while ($offset + 1 < $length && $bytes[$offset + 1] === "\xFF") {
                $offset++;
            }
            $marker = ord($bytes[$offset + 1] ?? "\x00");
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $offset += 2;

                continue;
            }
            if ($marker === 0x00 || $marker === 0xD8 || $marker === 0xD9) {
                return false;
            }
            $size = self::uint16($bytes, $offset + 2);
            if ($size < 2 || $offset + 2 + $size > $length) {
                return false;
            }
            if ($marker >= 0xC0 && $marker <= 0xCF && ! in_array($marker, [0xC4, 0xC8, 0xCC], true)) {
                $framed = $size >= 8 && self::uint16($bytes, $offset + 5) > 0 && self::uint16($bytes, $offset + 7) > 0;
            }
            if ($marker === 0xDA) {
                return $framed && $offset + 2 + $size < $length - 2;
            }
            $offset += 2 + $size;
        }

        return false;
    }

    private static function png(string $bytes): bool
    {
        $length = strlen($bytes);
        if (! str_starts_with($bytes, self::PNG_SIGNATURE)) {
            return false;
        }
        $offset = strlen(self::PNG_SIGNATURE);
        $data = false;
        while ($offset + 12 <= $length) {
            $size = self::uint32($bytes, $offset);
            $type = substr($bytes, $offset + 4, 4);
            if ($size > $length - $offset - 12 || self::uint32($bytes, $offset + 8 + $size) !== crc32(substr($bytes, $offset + 4, 4 + $size))) {
                return false;
            }
            $first = $offset === strlen(self::PNG_SIGNATURE);
            if ($first !== ($type === 'IHDR') || ($first && ($size !== 13 || self::uint32($bytes, $offset + 8) === 0 || self::uint32($bytes, $offset + 12) === 0))) {
                return false;
            }
            $data = $data || $type === 'IDAT';
            $offset += 12 + $size;
            if ($type === 'IEND') {
                return $data && $size === 0 && $offset === $length;
            }
        }

        return false;
    }

    private static function webp(string $bytes): bool
    {
        $length = strlen($bytes);
        if ($length < 20 || ! str_starts_with($bytes, 'RIFF') || substr($bytes, 8, 4) !== 'WEBP' || self::uint32le($bytes, 4) !== $length - 8
            || ! in_array(substr($bytes, 12, 4), ['VP8 ', 'VP8L', 'VP8X'], true)) {
            return false;
        }
        $offset = 12;
        $image = false;
        while ($offset + 8 <= $length) {
            $type = substr($bytes, $offset, 4);
            $size = self::uint32le($bytes, $offset + 4);
            $next = $offset + 8 + $size + ($size % 2);
            if ($next > $length || ($type === 'VP8 ' && ($size < 10 || substr($bytes, $offset + 11, 3) !== "\x9D\x01\x2A"))
                || ($type === 'VP8L' && ($size < 5 || $bytes[$offset + 8] !== "\x2F")) || ($type === 'VP8X' && ($offset !== 12 || $size !== 10))) {
                return false;
            }
            $image = $image || in_array($type, ['VP8 ', 'VP8L', 'ANMF'], true);
            $offset = $next;
        }

        return $image && $offset === $length;
    }

    private static function uint16(string $bytes, int $offset): int
    {
        return (ord($bytes[$offset] ?? "\x00") << 8) | ord($bytes[$offset + 1] ?? "\x00");
    }

    private static function uint32(string $bytes, int $offset): int
    {
        return (self::uint16($bytes, $offset) << 16) | self::uint16($bytes, $offset + 2);
    }

    private static function uint32le(string $bytes, int $offset): int
    {
        return ord($bytes[$offset] ?? "\x00") | ord($bytes[$offset + 1] ?? "\x00") << 8
            | ord($bytes[$offset + 2] ?? "\x00") << 16 | ord($bytes[$offset + 3] ?? "\x00") << 24;
    }
}
