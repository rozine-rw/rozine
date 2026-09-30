<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationVersion;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessQuoteFixture as Fixture;

/** @return array<string, mixed> */
function persistedAuditReportFixture(string $kind = 'flash'): array
{
    $fixture = Fixture::ready(auditKind: $kind);
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];

    return app(WithAuditApplicationBinding::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['application']->id,
        function (array $assignment, array $binding) use ($fixture, $user): array {
            $report = AuditReport::factory()->forBinding($assignment, $binding)->create();
            $version = AuditReportVersion::factory()->forReport($report, $assignment['party_id'], $user->id)->create();

            return [...$fixture, 'report' => $report, 'version' => $version, 'binding' => $binding, 'accepted' => $assignment];
        });
}

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

it('keeps the submitted source and report drafts encrypted and outside model serialization', function (): void {
    $fixture = persistedAuditReportFixture();
    $report = $fixture['report']->refresh();
    $version = $fixture['version']->refresh();
    $binding = ['assignment' => ['id' => $fixture['accepted']['id'], 'revision' => $fixture['accepted']['revision'],
        'party_id' => $fixture['accepted']['party_id']], 'engagement' => $fixture['accepted']['engagement'], 'application' => $fixture['binding']];

    expect($report->binding)->toBe($binding)
        ->and($report->binding_sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($binding)))
        ->and($report->draft)->toBe(['note' => '', 'completed_steps' => [], 'fields' => []])
        ->and($report->toArray())->not->toHaveKeys(['binding', 'draft'])
        ->and($report->getRawOriginal('binding'))->not->toContain($fixture['application']->id)
        ->and($report->getRawOriginal('draft'))->not->toContain('completed_steps')
        ->and($report->isGuarded('binding'))->toBeTrue()
        ->and($version->snapshot['binding_sha256'])->toBe($report->binding_sha256)
        ->and($version->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($version->snapshot)))
        ->and($version->getRawOriginal('snapshot'))->not->toContain($report->id)
        ->and($version->toArray())->not->toHaveKey('snapshot')
        ->and($version->isGuarded('snapshot'))->toBeTrue();
});

it('freezes source pins and prevents revision skipping while allowing a new draft version', function (): void {
    $fixture = persistedAuditReportFixture();
    $report = $fixture['report'];
    $before = $report->refresh()->getRawOriginal();
    foreach (['id', 'assignment_id', 'assignment_revision', 'author_party_id', 'business_id', 'application_id', 'application_version_id', 'submission_id', 'quote_id', 'amends_id',
        'binding', 'binding_sha256', 'kind', 'created_at', 'application_revision'] as $column) {
        $value = match ($column) {
            'binding', 'binding_sha256' => str_repeat('a', 64), 'kind' => 'monthly',
            'created_at' => now()->subDay(), 'application_revision' => $report->application_revision + 1,
            'assignment_revision' => $report->assignment_revision + 1,
            default => (string) Str::ulid(),
        };
        expect(fn () => DB::transaction(fn (): int => DB::table('audit_reports')->where('id', $report->id)
            ->update([$column => $value, 'revision' => 2])))->toThrow(QueryException::class, 'Audit report source binding is immutable');
    }
    foreach ([0, 1, 3] as $revision) {
        expect(fn () => DB::transaction(fn (): int => DB::table('audit_reports')->where('id', $report->id)
            ->update(['revision' => $revision])))->toThrow(QueryException::class, 'Audit report revision must advance once');
    }
    expect($report->refresh()->getRawOriginal())->toBe($before);
    $report->forceFill(['revision' => 2, 'draft' => [...$report->draft, 'note' => 'Persisted assessment.']])->save();
    $version = AuditReportVersion::factory()->forReport($report, $fixture['accepted']['party_id'], $fixture['audit']['partners'][0]['user']->id)
        ->create(['command' => 'audit.save_step']);

    expect($report->refresh()->revision)->toBe(2)->and($report->draft['note'])->toBe('Persisted assessment.')
        ->and($version->snapshot['draft']['note'])->toBe('Persisted assessment.')
        ->and($fixture['version']->refresh()->snapshot['draft']['note'])->toBe('');
});

