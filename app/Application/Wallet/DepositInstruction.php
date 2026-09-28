<?php

declare(strict_types=1);

namespace App\Application\Wallet;

/** What a provider is asked to collect for one recorded intent. Opaque references only. */
final readonly class DepositInstruction
{
    public function __construct(
        public string $intentId,
        public string $operationId,
        public string $providerReference,
        public string $amount,
        public string $currency,
        public string $environment,
    ) {}
}
