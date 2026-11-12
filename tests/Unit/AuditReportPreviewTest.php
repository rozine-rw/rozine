<?php

declare(strict_types=1);

use App\Application\Auditor\BuildAuditReportPreview;
use App\Infrastructure\Operations\JcsCanonicalJson;

it('binds persisted observations, notes, licence and source revisions into the preview', function (string $kind, ?string $reported, ?string $observed, string $status, string $note, bool $required): void {
    $json = new JcsCanonicalJson;
    $builder = new BuildAuditReportPreview($json);
    $pin = ['id' => 'source', 'revision' => 1, 'sha256' => str_repeat('a', 64)];
    $document = ['id' => 'original-b', 'filename' => 'statement.csv', 'sha256' => str_repeat('b', 64), 'media_type' => 'text/csv',
        'size_bytes' => 10, 'received_at' => '2026-09-01T00:00:00Z', 'extraction' => ['id' => 'extraction', 'revision' => 1,
            'parser_version' => 'utf8-csv-2', 'status' => 'text_extracted', 'reason_codes' => [], 'record_count' => 1]];
    $sources = ['verification' => $pin, 'declaration' => [...$pin, 'id' => 'declaration'], 'check_in' => [...$pin, 'id' => 'check-in'],
        'photos' => [...$pin, 'id' => 'photos'], 'reported_stock' => $reported, 'reported_cash' => $reported, 'reported_units' => $reported,
        'financial_proofs' => ['original-b'], 'inventory_proofs' => ['photo'], 'extra_photos' => [],
        'documents' => [$document, [...$document, 'id' => 'original-a']], 'monthly' => null, 'history' => [], 'licence' => 'SYNTHETIC-CPA', 'source_facts' => null,
        'authority' => ['mandate_version' => 1, 'mandate_sha256' => str_repeat('a', 64), 'profile_revision' => 1, 'engagement_id' => 'engagement']];
    $report = ['id' => 'report', 'assignment_id' => 'assignment', 'business_id' => 'business', 'application_id' => 'application',
        'application_revision' => 4, 'revision' => 8, 'kind' => $kind, 'procedure' => $kind, 'status' => 'draft', 'step' => 'seal', 'period' => $kind === 'monthly' ? '2026-08' : null,
        'amends_id' => null, 'amendment_id' => null, 'binding_sha256' => str_repeat('c', 64), 'version' => ['id' => 'version', 'sha256' => str_repeat('d', 64)],
        'draft' => ['note' => $note, 'completed_steps' => [], 'fields' => ['ledger' => ['observed_stock' => $observed],
            'count' => ['cash' => $observed, 'stock_units' => $observed, 'operational_status' => $status]]]];
    $first = $builder->handle($report, $sources);
    expect($first['note_required'])->toBe($required)->and($first['note_missing'])->toBe($required && $note === '')
        ->and($first['digest'])->toBe(hash('sha256', $json->encode($first['payload'])))
        ->and($first['payload']['draft']['note'])->toBe($note)->and($first['payload']['licence'])->toBe('SYNTHETIC-CPA')
        ->and(array_column($first['payload']['originals'], 'id'))->toBe(['original-a', 'original-b'])
        ->and($first['payload'])->not->toHaveKeys(['rating', 'capacity', 'credit_verdict']);
    $changed = $report;
    $changed['draft']['note'] .= ' Additional factual note.';
    expect($builder->handle($changed, $sources)['digest'])->not->toBe($first['digest']);
    $changed = $report;
    $changed['revision']++;
    expect($builder->handle($changed, $sources)['digest'])->not->toBe($first['digest']);
    $changedSources = [...$sources, 'verification' => [...$pin, 'revision' => 2]];
    expect($builder->handle($report, $changedSources)['digest'])->not->toBe($first['digest'])
        ->and($builder->handle($report, [...$sources, 'licence' => 'NEW-LICENCE'])['digest'])->not->toBe($first['digest']);
    $sources['documents'] = array_reverse($sources['documents']);
    expect($builder->handle($report, $sources)['digest'])->toBe($first['digest']);
    $sources['photos'] = null;
    $sources['declaration'] = null;
    $report['draft']['fields']['count'] = ['operational_status' => 'active'];
    expect($builder->handle($report, $sources)['note_required'])->toBeFalse();
})->with([
    ['flash', '10000000', '10000001', 'active', '', true],
    ['flash', '100', '100', 'active', '', false],
    ['flash', '100', '90', 'active', 'Count discrepancy observed.', true],
    ['flash', null, '100', 'active', '', false],
    ['flash', '100', null, 'active', '', false],
    ['monthly', '100', '100', 'active', '', false],
    ['monthly', '100', '90', 'active', '', true],
    ['monthly', '100', '100', 'suspended', '', true],
]);

it('seals the cause codes that explain a monthly cash difference on its cash finding only', function (): void {
    $builder = new BuildAuditReportPreview(new JcsCanonicalJson);
    $pin = ['id' => 'source', 'revision' => 1, 'sha256' => str_repeat('a', 64)];
    $sources = ['verification' => $pin, 'declaration' => [...$pin, 'id' => 'declaration'], 'check_in' => null, 'photos' => null,
        'reported_stock' => null, 'reported_cash' => '100', 'reported_units' => '10', 'financial_proofs' => [], 'inventory_proofs' => [], 'extra_photos' => [],
        'documents' => [], 'monthly' => null, 'history' => [], 'licence' => 'SYNTHETIC-CPA', 'source_facts' => null,
        'authority' => ['mandate_version' => 1, 'mandate_sha256' => str_repeat('a', 64), 'profile_revision' => 1, 'engagement_id' => 'engagement']];
    $explanations = [['cause' => 'unrecorded_sales', 'amount' => '4'], ['cause' => 'entry_error', 'amount' => '6']];
    $report = ['id' => 'report', 'assignment_id' => 'assignment', 'business_id' => 'business', 'application_id' => 'application',
        'application_revision' => 4, 'revision' => 3, 'kind' => 'monthly', 'procedure' => 'monthly', 'status' => 'draft', 'step' => 'seal', 'period' => '2026-08',
        'amends_id' => null, 'amendment_id' => null, 'binding_sha256' => str_repeat('c', 64), 'version' => ['id' => 'version', 'sha256' => str_repeat('d', 64)], 'draft' => ['note' => 'Recorded.', 'completed_steps' => [],
            'fields' => ['count' => ['cash' => '90', 'stock_units' => '9', 'operational_status' => 'active'], 'differences' => ['explanations' => $explanations]]]];

    $findings = $builder->handle($report, $sources)['findings'];
    expect($findings[0])->toMatchArray(['code' => 'CASH_DIFFERENCE', 'difference' => '-10', 'explained_by' => $explanations])
        ->and($findings[1])->not->toHaveKey('explained_by');

    $report['draft']['fields']['differences'] = ['explanations' => []];
    expect($builder->handle($report, $sources)['findings'][0])->not->toHaveKey('explained_by');
});
