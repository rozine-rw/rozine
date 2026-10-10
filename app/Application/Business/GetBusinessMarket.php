<?php

declare(strict_types=1);

namespace App\Application\Business;

/**
 * The Business Market tab for a current mandate holder: how the business's notes trade on the
 * secondary market. Secondary trading has no listing, price or volume read yet, so nothing but the
 * business the page belongs to is returned, read under the same `business.view` authority as every
 * other Business tab.
 */
final class GetBusinessMarket
{
    public function __construct(private WithBusinessAuthority $authority) {}

    /** @return array{identity_context_revision: int, business_id: string} */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $id = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (array $business): string => (string) $business['id']);

        return ['identity_context_revision' => $contextRevision, 'business_id' => $id];
    }
}
