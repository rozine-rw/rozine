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
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();
    $accountAttributes = $user->refresh()->getAttributes();
    unset($accountAttributes['party_id'], $accountAttributes['active_membership_id'], $accountAttributes['active_membership_revision'], $accountAttributes['context_revision']);

    expect(Schema::hasIndex('users', ['party_id']))->toBeTrue();

    $accessMigration->down();
    $migration->down();

    expect(Schema::hasTable('parties'))->toBeFalse()
        ->and(Schema::hasTable('role_memberships'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeFalse()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeFalse()
        ->and($user->refresh()->getAttributes())->toBe($accountAttributes);

    $migration->up();
    $accessMigration->up();

    expect(Schema::hasTable('parties'))->toBeTrue()
        ->and(Schema::hasTable('role_memberships'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'party_id'))->toBeTrue()
        ->and(Schema::hasIndex('users', ['party_id']))->toBeTrue()
        ->and($user->refresh()->party_id)->toBeNull();
});
