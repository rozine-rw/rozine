<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * A plain name for the device behind a browser's user agent, such as "Chrome on Windows", for the
 * new-device notice. Anything unrecognised is named generically rather than echoed back.
 */
final class DeviceLabel
{
    /** @var array<string, string> Checked in order: Edge and Opera also announce Chrome, and Chrome announces Safari. */
    private const array BROWSERS = ['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'FxiOS/' => 'Firefox',
        'CriOS/' => 'Chrome', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'];

    /** @var array<string, string> Checked in order: Android also announces Linux, and iOS announces Mac OS X. */
    private const array SYSTEMS = ['Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Windows' => 'Windows',
        'Mac OS X' => 'macOS', 'CrOS' => 'ChromeOS', 'Linux' => 'Linux'];

    public static function fromUserAgent(?string $userAgent): string
    {
        $browser = self::first(self::BROWSERS, (string) $userAgent);
        $system = self::first(self::SYSTEMS, (string) $userAgent);

        return match (true) {
            $browser !== null && $system !== null => "{$browser} on {$system}",
            default => $browser ?? $system ?? 'An unrecognised browser',
        };
    }

    /** @param  array<string, string>  $names */
    private static function first(array $names, string $userAgent): ?string
    {
        foreach ($names as $marker => $name) {
            if (str_contains($userAgent, $marker)) {
                return $name;
            }
        }

        return null;
    }
}
