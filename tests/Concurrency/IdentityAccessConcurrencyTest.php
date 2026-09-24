<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\ChangeMembership;
use App\Application\Identity\ResolveVerifiedPerson;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function concurrentIdentityOperator(): User
{
    $user = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $user->id]);

    return $user;
}

/**
 * @param  list<Closure(): void>  $operations
 * @return list<int>
 */
function runIdentityContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork identity contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (IdentityViolation $exception) {
                exit($exception->status === 409 ? 2 : 3);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }

    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exitCode = pcntl_wexitstatus($status);
        $statuses[] = pcntl_wifexited($status) && is_int($exitCode) ? $exitCode : 99;
    }
    sort($statuses);

    return $statuses;
}

it('serializes conflicting Investor and Auditor grants from different operators', function (): void {
    $party = Party::factory()->verified()->create();
    $one = concurrentIdentityOperator();
    $two = concurrentIdentityOperator();
    $operations = [];
    foreach ([[$one, 'investor'], [$two, 'auditor']] as [$operator, $role]) {
        $operations[] = function () use ($operator, $party, $role): void {
            app(ChangeMembership::class)->handle($operator->id, $party->id, $role, 'active', 0,
                'case:concurrency', 'Reviewed grant.', (string) Str::uuid());
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2]);
    expect(RoleMembership::query()->where('party_id', $party->id)->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'membership.change')->count())->toBe(1);
});

it('resolves simultaneous registrations to one canonical person', function (): void {
    $operations = [];
    $users = [];
    for ($index = 0; $index < 2; $index++) {
        $operator = concurrentIdentityOperator();
        $user = User::factory()->for(Party::factory())->create();
        $users[] = $user;
        $operations[] = function () use ($operator, $user): void {
            app(ResolveVerifiedPerson::class)->handle($operator->id, $user->id, 'provider:same-person',
                'case:concurrency', 'Verified person.', (string) Str::uuid());
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 0]);
    expect($users[0]->refresh()->party_id)->toBe($users[1]->refresh()->party_id)
        ->and(VerifiedPersonIdentity::query()->count())->toBe(1);
});

it('admits only one simultaneous role switch from the same context revision', function (): void {
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    $operations = [];
    foreach (['investor', 'business'] as $role) {
        RoleMembership::factory()->for($party)->active()->create(['role' => $role]);
        $operations[] = function () use ($user, $role): void {
            app(SelectActiveRole::class)->handle($user->id, $role, 0, (string) Str::uuid());
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2]);
    expect($user->refresh()->context_revision)->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'role.select')->count())->toBe(1);
});

it('holds authority through a role-scoped operation and denies new work after revocation', function (): void {
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();
    $operator = concurrentIdentityOperator();
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    config(['database.connections.identity_contender' => config('database.connections.pgsql')]);
    DB::connection('identity_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();

    app(AuthorizeActiveRole::class)->handle($user->id, 'investor', $party->id, 1, function () use ($default, $operator, $party): void {
        DB::setDefaultConnection('identity_contender');
        try {
            expect(fn () => app(ChangeMembership::class)->handle($operator->id, $party->id, 'investor', 'revoked', 1,
                'case:revocation', 'Withdraw authority.', (string) Str::uuid()))->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('identity_contender');
        }
    });

    app(ChangeMembership::class)->handle($operator->id, $party->id, 'investor', 'revoked', 1,
        'case:revocation', 'Withdraw authority.', (string) Str::uuid());
    expect(fn () => app(AuthorizeActiveRole::class)->handle($user->id, 'investor', $party->id, 1, fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED');
});
