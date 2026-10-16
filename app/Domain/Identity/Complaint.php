<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use DateTimeImmutable;

/**
 * A complaint's rules (BRS v2.0 CMP-R03): it is acknowledged within 4 hours and resolved within 48
 * hours of being opened, or the customer is told why and when. The first staff message acknowledges
 * it; the staff message marked as the resolution closes it, after which nobody can add to it. Its
 * reference is the readable part of its identifier.
 */
final class Complaint
{
    public const int ACKNOWLEDGE_HOURS = 4;

    public const int RESOLVE_HOURS = 48;

    public const int MAX_LENGTH = 2000;

    /** The text of a message as it is recorded: trimmed, and empty for anything but text. */
    public static function body(mixed $text): string
    {
        return is_string($text) ? trim($text) : '';
    }

    /** Why a message's text is refused, or null when it may be recorded. */
    public static function refusal(string $body): ?string
    {
        if ($body === '') {
            return 'Tell us what happened.';
        }

        return mb_strlen($body) > self::MAX_LENGTH ? 'Keep the message to '.number_format(self::MAX_LENGTH).' characters.' : null;
    }

    /** `CMP-` and the last eight characters of the complaint's identifier, upper-cased. */
    public static function reference(string $id): string
    {
        return 'CMP-'.strtoupper(substr($id, -8));
    }

    /** @return array{acknowledge_by: DateTimeImmutable, resolve_by: DateTimeImmutable} */
    public static function clocks(DateTimeImmutable $openedAt): array
    {
        return ['acknowledge_by' => $openedAt->modify('+'.self::ACKNOWLEDGE_HOURS.' hours'),
            'resolve_by' => $openedAt->modify('+'.self::RESOLVE_HOURS.' hours')];
    }

    /** @return 'awaiting'|'answered'|'resolved' */
    public static function status(bool $acknowledged, bool $resolved): string
    {
        return $resolved ? 'resolved' : ($acknowledged ? 'answered' : 'awaiting');
    }
}
