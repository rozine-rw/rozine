<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\SyntheticWithdrawalSigner;
use App\Application\Wallet\Contracts\WithdrawalProvider;
use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;
use App\Domain\Operations\CommandRejection;
use LogicException;

/**
 * Bound everywhere the synthetic provider is not allowed. No live payout provider, PSP or banking
 * arrangement exists yet, so nothing can be paid out, verified or signed.
 */
final class UnavailableWithdrawalProvider implements SyntheticWithdrawalSigner, WithdrawalProvider
{
    public function name(): string
    {
        return 'unavailable';
    }

    public function charge(): string
    {
        throw new LogicException('WALLET_PROVIDER_UNAVAILABLE');
    }

    public function idempotentSends(): bool
    {
        return false;
    }

    public function send(WithdrawalInstruction $instruction): bool
    {
        throw new LogicException('WALLET_PROVIDER_UNAVAILABLE');
    }

    /** @param array<string, mixed> $message */
    public function verify(array $message): VerifiedWithdrawalEvent
    {
        throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
    }

    public function sign(array $fields): array
    {
        throw new LogicException('WALLET_PROVIDER_UNAVAILABLE');
    }
}
