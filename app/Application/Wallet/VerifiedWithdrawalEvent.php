<?php

declare(strict_types=1);

namespace App\Application\Wallet;

/**
 * A withdrawal outcome whose authenticity the adapter has verified. Its facts are still checked
 * against the withdrawal (environment, currency, amount paid) before anything is applied.
 */
final readonly class VerifiedWithdrawalEvent
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $providerReference,
        public string $state,
        public string $amount,
        public string $currency,
        public string $environment,
        public string $observedAt,
        public string $contentSha256,
        public array $evidence,
    ) {}
}
