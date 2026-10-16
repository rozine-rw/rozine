<?php

declare(strict_types=1);

namespace App\Domain\Operations;

/**
 * Where a reader of the change feed stands. It is opaque to clients.
 *
 * - `xmin` is the snapshot horizon of the previous read: every transaction below it had finished,
 *   so every change it wrote has been delivered, and no change can later appear below it. The next
 *   read delivers the changes of transactions from `xmin` up to its own horizon.
 * - `afterId` is the highest feed id already delivered from the reader's own transaction, which a
 *   transaction always sees (read-your-writes); other transactions never use it.
 * - `issuedAt` (Unix seconds) lets an abandoned cursor expire.
 * - `context` is the identity context revision it was issued under; another one resets the reader.
 */
final readonly class ChangeCursor
{
    public const string VERSION = '1';

    public function __construct(public int $xmin, public int $afterId, public int $issuedAt, public int $context) {}

    public static function parse(string $cursor): ?self
    {
        if (preg_match('/^'.self::VERSION.'\.([1-9][0-9]{0,18})\.(0|[1-9][0-9]{0,18})\.([1-9][0-9]{0,11})\.(0|[1-9][0-9]{0,11})$/', $cursor, $parts) !== 1) {
            return null;
        }
        foreach ([$parts[1], $parts[2]] as $value) {
            if (strlen($value) === 19 && strcmp($value, (string) PHP_INT_MAX) > 0) {
                return null;
            }
        }

        return new self((int) $parts[1], (int) $parts[2], (int) $parts[3], (int) $parts[4]);
    }

    public function encode(): string
    {
        return implode('.', [self::VERSION, $this->xmin, $this->afterId, $this->issuedAt, $this->context]);
    }

    /** Whether it was issued more than `$ttlSeconds` before `$now`, or claims to be from the future. */
    public function expired(int $now, int $ttlSeconds): bool
    {
        return $this->issuedAt < $now - $ttlSeconds || $this->issuedAt > $now + 300;
    }
}
