<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * The Investor-facing facts of a Business's published monthly audit reports (FR-103, FR-110):
 * only reports whose seal is still valid and whose bank statements reconciled to monthly figures.
 * No Auditor original, photo, location or statement leaves this port (H9).
 *
 * @phpstan-type PublishedMonthlyReport array{report_id: string, period: string, inflow: string, outflow: string, net: string,
 *     operational_status: string, differences: bool, auditor: array{name: string, licence: string, sealed_on: string},
 *     auditor_note: string, business_note: string}
 */
interface PublishedMonthlyReports
{
    /**
     * Newest period first. `period` is the reported month (`YYYY-MM`); amounts are whole RWF.
     *
     * @return list<PublishedMonthlyReport>
     */
    public function forBusiness(string $businessId): array;
}
