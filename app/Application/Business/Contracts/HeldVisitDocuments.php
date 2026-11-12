<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The visit-ready items a Business need not bring again because its CPA already holds them (BRS v3
 * VIS-R03: "documents the CPA already holds are not asked again"). Today these are the tax and
 * RSSB clearance certificates, held when the Business's latest sealed audit report recorded an
 * expiry date on or after the day the visit must be sealed by.
 */
interface HeldVisitDocuments
{
    /**
     * The checklist item keys held and still valid on `$validOn` (a Kigali `Y-m-d` date).
     *
     * @return list<string>
     */
    public function held(string $businessId, string $validOn): array;
}
