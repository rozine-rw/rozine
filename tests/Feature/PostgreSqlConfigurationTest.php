<?php

declare(strict_types=1);

use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('database tests use PostgreSQL with a dedicated nonprivileged owner', function (): void {
    $identity = DB::selectOne('SELECT current_database() AS database, current_user AS username, rolsuper, rolcreatedb, rolcreaterole FROM pg_roles WHERE rolname = current_user');

    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and($identity->database)->toBe('rozine_test')
        ->and($identity->username)->toBe('rozine_test')
        ->and($identity->rolsuper)->toBeFalse()
        ->and($identity->rolcreatedb)->toBeFalse()
        ->and($identity->rolcreaterole)->toBeFalse()
        ->and(config('queue.batching.database'))->toBe('pgsql')
        ->and(config('queue.failed.database'))->toBe('pgsql');
    expect(config('database.connections.pgsql.timezone'))->toBe('UTC')
        ->and(DB::selectOne('SHOW TIME ZONE')->TimeZone)->toBe('UTC');
    expect(DB::selectOne('SHOW transaction_isolation')->transaction_isolation)->toBe('read committed')
        ->and(DB::selectOne('SHOW default_transaction_isolation')->default_transaction_isolation)->toBe('read committed');
});

test('the test owner cannot connect to the application or demo database', function (): void {
    $access = DB::selectOne("SELECT has_database_privilege(current_user, 'rozine', 'CONNECT') AS application, has_database_privilege(current_user, 'rozine_demo', 'CONNECT') AS demo");

    expect($access->application)->toBeFalse()
        ->and($access->demo)->toBeFalse();
});

