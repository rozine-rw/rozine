<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\IngestAuditLedger;
use App\Application\Auditor\ReadAuditLedger;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\GetAuditStatementVerification;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditLedgerFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('continues from an actual upload to a pinned preview without changing Business statements or verification', function (): void {
    $fixture = AuditLedgerFixture::ready();
    ['user' => $user, 'report' => $report] = $fixture;
    $before = app(GetAuditStatements::class)->handle($user->id, 1, $report->assignment_id);
    $verification = app(GetAuditStatementVerification::class)->handle($user->id, 1, $report->assignment_id);
    $receipt = app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', "date,amount\n2026-08-01,38000000\n", null, (string) Str::uuid());
    $original = AuditLedgerOriginal::query()->whereKey($receipt['data']['document_id'])->firstOrFail();
    expect($receipt['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED')
        ->and(app(GetAuditStatements::class)->handle($user->id, 1, $report->assignment_id))->toBe($before)
        ->and(app(GetAuditStatementVerification::class)->handle($user->id, 1, $report->assignment_id))->toBe($verification)
        ->and($verification['current'])->toBeTrue()
        ->and($original->getRawOriginal('content'))->not->toContain('38000000')
        ->and($original->getRawOriginal('filename'))->not->toBe('ledger.csv')
        ->and($original->toArray())->not->toHaveKeys(['content', 'filename']);
    $page = app(GetAuditProcedure::class)->handle($user->id, 1, $report->id);
    expect($page['can_continue'])->toBeTrue()->and(array_column($page['sources']['ledger_documents'], 'id'))->toBe([$original->id]);
    $saved = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, 5, 'ledger', ['observed_stock' => '38000000', 'reconciled' => true], (string) Str::uuid());
    expect($saved['code'])->toBe('AUDIT_STEP_SAVED')->and($saved['data']['step'])->toBe('seal');
    $page = app(GetAuditProcedure::class)->handle($user->id, 1, $report->id);
    expect($page['can_continue'])->toBeTrue()
        ->and($page['report']['draft']['sources']['ledger']['ledger:'.$original->id])->toBe(['id' => $original->id, 'revision' => 5, 'sha256' => $original->sha256])
        ->and(array_find($page['seal']['payload']['originals'], fn (array $document): bool => $document['id'] === $original->id))->toBe(['id' => $original->id, 'sha256' => $original->sha256, 'kind' => 'ledger']);
    $this->actingAs($user)->get(route('auditor.reports.show', ['report' => $report->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('stage.evidence.0.kind', 'statement'));
    $history = $report->refresh()->draft;
    $rewound = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, 6, 'check_in', [], (string) Str::uuid());
    expect($rewound['code'])->toBe('AUDIT_STEP_SAVED')->and($report->refresh()->draft)->not->toHaveKey('documents')
        ->and(app(GetAuditProcedure::class)->handle($user->id, 1, $report->id)['sources']['ledger_documents'])->toBe([])
        ->and(AuditReportVersion::query()->where('audit_report_id', $report->id)->where('revision', 6)->firstOrFail()->snapshot['draft'])->toBe($history)
        ->and(app(ReadAuditLedger::class)->handle($user->id, 1, $report->id, $original->id)['content'])->toBe($original->content);
    $this->assertDatabaseCount('statement_originals', 1);
    $this->assertDatabaseCount('audit_ledger_originals', 1);
});

it('separates statement and ledger cards and protects ledger downloads on both transports', function (bool $api): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $this->actingAs($user);
    if ($api) {
        Sanctum::actingAs($user, ['auditor:read', 'auditor:command']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $page = $this->get(route($prefix.'reports.show', ['report' => $report->id]))->assertOk();
    expect($api ? $page->json('data.stage.documents') : $page->viewData('page')['props']['stage']['documents'])->toBe([]);
    $body = "%PDF-1.4\nsynthetic textless source";
    $receipt = app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'private-ledger.pdf', $body, null, (string) Str::uuid());
    $id = $receipt['data']['document_id'];
    $page = $this->get(route($prefix.'reports.show', ['report' => $report->id]))->assertOk();
    $documents = $api ? $page->json('data.stage.documents') : $page->viewData('page')['props']['stage']['documents'];
    expect($documents)->toHaveCount(1)->and($documents[0]['evidence']['kind'])->toBe('ledger')->and($documents[0]['state'])->toBe('scanning');
    $this->get(route($prefix.'reports.ledgers.show', ['report' => $report->id, 'document' => $id]))->assertOk()->assertContent($body)
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox")
        ->assertHeader('Content-Disposition', 'attachment; filename=ledger-'.strtolower(substr($id, -8)).'.pdf');
    $this->get(route($prefix.'reports.statements.show', ['report' => $report->id, 'document' => $id]))->assertNotFound();
    $this->get(route($prefix.'reports.ledgers.show', ['report' => $report->id, 'document' => (string) Str::ulid()]))->assertNotFound();
    if ($api) {
        Sanctum::actingAs($user, ['auditor:command']);
        $this->getJson(route($prefix.'reports.ledgers.show', ['report' => $report->id, 'document' => $id]))->assertForbidden();
    }
})->with([false, true]);

it('does not accept another report original as a replacement or allow its download', function (): void {
    $first = AuditLedgerFixture::ready();
    $second = AuditLedgerFixture::ready();
    $receipt = app(IngestAuditLedger::class)->handle($first['user']->id, 1, $first['report']->id, 4, 'ledger.csv', "amount\n50\n", null, (string) Str::uuid());
    $result = app(IngestAuditLedger::class)->handle($second['user']->id, 1, $second['report']->id, 4, 'rescan.csv', "amount\n51\n", $receipt['data']['document_id'], (string) Str::uuid());
    expect($result['code'])->toBe('STATEMENT_NOT_FOUND')
        ->and(fn () => app(ReadAuditLedger::class)->handle($second['user']->id, 1, $second['report']->id, $receipt['data']['document_id']))->toThrow(CommandRejection::class, 'STATEMENT_NOT_FOUND');
});

it('rejects corrupted source bytes and report document references', function (string $corruption): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $original = AuditLedgerOriginal::factory()->create(['audit_report_id' => $report->id, 'report_revision' => 5,
        'sha256' => $corruption === 'content' ? str_repeat('0', 64) : hash('sha256', "date,amount\n2026-08-01,100\n")]);
    if ($corruption === 'content') {
        expect(fn () => app(ReadAuditLedger::class)->handle($user->id, 1, $report->id, $original->id))->toThrow(RuntimeException::class, 'AUDIT_LEDGER_INTEGRITY_FAILED');

        return;
    }
    $reference = ['id' => $original->id, 'revision' => 5, 'sha256' => $original->sha256, 'replaces' => null];
    $reference[$corruption] = $corruption === 'revision' ? 6 : ($corruption === 'id' ? (string) Str::ulid() : str_repeat('0', 64));
    $report->forceFill(['revision' => 5, 'draft' => [...$report->draft, 'documents' => [$reference]]])->save();
    AuditReportVersion::factory()->forReport($report, $user->party_id, $user->id)->create();
    expect(fn () => app(GetAuditProcedure::class)->handle($user->id, 1, $report->id))->toThrow(RuntimeException::class, 'AUDIT_LEDGER_INTEGRITY_FAILED');
})->with(['content', 'id', 'sha256', 'revision']);

