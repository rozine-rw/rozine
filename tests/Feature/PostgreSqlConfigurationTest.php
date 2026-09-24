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
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();
    $accountAttributes = $user->refresh()->getAttributes();
    unset($accountAttributes['party_id'], $accountAttributes['active_membership_id'], $accountAttributes['active_membership_revision'], $accountAttributes['context_revision']);

    expect(Schema::hasIndex('users', ['party_id']))->toBeTrue();

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

    expect(Schema::hasTable('parties'))->toBeTrue()
        ->and(Schema::hasTable('role_memberships'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeTrue()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeTrue()
        ->and($user->refresh()->party_id)->toBeNull()
        ->and(Schema::hasColumn('staff_accounts', 'roles'))->toBeTrue()
        ->and(Schema::hasTable('verified_organization_identities'))->toBeTrue()
        ->and(Schema::hasTable('command_operations'))->toBeTrue()
        ->and(Schema::hasColumn('business_mandates', 'profile'))->toBeTrue()
        ->and(Schema::hasTable('consent_releases'))->toBeTrue();
});
