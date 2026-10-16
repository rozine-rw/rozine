<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use InvalidArgumentException;

/**
 * The one audience a recorded change belongs to: a Party, a Business, or a staff queue. A change
 * is only ever read back by a caller whose current authority reaches that same audience.
 */
final readonly class ChangeScope
{
    public const string PARTY = 'party';

    public const string BUSINESS = 'business';

    public const string STAFF_QUEUE = 'staff_queue';

    /** The staff queues that can carry changes. */
    public const array QUEUES = ['applications'];

    private function __construct(public string $kind, public string $id) {}

    public static function party(string $partyId): self
    {
        return new self(self::PARTY, self::identifier($partyId));
    }

    public static function business(string $businessId): self
    {
        return new self(self::BUSINESS, self::identifier($businessId));
    }

    public static function staffQueue(string $queue): self
    {
        if (! in_array($queue, self::QUEUES, true)) {
            throw new InvalidArgumentException('CHANGE_SCOPE_INVALID');
        }

        return new self(self::STAFF_QUEUE, $queue);
    }

    public function key(): string
    {
        return $this->kind.':'.$this->id;
    }

    private static function identifier(string $id): string
    {
        if (preg_match('/^[0-9A-Za-z]{26}$/', $id) !== 1) {
            throw new InvalidArgumentException('CHANGE_SCOPE_INVALID');
        }

        return $id;
    }
}