it('protects retained originals and extraction history from database mutation and destructive rollback', function (): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', "amount\n50\n", null, (string) Str::uuid());
    foreach (['audit_ledger_originals', 'audit_ledger_extractions'] as $table) {
        expect(fn () => DB::transaction(fn () => DB::table($table)->delete()))->toThrow(QueryException::class);
    }
    $migration = require database_path('migrations/2026_09_25_120136_create_audit_ledger_evidence_tables.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit ledger evidence requires a forward migration.');
    $this->assertDatabaseCount('audit_ledger_originals', 1);
    $this->assertDatabaseCount('audit_ledger_extractions', 1);
    expect(CommandOperation::query()->where('command', 'audit.save_step')->count())->toBe(4);
});

it('rechecks engagement acceptance before downloads saves uploads and receipt lookups', function (): void {
    $fixture = AuditLedgerFixture::ready();
    ['user' => $user, 'report' => $report] = $fixture;
    $request = (string) Str::uuid();
    $receipt = app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', "amount\n50\n", null, $request);
    AuditEngagementFixture::release($fixture['audit']['staff'], 1);
    $before = CommandOperation::query()->count();
    $this->actingAs($user);
    $this->getJson(route('auditor.reports.ledgers.show', ['report' => $report->id, 'document' => $receipt['data']['document_id']]))
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    $envelope = ['audit_id' => $report->id, 'identity_context_revision' => 1, 'expected_revision' => 5, 'request_id' => (string) Str::uuid(), 'step' => 'ledger'];
    $this->postJson(route('auditor.reports.save', ['report' => $report->id]), [...$envelope, 'observed_stock' => '38000000', 'reconciled' => true])
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    $this->postJson(route('auditor.reports.save', ['report' => $report->id]), [...$envelope, 'document' => UploadedFile::fake()->createWithContent('ledger.csv', "amount\n51\n")])
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    $this->getJson(route('auditor.reports.operations.show', ['request_id' => $request, 'command' => 'audit.save_step']))
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    expect(CommandOperation::query()->count())->toBe($before)->and($report->refresh()->revision)->toBe(5);
});

it('projects parsed and review-required ledger originals with protected download links', function (string $status, string $state): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $receipt = app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', "amount\n50\n", null, (string) Str::uuid());
    AuditLedgerExtraction::factory()->create(['audit_ledger_original_id' => $receipt['data']['document_id'], 'revision' => 2, 'status' => $status]);
    Sanctum::actingAs($user, ['auditor:read']);
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $report->id]))->assertOk()
        ->assertJsonPath('data.stage.documents.0.state', $state)
        ->assertJsonPath('data.stage.documents.0.link.url', route('api.v1.auditor.reports.ledgers.show', ['report' => $report->id, 'document' => $receipt['data']['document_id']], false));
})->with([['text_extracted', 'parsed'], ['needs_review', 'failed']]);
