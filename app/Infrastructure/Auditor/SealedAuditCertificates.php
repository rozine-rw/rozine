<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Business\Contracts\HeldVisitDocuments;
use App\Models\AuditReport;

/**
 * Certificates the CPA already holds, read from the Business's sealed audit reports: for each
 * certificate, the expiry date recorded by the latest sealed report that recorded one. A monthly
 * visit records both on its expiries step; a baseline visit records the tax clearance on its
 * registration step, or none when the Business has no certificate.
 */
final class SealedAuditCertificates implements HeldVisitDocuments
{
    /** Where each checklist item's expiry date is recorded, in the steps that record it. */
    private const array SOURCES = [
        'tax_clearance' => [['expiries', 'tax_clearance_expires_on'], ['registration', 'tax_clearance_expires_on']],
        'rssb_clearance' => [['expiries', 'rssb_clearance_expires_on']],
    ];

    public function held(string $businessId, string $validOn): array
    {
        $expiries = [];
        $reports = AuditReport::query()->where('business_id', $businessId)->where('status', 'sealed')
            ->orderByDesc('updated_at')->orderByDesc('id')->cursor();
        foreach ($reports as $report) {
            /** @var array{fields?: array<string, array<string, mixed>>} $draft */
            $draft = $report->draft;
            foreach (self::SOURCES as $item => $sources) {
                foreach ($sources as [$step, $field]) {
                    if (! array_key_exists($item, $expiries) && array_key_exists($field, $draft['fields'][$step] ?? [])) {
                        $expiries[$item] = $draft['fields'][$step][$field];
                    }
                }
            }
            if (count($expiries) === count(self::SOURCES)) {
                break;
            }
        }

        return array_values(array_filter(array_keys(self::SOURCES),
            fn (string $item): bool => is_string($expiries[$item] ?? null) && $expiries[$item] >= $validOn));
    }
}
