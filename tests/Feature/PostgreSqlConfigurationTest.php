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
    $ledgerMigration->up();
    $engagementMigration->up();
    $sourcePinMigration->up();
    $sourceFactsMigration->up();

    expect(Schema::hasTable('parties'))->toBeTrue()
        ->and(Schema::hasTable('role_memberships'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeTrue()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeTrue()
        ->and($user->refresh()->party_id)->toBeNull()
        ->and(Schema::hasColumn('staff_accounts', 'roles'))->toBeTrue()
        ->and(Schema::hasTable('verified_organization_identities'))->toBeTrue()
        ->and(Schema::hasTable('command_operations'))->toBeTrue()
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
        ->and(Schema::hasColumn('business_applications', 'current_quote_id'))->toBeTrue();
});