it('retains terminal reports and every version against direct writes and deletion', function (string $status): void {
    if ($status === 'sealed') {
        $fixture = AuditSealingFixture::ready();
        AuditSealingFixture::seal($fixture);
        $fixture['version'] = AuditReportVersion::query()->where('audit_report_id', $fixture['report']->id)->firstOrFail();
    } else {
        $fixture = persistedAuditReportFixture(in_array($status, ['changes_requested', 'rejected'], true) ? 'routine' : 'flash');
    }
    $report = $fixture['report']->refresh();
    if ($status !== 'draft') {
        if ($status !== 'sealed') {
            $report->forceFill(['revision' => 2, 'status' => $status, 'step' => 'seal'])->save();
        }
        expect(fn () => DB::transaction(fn (): int => DB::table('audit_reports')->where('id', $report->id)
            ->update(['revision' => 3, 'status' => 'draft'])))->toThrow(QueryException::class, 'Final audit reports and report history are immutable');
    }
    expect(fn () => DB::transaction(fn (): int => DB::table('audit_reports')->where('id', $report->id)->delete()))
        ->toThrow(QueryException::class, 'Final audit reports and report history are immutable');
    foreach (['update', 'delete'] as $operation) {
        expect(fn () => DB::transaction(fn (): int => $operation === 'delete'
            ? DB::table('audit_report_versions')->where('id', $fixture['version']->id)->delete()
            : DB::table('audit_report_versions')->where('id', $fixture['version']->id)->update(['sha256' => str_repeat('0', 64)])))
            ->toThrow(QueryException::class, 'Audit report version history is immutable');
    }
})->with(['draft', 'sealed', 'changes_requested', 'rejected', 'withdrawn']);

it('rejects unrelated source IDs and invalid report states at the database boundary', function (): void {
    $fixture = persistedAuditReportFixture();
    $report = $fixture['report'];
    $other = persistedAuditReportFixture();
    foreach ([['business_id' => $other['audit']['business']], ['application_id' => $other['application']->id],
        ['assignment_id' => $other['assignment']->id], ['application_version_id' => (string) Str::ulid()],
        ['application_revision' => 1], ['submission_id' => (string) Str::ulid()], ['quote_id' => (string) Str::ulid()],
        ['status' => 'approved'], ['revision' => 0], ['kind' => 'flash', 'step' => 'count'], ['kind' => 'monthly', 'step' => 'ledger'],
        ['status' => 'sealed', 'step' => 'review']] as $changes) {
        expect(fn () => DB::transaction(fn (): AuditReport => AuditReport::factory()->forBinding($fixture['accepted'], $fixture['binding'])
            ->create(['amends_id' => $report->id, ...$changes])))->toThrow(QueryException::class);
    }
    expect(fn () => DB::transaction(fn (): AuditReportVersion => AuditReportVersion::factory()
        ->forReport($report, $fixture['accepted']['party_id'], $fixture['audit']['partners'][0]['user']->id)->create()))
        ->toThrow(QueryException::class);
    $this->assertDatabaseCount('audit_reports', 2);
    $this->assertDatabaseCount('audit_report_versions', 2);
});

it('permits one original report and one linked successor per report within the same assignment', function (): void {
    $fixture = AuditSealingFixture::ready();
    $report = $fixture['report'];
    $factory = app(WithAuditApplicationBinding::class)->handle($fixture['user']->id, 1, $fixture['assignment']->id, $fixture['application']->id,
        fn (array $assignment, array $binding) => AuditReport::factory()->forBinding($assignment, $binding));
    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create()))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['amends_id' => $report->id])))
        ->toThrow(QueryException::class, 'Audit amendment requires a terminal report');
    AuditSealingFixture::seal($fixture);
    $amendment = $factory->create(['amends_id' => $report->id]);
    expect($amendment->amends_id)->toBe($report->id);
    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['amends_id' => $report->id])))
        ->toThrow(QueryException::class);
    $other = persistedAuditReportFixture();
    expect(fn () => DB::transaction(fn (): AuditReport => AuditReport::factory()->forBinding($other['accepted'], $other['binding'])
        ->create(['amends_id' => $amendment->id])))->toThrow(QueryException::class);
    $id = (string) Str::ulid();
    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['id' => $id, 'amends_id' => $id])))
        ->toThrow(QueryException::class);
});

