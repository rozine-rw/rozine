<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\IngestAuditLedger;
use App\Application\Auditor\ReadAuditLedger;
use App\Application\Auditor\StartAuditReport;
use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\GetAuditStatementVerification;
use App\Application\Evidence\ReadAuditStatement;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessQuoteFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/**
 * Seeds the prior procedure state to isolate ingestion; full capture progression is tested with the source adapter.
 *
 * @return array<string, mixed>
 */
function ledgerDraftFixture(): array
{
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];
    $started = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
        $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());
    $report = AuditReport::query()->whereKey($started['data']['audit_id'])->firstOrFail();
    $report->forceFill(['revision' => 2, 'step' => 'ledger', 'draft' => ['note' => '', 'completed_steps' => ['review', 'check_in', 'photos'], 'fields' => []]])->save();
    AuditReportVersion::factory()->forReport($report, $user->party_id, $user->id)->create(['command' => 'fixture.procedure']);

    return [...$fixture, 'user' => $user, 'report' => $report];
}

it('retains a ledger original once and recovers its receipt without approving or advancing the report', function (): void {
    $fixture = ledgerDraftFixture();
    $user = $fixture['user'];
    $report = $fixture['report'];
    $request = (string) Str::uuid();
    $body = "date,reference,amount\n2026-08-01,inventory,1500\n";
    $before = app(GetAuditStatements::class)->handle($user->id, 1, $report->assignment_id);
    $action = app(IngestAuditLedger::class);
    $first = $action->handle($user->id, 1, $report->id, 2, 'ledger.csv', $body, null, $request);
    expect($first['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED')->and($first['revision'])->toBe(3)
        ->and($first['data']['step'])->toBe('ledger')->and(app(GetAuditStatements::class)->handle($user->id, 1, $report->assignment_id))->toBe($before)
        ->and($action->handle($user->id, 1, $report->id, 2, 'ledger.csv', $body, null, $request))->toBe($first)
        ->and(app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.save_step', $request))->toBe($first);
    $read = app(GetAuditReport::class)->handle($user->id, 1, $report->id);
    expect($read['status'])->toBe('draft')->and($read['step'])->toBe('ledger')
        ->and($read['draft']['documents'])->toBe([['id' => $first['data']['document_id'], 'revision' => 3, 'sha256' => hash('sha256', $body), 'replaces' => null]])
        ->and($read['draft']['fields'])->not->toHaveKey('ledger');
    $original = app(ReadAuditLedger::class)->handle($user->id, 1, $report->id, $first['data']['document_id']);
    expect($original['content'])->toBe($body)->and($original['sha256'])->toBe(hash('sha256', $body))
        ->and(app(GetAuditStatementVerification::class)->handle($user->id, 1, $report->assignment_id)['current'])->toBeTrue();
    $this->assertDatabaseCount('audit_report_versions', 3);
    expect(fn () => $action->handle($user->id, 1, $report->id, 2, 'ledger.csv', $body.'changed', null, $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('retains rescan lineage and the original while rejecting stale revisions and foreign replacements', function (): void {
    $fixture = ledgerDraftFixture();
    $user = $fixture['user'];
    $report = $fixture['report'];
    $action = app(IngestAuditLedger::class);
    $businessOriginal = app(GetAuditStatements::class)->handle($user->id, 1, $report->assignment_id)['evidence']['documents'][0]['id'];
    $source = app(ReadAuditStatement::class)->handle($user->id, 1, $report->assignment_id, $businessOriginal);
    $foreign = $action->handle($user->id, 1, $report->id, 2, 'rescan.csv', $source['content'], $businessOriginal, (string) Str::uuid());
    expect($foreign['code'])->toBe('STATEMENT_NOT_FOUND');
    $first = $action->handle($user->id, 1, $report->id, 2, 'ledger.csv', $source['content'], null, (string) Str::uuid());
    $old = $first['data']['document_id'];
    expect($old)->not->toBe($businessOriginal);
    $same = $action->handle($user->id, 1, $report->id, 3, 'rescan.csv', $source['content'], $old, (string) Str::uuid());
    expect($same['data']['document_id'])->toBe($old)->and($report->refresh()->draft['documents'][0]['replaces'])->toBeNull();
    $rescan = $action->handle($user->id, 1, $report->id, 4, 'rescan.csv', $source['content']."2026-08-02,10\n", $old, (string) Str::uuid());
    expect($rescan['data']['document_id'])->not->toBe($old)
        ->and($report->refresh()->draft['documents'][0]['replaces'])->toBe($old)
        ->and(app(ReadAuditLedger::class)->handle($user->id, 1, $report->id, $old)['content'])->toBe($source['content']);
    $stale = $action->handle($user->id, 1, $report->id, 2, 'rescan.csv', $source['content'], null, (string) Str::uuid());
    expect($stale['code'])->toBe('VERSION_CONFLICT');
    $foreign = $action->handle($user->id, 1, $report->id, 5, 'rescan.csv', $source['content'], (string) Str::ulid(), (string) Str::uuid());
    expect($foreign['code'])->toBe('STATEMENT_NOT_FOUND')->and($report->refresh()->revision)->toBe(5);
    $this->assertDatabaseCount('statement_originals', 1);
    $this->assertDatabaseCount('audit_ledger_originals', 2);
});

it('records unsupported uploads against the document field without writing an original', function (): void {
    $fixture = ledgerDraftFixture();
    $result = app(IngestAuditLedger::class)->handle($fixture['user']->id, 1, $fixture['report']->id, 2, 'ledger.png', 'not an original PDF or CSV', null, (string) Str::uuid());
    expect($result['status'])->toBe('rejected')->and($result['code'])->toBe('STATEMENT_TYPE_UNSUPPORTED')
        ->and($result['field_errors'])->toHaveKey('document')->and($fixture['report']->refresh()->revision)->toBe(2);
    $this->assertDatabaseCount('statement_originals', 1);
});

it('does not accept a ledger before the Flash procedure reaches the ledger step', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];
    $started = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
        $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());
    $result = app(IngestAuditLedger::class)->handle($user->id, 1, $started['data']['audit_id'], 1, 'ledger.csv',
        "date,amount\n2026-08-01,5\n", null, (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_LEDGER_NOT_AVAILABLE');
    $this->assertDatabaseCount('audit_report_versions', 1);
    $this->assertDatabaseCount('statement_originals', 1);
});

it('rolls back originals and parser work if report history fails', function (): void {
    $fixture = ledgerDraftFixture();
    $request = (string) Str::uuid();
    $event = 'eloquent.creating: '.AuditReportVersion::class;
    Event::listen($event, function (): void {
        throw new RuntimeException('Ledger history failed');
    });
    try {
        expect(fn () => app(IngestAuditLedger::class)->handle($fixture['user']->id, 1, $fixture['report']->id, 2, 'ledger.csv', "date,amount\n2026-08-01,5\n", null, $request))
            ->toThrow(RuntimeException::class, 'Ledger history failed');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('statement_originals', 1);
    $this->assertDatabaseCount('statement_extractions', 1);
    $this->assertDatabaseCount('audit_ledger_originals', 0);
    $this->assertDatabaseCount('audit_ledger_extractions', 0);
    expect($fixture['report']->refresh()->revision)->toBe(2)->and(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
});

it('accepts the multipart UI envelope on both transports and forbids simultaneous step fields', function (bool $api): void {
    $fixture = ledgerDraftFixture();
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($fixture['user'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($fixture['user']);
    }
    $body = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 2,
        'audit_id' => $fixture['report']->id, 'step' => 'ledger', 'replaces' => null,
        'document' => UploadedFile::fake()->createWithContent('ledger.csv', "date,amount\n2026-08-01,5\n")];
    $url = route($prefix.'reports.save', ['report' => $fixture['report']->id]);
    $this->postJson($url, [...$body, 'observed_stock' => '100'])->assertUnprocessable()->assertJsonValidationErrors('observed_stock');
    $this->postJson($url, $body)->assertOk()->assertJsonPath('code', 'INGESTED_NOT_AUDIT_APPROVED')->assertJsonPath('revision', 3)
        ->assertJsonPath('data.next.url', route($prefix.'reports.show', ['report' => $fixture['report']->id], false));
})->with([false, true]);
