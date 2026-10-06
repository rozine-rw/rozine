<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\StartAuditReport;
use App\Models\AuditReport;
use Illuminate\Support\Str;
use RuntimeException;

final class AuditLedgerFixture
{
    /** @return array<string, mixed> */
    public static function ready(): array
    {
        $fixture = BusinessQuoteFixture::ready();
        BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
        $facts = AuditSourceFactsFixture::facts();
        $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
        $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];
        AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
        $user = $fixture['audit']['partners'][0]['user'];
        $started = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
            $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());
        $report = AuditReport::query()->whereKey($started['data']['audit_id'])->firstOrFail();
        foreach (['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Stock room']]] as $step => $fields) {
            $saved = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, $report->refresh()->revision, $step, $fields, (string) Str::uuid());
            if ($saved['code'] !== 'AUDIT_STEP_SAVED') {
                throw new RuntimeException('Synthetic ledger procedure did not advance: '.$saved['code']);
            }
        }

        return [...$fixture, 'user' => $user, 'report' => $report->refresh()];
    }
}