test('the identity migration can be rolled back and reapplied on PostgreSQL', function (): void {
    $migration = require database_path('migrations/2026_09_23_101346_create_identity_parties_and_role_memberships.php');
    $accessMigration = require database_path('migrations/2026_09_23_143859_add_controlled_identity_access.php');
    $navigationMigration = require database_path('migrations/2026_09_24_020204_create_staff_access_and_role_bookmarks.php');
    $staffRolesMigration = require database_path('migrations/2026_09_24_042532_add_roles_to_staff_accounts.php');
    $organizationMigration = require database_path('migrations/2026_09_24_050223_create_verified_organization_identities_table.php');
    $operationsMigration = require database_path('migrations/2026_09_24_052148_create_command_operations_table.php');
    $businessMigration = require database_path('migrations/2026_09_24_053517_create_business_profiles_and_mandates.php');
    $consentMigration = require database_path('migrations/2026_09_24_061212_create_consent_releases_table.php');
    $applicationMigration = require database_path('migrations/2026_09_24_063740_create_business_applications_table.php');
    $statementMigration = require database_path('migrations/2026_09_24_070804_create_statement_evidence_tables.php');
    $openDraftMigration = require database_path('migrations/2026_09_24_075833_add_one_open_draft_constraint_to_business_applications.php');
    $transcriptionMigration = require database_path('migrations/2026_09_24_081222_create_statement_transcriptions_table.php');
    $filenameMigration = require database_path('migrations/2026_09_24_085637_encrypt_statement_original_filenames.php');
    $auditorMigration = require database_path('migrations/2026_09_24_093501_create_auditor_profiles_and_accreditation_history.php');
    $locationMigration = require database_path('migrations/2026_09_24_103630_create_audit_locations_and_history.php');
    $independenceMigration = require database_path('migrations/2026_09_24_110246_create_auditor_independence_reviews_and_history.php');
    $assignmentMigration = require database_path('migrations/2026_09_24_113400_create_audit_assignments_and_conflicts.php');
    $verificationMigration = require database_path('migrations/2026_09_24_124527_create_statement_verifications_table.php');
    $creditMigration = require database_path('migrations/2026_09_25_012151_create_business_credit_snapshots_table.php');
    $quoteMigration = require database_path('migrations/2026_09_25_014242_create_business_application_quotes_table.php');
    $acceptanceMigration = require database_path('migrations/2026_09_25_022105_create_business_application_acceptances.php');
    $reportMigration = require database_path('migrations/2026_09_25_053838_create_audit_reports_and_versions.php');
    $engagementMigration = require database_path('migrations/2026_09_25_070105_create_audit_engagement_releases_and_acceptances.php');
    $sourcePinMigration = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    $sourceFactsMigration = require database_path('migrations/2026_09_25_102249_create_audit_source_snapshots_table.php');
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();
    $accountAttributes = $user->refresh()->getAttributes();
    unset($accountAttributes['party_id'], $accountAttributes['active_membership_id'], $accountAttributes['active_membership_revision'], $accountAttributes['context_revision']);

    expect(Schema::hasIndex('users', ['party_id']))->toBeTrue();

    $sourceFactsMigration->down();
    $sourcePinMigration->down();
    $engagementMigration->down();
    $ledgerMigration = require database_path('migrations/2026_09_25_120136_create_audit_ledger_evidence_tables.php');
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
    $primaryWalletBindings = require database_path('migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php');
    $primaryOutcomes = require database_path('migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php');
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
    $walletLedger = require database_path('migrations/2026_09_28_104818_create_investor_wallet_ledger_tables.php');
    $walletInputs = require database_path('migrations/2026_09_28_104819_create_wallet_deposit_policy_method_and_restriction_tables.php');
    $walletDeposits = require database_path('migrations/2026_09_28_104821_create_wallet_deposit_intent_and_outcome_tables.php');
    $ledgerSeal = require database_path('migrations/2026_09_28_112500_seal_ledger_entries_once_validated.php');
    $primaryPostings = require database_path('migrations/2026_09_28_112902_add_primary_postings_to_wallet_ledger.php');
    $postingAnchors = require database_path('migrations/2026_09_28_140000_bind_primary_postings_to_their_source_anchor.php');
    $depositCreditBinding = require database_path('migrations/2026_09_28_175521_bind_deposit_credits_to_their_intent_amounts.php');
    $disbursements = require database_path('migrations/2026_09_29_100000_create_disbursement_tables.php');
    $holdings = require database_path('migrations/2026_09_29_100100_create_primary_holdings_table.php');
    $walletIssue = require database_path('migrations/2026_09_29_100200_add_primary_issue_to_wallet_ledger.php');
    $staffDisjoint = require database_path('migrations/2026_09_29_100300_keep_staff_accounts_and_parties_disjoint.php');
    $closingAuthority = require database_path('migrations/2026_09_29_100400_bind_disbursement_closing_command_authority.php');
    $closingAuthorityShape = fn (): array => [
        DB::select("SELECT column_name, data_type, udt_name, is_nullable, column_default FROM information_schema.columns
            WHERE table_schema = current_schema() AND table_name = 'disbursement_closings' ORDER BY ordinal_position"),
        DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint
            WHERE conrelid = 'disbursement_closings'::regclass ORDER BY conname"),
        DB::select("SELECT pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE tgname = 'disbursement_closings_authority'"),
        DB::select("SELECT pg_get_functiondef(oid) AS definition FROM pg_proc WHERE proname = 'authenticate_disbursement_closing_authority'")];
    $originalClosingAuthority = $closingAuthorityShape();
    $holdingBinding = require database_path('migrations/2026_09_30_084737_bind_primary_holdings_to_retained_commitments.php');
    $holdingIssue = require database_path('migrations/2026_09_30_114217_require_issue_evidence_for_primary_holdings.php');
    $issuedCompleteness = require database_path('migrations/2026_09_30_234802_require_complete_primary_holdings_for_issued_closings.php');
    $issuedCompleteness->down();
    $holdingIssue->down();
    $holdingBinding->down();
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
    $closingAuthority->down();
    expect(Schema::hasColumn('disbursement_closings', 'actor_user_id'))->toBeFalse();
    $staffDisjoint->down();
    $walletIssue->down();
    expect(Schema::hasColumn('ledger_entries', 'cause_id'))->toBeFalse();
    $holdings->down();
    $disbursements->down();
    expect(Schema::hasTable('disbursements'))->toBeFalse()->and(Schema::hasTable('primary_holdings'))->toBeFalse();
    $confirmationOperations->down();
    $confirmationReceipts->down();
    $changeFeed = require database_path('migrations/2026_09_28_180000_create_change_feed_table.php');
    $changeFeed->down();
    expect(Schema::hasTable('change_feed'))->toBeFalse();
    $depositCreditBinding->down();
    expect(DB::scalar("SELECT to_regprocedure('deposit_credit_entry_check(varchar)') IS NULL"))->toBeTrue();
    $primaryTerminalCash->down();
    $primarySourceGuard->down();
    $primaryOutcomes->down();
    $primaryWalletBindings->down();
    $primaryOrdinals->down();
    $primaryCommands->down();
    $primaryCapacity->down();
    $primary->down();
    $postingAnchors->down();
    $primaryPostings->down();
    expect(Schema::hasColumn('ledger_entries', 'origin_operation_id'))->toBeFalse();
    $ledgerSeal->down();
    $walletDeposits->down();
    $walletInputs->down();
    $walletLedger->down();
    expect(Schema::hasTable('investor_wallets'))->toBeFalse()->and(Schema::hasTable('ledger_lines'))->toBeFalse()
        ->and(Schema::hasTable('deposit_policies'))->toBeFalse()->and(Schema::hasTable('wallet_deposit_intents'))->toBeFalse();
    $closures->down();
    $campaigns->down();
    $exposure->down();
    $monthlyReview->down();
    $proofLineage->down();
    $publications->down();
    $signing->down();
    $decisions->down();
    $ledgerAuthority->down();
    $ledgerMigration->down();
    $lineageMigration = require database_path('migrations/2026_09_25_114139_enforce_audit_report_amendment_lineage.php');
    $lineageMigration->down();
    $reportMigration->down();
    $acceptanceMigration->down();
    $quoteMigration->down();
    $creditMigration->down();
    $verificationMigration->down();
    $assignmentMigration->down();
    $independenceMigration->down();
    $locationMigration->down();
    $auditorMigration->down();
    $filenameMigration->down();
    $transcriptionMigration->down();
    $openDraftMigration->down();
    expect(Schema::hasIndex('business_applications', 'business_application_one_draft'))->toBeFalse();
    $statementMigration->down();
    $applicationMigration->down();
    $consentMigration->down();
    $businessMigration->down();
    $operationsMigration->down();
    $organizationMigration->down();
    $staffRolesMigration->down();
    $navigationMigration->down();
    $accessMigration->down();
    $migration->down();

    expect(Schema::hasTable('parties'))->toBeFalse()
        ->and(Schema::hasTable('role_memberships'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeFalse()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeFalse()
        ->and($user->refresh()->getAttributes())->toBe($accountAttributes);

    $migration->up();
    $accessMigration->up();
    $navigationMigration->up();
    $staffRolesMigration->up();
    $organizationMigration->up();
    $operationsMigration->up();
    $businessMigration->up();
    $consentMigration->up();
    $applicationMigration->up();
    $statementMigration->up();
    $openDraftMigration->up();
    $transcriptionMigration->up();
    $filenameMigration->up();
    $auditorMigration->up();
    $locationMigration->up();
    $independenceMigration->up();
    $assignmentMigration->up();
    $verificationMigration->up();
    $creditMigration->up();
    $quoteMigration->up();
    $acceptanceMigration->up();
    $reportMigration->up();
    $lineageMigration->up();
    $decisions->up();
    $ledgerMigration->up();
    $ledgerAuthority->up();
    $engagementMigration->up();
    $sourcePinMigration->up();
    $sourceFactsMigration->up();
    $signing->up();
    $publications->up();
    $proofLineage->up();
    $monthlyReview->up();
    $exposure->up();
    $campaigns->up();
    $closures->up();
    $walletLedger->up();
    $walletInputs->up();
    $walletDeposits->up();
    $ledgerSeal->up();
    $primaryPostings->up();
    $postingAnchors->up();
    $primary->up();
    $primaryCapacity->up();
    $primaryCommands->up();
    $primaryOrdinals->up();
    $primaryWalletBindings->up();
    $primaryOutcomes->up();
    $primarySourceGuard->up();
    $primaryTerminalCash->up();
    $depositCreditBinding->up();
    $changeFeed->up();
    $confirmationReceipts->up();
    $confirmationOperations->up();
    $disbursements->up();
    $holdings->up();
    $walletIssue->up();
    $staffDisjoint->up();
    $closingAuthority->up();
    expect(Schema::hasColumn('disbursement_closings', 'actor_user_id'))->toBeTrue();
    $expiryFailures->up();
    $expiryFailureReasons->up();
    $fundings->up();
    $closureReturns->up();
    $refundReceipts->up();
    $entryIndex->up();
    $campaignExpiryFailures->up();
    $expirySettlements->up();
    $holdingBinding->up();
    $holdingIssue->up();
    $issuedCompleteness->up();
    expect($closingAuthorityShape())->toEqual($originalClosingAuthority);
    expect(DB::scalar("SELECT count(*) FROM pg_trigger WHERE tgname IN ('primary_issued_closing_complete', 'primary_funded_closing_complete')"))->toBe(2);
    expect(DB::select($primaryGuardQuery))->toEqual($primaryGuards)
        ->and(DB::select($functionQuery))->toEqual($functions)->and(DB::select($constraintQuery))->toEqual($constraints);
    expect(DB::selectOne("SELECT count(*) AS total FROM pg_constraint WHERE conname = 'primary_commitment_source_unavailable'")->total)->toBe(1);

    expect(Schema::hasTable('parties'))->toBeTrue()
        ->and(Schema::hasTable('role_memberships'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeTrue()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeTrue()
        ->and($user->refresh()->party_id)->toBeNull()
        ->and(Schema::hasColumn('staff_accounts', 'roles'))->toBeTrue()
        ->and(Schema::hasTable('verified_organization_identities'))->toBeTrue()
        ->and(Schema::hasTable('command_operations'))->toBeTrue()
        ->and(Schema::hasTable('disbursement_closings'))->toBeTrue()
        ->and(Schema::hasTable('primary_holdings'))->toBeTrue()
        ->and(Schema::hasColumn('ledger_entries', 'cause_id'))->toBeTrue()
        ->and(Schema::hasColumn('business_mandates', 'profile'))->toBeTrue()
        ->and(Schema::hasTable('consent_releases'))->toBeTrue()
        ->and(Schema::hasTable('business_application_versions'))->toBeTrue()
        ->and(Schema::hasIndex('business_applications', 'business_application_one_draft'))->toBeTrue()
        ->and(Schema::hasTable('statement_originals'))->toBeTrue()
        ->and(Schema::hasTable('statement_extractions'))->toBeTrue()
        ->and(Schema::hasTable('statement_transcriptions'))->toBeTrue()
        ->and(Schema::hasTable('auditor_profiles'))->toBeTrue()
        ->and(Schema::hasTable('auditor_certificates'))->toBeTrue()
        ->and(Schema::hasTable('auditor_profile_versions'))->toBeTrue()
        ->and(Schema::hasTable('audit_locations'))->toBeTrue()
        ->and(Schema::hasTable('audit_location_versions'))->toBeTrue()
        ->and(Schema::hasTable('auditor_independence_versions'))->toBeTrue()
        ->and(Schema::hasTable('business_credit_snapshots'))->toBeTrue()
        ->and(Schema::hasTable('business_application_quotes'))->toBeTrue()
        ->and(Schema::hasTable('audit_reports'))->toBeTrue()
        ->and(Schema::hasTable('audit_report_versions'))->toBeTrue()
        ->and(Schema::hasTable('audit_engagement_releases'))->toBeTrue()
        ->and(Schema::hasTable('audit_engagement_acceptances'))->toBeTrue()
        ->and(Schema::hasColumn('audit_reports', 'engagement_acceptance_id'))->toBeTrue()
        ->and(Schema::hasColumn('statement_verifications', 'engagement_acceptance_id'))->toBeTrue()
        ->and(Schema::hasTable('audit_source_snapshots'))->toBeTrue()
        ->and(Schema::hasColumn('business_applications', 'current_quote_id'))->toBeTrue()
        ->and(Schema::hasTable('investor_wallets'))->toBeTrue()
        ->and(Schema::hasTable('ledger_lines'))->toBeTrue()
        ->and(Schema::hasTable('investor_funding_methods'))->toBeTrue()
        ->and(Schema::hasTable('wallet_deposit_credits'))->toBeTrue()
        ->and(Schema::hasColumn('ledger_entries', 'origin_operation_id'))->toBeTrue()
        ->and(DB::scalar("SELECT to_regprocedure('deposit_credit_entry_check(varchar)') IS NOT NULL"))->toBeTrue()
        ->and(Schema::hasTable('change_feed'))->toBeTrue();
});
