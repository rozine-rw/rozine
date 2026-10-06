<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;

/**
 * `repayment.pay`: pays one option of a servicing note from the Business wallet under the actor's
 * current mandate permission. The server recomputes the total; a different quoted total or servicing
 * revision is refused `VERSION_CONFLICT`, never charged.
 */
final class PayBusinessRepayment
{
    public function __construct(private BusinessWalletStore $store) {}

    /**
     * @param  array{currency: string, amount: string}  $quotedTotal
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, string $noteId, string $option,
        int $expectedRevision, array $quotedTotal): array
    {
        return $this->store->pay($userId, $contextRevision, $businessId, $requestId, $noteId, $option, $expectedRevision, $quotedTotal);
    }
}