it('rejects draft-era versions noncurrent quotes and another accepted authority at the database boundary', function (): void {
    $fixture = persistedAuditReportFixture();
    $application = $fixture['application']->refresh();
    $prior = BusinessApplicationVersion::query()->where('business_application_id', $application->id)
        ->where('revision', '<', $application->revision)->orderByDesc('revision')->firstOrFail();
    $otherQuote = BusinessApplicationQuote::factory()->create(['business_application_id' => $application->id, 'revision' => 2]);
    $factory = AuditReport::factory()->forBinding($fixture['accepted'], $fixture['binding']);
    foreach ([['application_revision' => $prior->revision, 'application_version_id' => $prior->id],
        ['quote_id' => $otherQuote->id], ['submission_id' => (string) Str::ulid()]] as $changes) {
        expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['amends_id' => $fixture['report']->id, ...$changes])))
            ->toThrow(QueryException::class, 'Audit report must bind the current submitted application');
    }
    foreach ([['assignment_revision' => $fixture['accepted']['revision'] + 1], ['author_party_id' => $fixture['audit']['authority']['users'][0]->party_id],
        ['assignment_id' => (string) Str::ulid()]] as $changes) {
        expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['amends_id' => $fixture['report']->id, ...$changes])))
            ->toThrow(QueryException::class, 'Audit report must bind the current accepted Auditor');
    }
    $draft = Fixture::ready();
    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['application_id' => $draft['application']->id,
        'application_revision' => $draft['application']->revision, 'amends_id' => $fixture['report']->id])))
        ->toThrow(QueryException::class, 'Audit report must bind the current submitted application');
    $this->assertDatabaseCount('audit_reports', 1);
});

