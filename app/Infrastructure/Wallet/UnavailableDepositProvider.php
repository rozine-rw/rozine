<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use LogicException;

/**
 * Bound everywhere the synthetic provider is not allowed. No live provider, PSP or banking
 * arrangement exists yet, so nothing can be sent, verified or signed.
 */
final class UnavailableDepositProvider implements DepositProvider, SyntheticEventSigner
{
    public function name(): string
    {
        return 'unavailable';
    }

    public function idempotentSends(): bool
    {
        return false;
    }

    public function initiate(DepositInstruction $instruction): bool
    {
        throw new LogicException('WALLET_PROVIDER_UNAVAILABLE');
    }

    /** @param array<string, mixed> $message */
    public function verify(array $message): VerifiedDepositEvent
    {
        throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    public function sign(array $fields): array
    {
        throw new LogicException('WALLET_PROVIDER_UNAVAILABLE');
    }
}
