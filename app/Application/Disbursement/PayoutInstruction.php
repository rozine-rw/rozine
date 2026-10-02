<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/** What the provider is asked to pay, or asked about, for one durable intent. Opaque references only. */
final readonly class PayoutInstruction
{
    public function __construct(
        public string $intentId,
        public string $operationId,
        public string $providerReference,
        public string $amount,
        public string $currency,
        public string $destinationId,
        public string $destinationSha256,
        public string $environment,
    ) {}
}
