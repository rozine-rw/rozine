<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Domain\Operations\CommandRejection;
use LogicException;

/**
 * Bound everywhere the synthetic provider is not allowed. No live payout provider or banking
 * arrangement exists, so nothing can be sent, queried or verified.
 */
final class UnavailablePayoutProvider implements PayoutProvider
{
    public function name(): string
    {
        return 'unavailable';
    }

    public function idempotentSends(): bool
    {
        return false;
    }

    public function send(PayoutInstruction $instruction): bool
    {
        throw new LogicException('PAYOUT_PROVIDER_UNAVAILABLE');
    }

    public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent
    {
        throw new LogicException('PAYOUT_PROVIDER_UNAVAILABLE');
    }

    /** @param array<string, mixed> $message */
    public function verify(array $message): VerifiedPayoutEvent
    {
        throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
    }
}