it('refuses destructive rollback once any report history exists', function (): void {
    persistedAuditReportFixture();
    $migration = require database_path('migrations/2026_09_25_053838_create_audit_reports_and_versions.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit report history requires a forward migration.');
    $this->assertDatabaseCount('audit_reports', 1);
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('rolls the unused report schema back and reapplies it without rewriting legacy application history', function (): void {
    $exposures = $this->createMock(BusinessExposureStore::class);
    $exposures->method('current')->willReturn([]);
    $exposures->expects($this->once())->method('reserve');
    $this->app->instance(BusinessExposureStore::class, $exposures);
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $before = $fixture['application']->refresh()->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_053838_create_audit_reports_and_versions.php');
    $sourceFacts = require database_path('migrations/2026_09_25_102249_create_audit_source_snapshots_table.php');
    $ledgers = require database_path('migrations/2026_09_25_120136_create_audit_ledger_evidence_tables.php');
    $ledgerAuthority = require database_path('migrations/2026_09_25_130201_enforce_audit_ledger_report_authority.php');
    $decisions = require database_path('migrations/2026_09_25_131948_enforce_audit_report_decisions_and_fresh_amendments.php');
    $signing = require database_path('migrations/2026_09_25_134827_create_audit_report_signing_tables.php');
    $publications = require database_path('migrations/2026_09_25_140638_create_audit_report_publication_tables.php');
    $proofLineage = require database_path('migrations/2026_09_25_154051_enforce_audit_seal_proof_and_publication_lineage.php');
    $monthlyReview = require database_path('migrations/2026_09_26_103442_add_monthly_audit_review_policy.php');
    $exposure = require database_path('migrations/2026_09_26_190340_create_business_exposure_reservations_table.php');
    $campaigns = require database_path('migrations/2026_09_27_054238_create_business_application_releases_and_campaigns.php');
    $closures = require database_path('migrations/2026_09_27_230946_create_business_campaign_closures_table.php');
    $primary = require database_path('migrations/2026_09_28_143756_create_primary_reservation_records.php');
    $primaryCapacity = require database_path('migrations/2026_09_28_151253_enforce_primary_campaign_capacity_and_closure.php');
    $primaryCommands = require database_path('migrations/2026_09_28_152823_bind_primary_evidence_to_command_actors.php');
    $primaryOrdinals = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    $walletBindings = require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php');
    $outcomes = require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
    $primarySourceGuard = require database_path('migrations/2026_09_28_165949_reject_unbound_primary_commitment_sources.php');
    $primaryTerminalCash = require database_path('migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php');
    $primaryGuardQuery = "SELECT tgname, pg_get_triggerdef(t.oid) AS definition, pg_get_functiondef(t.tgfoid) AS body FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid WHERE NOT t.tgisinternal AND c.relname IN ('primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'ledger_entries') ORDER BY tgname";
    $primaryGuards = DB::select($primaryGuardQuery);
    $functionQuery = "SELECT p.proname, pg_get_function_identity_arguments(p.oid) AS arguments, pg_get_functiondef(p.oid) AS definition FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = current_schema() AND p.prokind = 'f' AND (p.proname LIKE '%primary%' OR p.proname = 'ledger_entry_balance_check') ORDER BY p.proname, arguments";
    $functions = DB::select($functionQuery);
    $constraintQuery = "SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid IN ('primary_reservations'::regclass, 'primary_reservation_versions'::regclass, 'primary_commitments'::regclass, 'ledger_entries'::regclass) ORDER BY conname";
    $constraints = DB::select($constraintQuery);
    expect(array_column($primaryGuards, 'tgname'))->toContain('primary_reservation_wallet_bound', 'primary_reservation_outcome_bound',
        'primary_version_outcome_bound', 'primary_version_cash_bound', 'ledger_primary_terminal_bound', 'primary_expiry_outcome_bound');
    $confirmationReceipts = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $confirmationOperations = require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
    $expiryFailures = require database_path('migrations/2026_09_29_112938_create_primary_expiry_failures_table.php');
    $fundings = require database_path('migrations/2026_09_30_054318_create_primary_campaign_fundings.php');
    $closureReturns = require database_path('migrations/2026_09_30_094556_bind_campaign_closures_to_complete_primary_returns.php');
    $entryIndex = require database_path('migrations/2026_09_30_171842_index_wallet_ledger_lines_by_entry.php');
    $expirySettlements = require database_path('migrations/2026_09_30_204213_create_primary_campaign_expiry_settlements_table.php');
    $expirySettlements->down();
    $campaignExpiryFailures = require database_path('migrations/2026_09_30_184347_create_business_campaign_expiry_failures_table.php');
    $campaignExpiryFailures->down();
    $entryIndex->down();
    $refundReceipts = require database_path('migrations/2026_09_30_120729_bind_primary_refund_receipts_to_returned_cash.php');
    $refundReceipts->down();
    $closureReturns->down();
    $fundings->down();
    $expiryFailureReasons = require database_path('migrations/2026_09_30_111709_add_reason_code_to_primary_expiry_failures.php');
    $expiryFailureReasons->down();
    $expiryFailures->down();
    $confirmationOperations->down();
    $confirmationReceipts->down();
    $primaryTerminalCash->down();
    $primarySourceGuard->down();
    $outcomes->down();
    $walletBindings->down();
    $primaryOrdinals->down();
    $primaryCommands->down();
    $primaryCapacity->down();
    $primary->down();
    $closures->down();
    $campaigns->down();
    $exposure->down();
    $monthlyReview->down();
    $proofLineage->down();
    $publications->down();
    $signing->down();
    $decisions->down();
    $ledgerAuthority->down();
    $ledgers->down();
    $lineage = require database_path('migrations/2026_09_25_114139_enforce_audit_report_amendment_lineage.php');
    $lineage->down();
    $sourceFacts->down();
    $migration->down();
    expect(Schema::hasTable('audit_reports'))->toBeFalse()->and(Schema::hasTable('audit_report_versions'))->toBeFalse();
    $migration->up();
    $sourceFacts->up();
    $ledgers->up();
    $ledgerAuthority->up();
    $lineage->up();
    $decisions->up();
    $signing->up();
    $publications->up();
    $proofLineage->up();
    $monthlyReview->up();
    $exposure->up();
    $campaigns->up();
    $closures->up();
    $primary->up();
    $primaryCapacity->up();
    $primaryCommands->up();
    $primaryOrdinals->up();
    $walletBindings->up();
    $outcomes->up();
    $primarySourceGuard->up();
    $primaryTerminalCash->up();
    $confirmationReceipts->up();
    $confirmationOperations->up();
    $expiryFailures->up();
    $expiryFailureReasons->up();
    $fundings->up();
    $closureReturns->up();
    $refundReceipts->up();
    $entryIndex->up();
    $campaignExpiryFailures->up();
    $expirySettlements->up();
    expect(DB::select($primaryGuardQuery))->toEqual($primaryGuards)
        ->and(DB::select($functionQuery))->toEqual($functions)->and(DB::select($constraintQuery))->toEqual($constraints);
    expect(Schema::hasTable('audit_reports'))->toBeTrue()->and(Schema::hasTable('audit_report_versions'))->toBeTrue()
        ->and($fixture['application']->refresh()->getRawOriginal())->toBe($before);
});
