<?php

declare(strict_types=1);

namespace App\Application\Wallet;

/**
 * What a provider is asked to pay out for one recorded withdrawal: what reaches the Investor's own
 * verified account, named by its method. Opaque references only.
 */
final readonly class WithdrawalInstruction
{
    public function __construct(
        public string $withdrawalId,
        public string $operationId,
        public string $providerReference,
        public string $methodId,
        public string $amount,
        public string $currency,
        public string $environment,
    ) {}
}
