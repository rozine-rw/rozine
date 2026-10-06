<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/**
 * A provider observation whose authenticity the adapter verified. Its facts are still compared
 * with the durable intent, with RWF 0 tolerance, before anything is reconciled.
 */
final readonly class VerifiedPayoutEvent
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $state,
        public ?string $operationId,
        public ?string $providerReference,
        public ?string $amount,
        public ?string $currency,
        public ?string $environment,
        public ?string $destinationSha256,
        public string $observedAt,
        public ?string $effectiveAt,
        public string $contentSha256,
        public array $evidence,
    ) {}
}
