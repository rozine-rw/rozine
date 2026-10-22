<?php

declare(strict_types=1);

use App\Application\Auditor\SealedAuditReportDocument;

it('says when a seal recorded no findings and keeps the partner note and names in the font encoding', function (): void {
    $document = new SealedAuditReportDocument(['report_id' => '01jreport0000000000000000a', 'kind' => 'monthly', 'business' => 'Café (Kigali)',
        'district' => 'Gasabo', 'period' => '2026-09', 'auditor' => 'Aline Mukamana', 'licence' => 'PPC-0412', 'sealed_at' => '2026-10-03T08:00:00Z',
        'digest' => str_repeat('a', 64), 'findings' => [], 'note' => '', 'variance_pct' => null]);

    expect($document->lines())->toContain('The seal recorded no findings.', 'Weighted variance: not applicable', 'No note.',
        'Audit:         Monthly verification, September 2026', 'Sealed:        3 Oct 2026 10:00 (Kigali)')
        ->and($document->pdf())->toContain("Caf\xE9 \\(Kigali\\)")->toContain('Page 1 of 1');
});
