<?php

declare(strict_types=1);

use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\ChangeMembership;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GetRoleBookmark;
use App\Application\Identity\RecordConsentRelease;
use App\Application\Identity\ResolveVerifiedOrganization;
use App\Application\Identity\ResolveVerifiedPerson;
use App\Application\Identity\SaveRoleBookmark;
use App\Application\Identity\SelectActiveRole;
use App\Application\Identity\WithCurrentConsent;
use App\Application\Identity\WithVerifiedParties;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplicationVersion;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\ConsentRelease;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleBookmark;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\ConsentFixture;

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
            } catch (IdentityViolation|CommandRejection $exception) {
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

it('deduplicates simultaneous bookmark saves and serializes them against role changes', function (): void {
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());
    $requestId = (string) Str::uuid();
    $save = function () use ($user, $requestId): void {
        app(SaveRoleBookmark::class)->handle($user->id, 'investor', 'investor.home', [], ['section' => 'access'], 1, $requestId);
    };
    expect(runIdentityContenders([$save, $save]))->toBe([0, 0]);
    expect(RoleBookmark::query()->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'bookmark.save')->count())->toBe(1);
    $statuses = runIdentityContenders([
        function () use ($user): void {
            app(SaveRoleBookmark::class)->handle($user->id, 'investor', 'investor.home', [], ['section' => 'overview'], 1, (string) Str::uuid());
        },
        function () use ($user): void {
            app(SelectActiveRole::class)->handle($user->id, 'business', 1, (string) Str::uuid());
        },
    ]);
    expect($statuses)->toBeIn([[0, 0], [0, 2]]);
    expect($user->refresh()->context_revision)->toBe(2);
    expect(fn () => app(GetRoleBookmark::class)->handle($user->id, 'investor'))
        ->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REQUIRED');
});

it('resolves simultaneous registry checks to one canonical organization', function (): void {
    $operations = [];
    for ($index = 0; $index < 2; $index++) {
        $staff = User::factory()->withTwoFactor()->create();
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Registry verifier.', (string) Str::uuid(), ['compliance']);
        $operations[] = function () use ($staff): void {
            app(ResolveVerifiedOrganization::class)->handle($staff->id, 'rdb:concurrent-company',
                'fixture:registry', 'Registry checked.', (string) Str::uuid());
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 0])
        ->and(VerifiedOrganizationIdentity::query()->count())->toBe(1)
        ->and(Party::query()->where('kind', 'organization')->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'organization.resolve')->count())->toBe(2);
});

it('records one outcome for concurrent retries of a registry verification', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Registry verifier.', (string) Str::uuid(), ['compliance']);
    $request = (string) Str::uuid();
    $resolve = function () use ($staff, $request): void {
        app(ResolveVerifiedOrganization::class)->handle($staff->id, 'rdb:concurrent-company',
            'fixture:registry', 'Registry checked.', $request);
    };
    expect(runIdentityContenders([$resolve, $resolve]))->toBe([0, 0])
        ->and(VerifiedOrganizationIdentity::query()->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'organization.resolve')->count())->toBe(1);
});

it('holds scoped staff authority until protected work commits', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Registry verifier.', (string) Str::uuid(), ['compliance']);
    config(['database.connections.identity_contender' => config('database.connections.pgsql')]);
    DB::connection('identity_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();

    app(AuthorizeStaffPermission::class)->handle($staff->id, 'businesses.verify', function () use ($default, $staff): void {
        DB::setDefaultConnection('identity_contender');
        try {
            expect(fn () => app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Read only now.', (string) Str::uuid(), ['analyst']))
                ->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('identity_contender');
        }
    });

    app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Read only now.', (string) Str::uuid(), ['analyst']);
    expect(fn () => app(AuthorizeStaffPermission::class)->handle($staff->id, 'businesses.verify', fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('holds all required party verifications through protected work', function (bool $revokeOrganization): void {
    $organization = VerifiedOrganizationIdentity::factory()->create();
    $person = Party::factory()->verified()->create();
    $target = $revokeOrganization ? $organization->party_id : $person->id;
    config(['database.connections.identity_contender' => config('database.connections.pgsql')]);
    DB::connection('identity_contender')->statement("SET lock_timeout = '500ms'");

    app(WithVerifiedParties::class)->handle('organization', $organization->party_id, [$person->id], function () use ($target): void {
        try {
            expect(fn () => DB::connection('identity_contender')->table('parties')->where('id', $target)->update(['verified_at' => null]))
                ->toThrow(QueryException::class);
        } finally {
            DB::purge('identity_contender');
        }
    });

    Party::query()->whereKey($target)->update(['verified_at' => null]);
    expect(fn () => app(WithVerifiedParties::class)->handle('organization', $organization->party_id, [$person->id], fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
})->with(['organization' => true, 'signatory' => false]);

it('serializes retries across two logins belonging to the same canonical party', function (bool $differentBody): void {
    $party = Party::factory()->verified()->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    DB::table('signup_counters')->insert(['name' => 'operation-journal-test', 'value' => 0]);
    $request = (string) Str::uuid();
    $operations = [];
    for ($index = 0; $index < 2; $index++) {
        $user = User::factory()->for($party)->create();
        app(SelectActiveRole::class)->handle($user->id, 'business', 0, (string) Str::uuid());
        $operations[] = function () use ($party, $user, $request, $index, $differentBody): void {
            app(OperationJournal::class)->execute('party:'.$party->id, $user->id, 'application.save', $request, 'fixture', $party->id,
                ['expected_revision' => 1, 'title' => $differentBody ? 'Choice '.$index : 'Same choice'],
                function (string $type, string $id) use ($user): void {
                    app(AuthorizeActiveRole::class)->handle($user->id, 'business', $id, 1, fn (): bool => true);
                }, function (): OperationResult {
                    DB::table('signup_counters')->where('name', 'operation-journal-test')->increment('value');

                    return new OperationResult('APPLICATION_SAVED', [], 2);
                });
        };
    }
    expect(runIdentityContenders($operations))->toBe($differentBody ? [0, 2] : [0, 0])
        ->and(DB::table('signup_counters')->where('name', 'operation-journal-test')->value('value'))->toBe(1)
        ->and(CommandOperation::query()->count())->toBe(1);
})->with(['identical retry' => false, 'conflicting retry' => true]);

it('does not present an uncommitted command as a recorded outcome during reconnect lookup', function (): void {
    $user = User::factory()->create();
    $key = 'staff:'.$user->id;
    $request = (string) Str::uuid();
    config(['database.connections.operation_lookup' => config('database.connections.pgsql')]);
    $default = DB::getDefaultConnection();
    $authorize = function (string $type, string $id): void {};
    $journal = app(OperationJournal::class);

    $result = $journal->execute($key, $user->id, 'fixture.save', $request, 'fixture', 'one', [], $authorize,
        function () use ($key, $request, $default, $authorize, $journal): OperationResult {
            DB::setDefaultConnection('operation_lookup');
            try {
                expect(fn () => $journal->find($key, 'fixture.save', $request, $authorize))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
            } finally {
                DB::setDefaultConnection($default);
                DB::purge('operation_lookup');
            }

            return new OperationResult('FIXTURE_SAVED', [], 1);
        });
    expect($journal->find($key, 'fixture.save', $request, $authorize))->toBe($result);
});

it('admits one concurrent mandate change from a given revision', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2);
    BusinessAuthorityFixture::configure($fixture);
    $another = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($another->id, true, 'Another verifier.', (string) Str::uuid(), ['compliance']);
    $operations = [];
    foreach ([$fixture['staff'], $another] as $staff) {
        $candidate = [...$fixture, 'staff' => $staff];
        $operations[] = function () use ($candidate): void {
            $result = BusinessAuthorityFixture::configure($candidate, 1);
            if ($result['code'] === 'VERSION_CONFLICT') {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2])
        ->and(BusinessProfile::query()->firstOrFail()->revision)->toBe(2)
        ->and(BusinessMandate::query()->count())->toBe(2)
        ->and(CommandOperation::query()->where('result->code', 'VERSION_CONFLICT')->count())->toBe(1);
});

it('serializes entity mandate revocation against protected business work', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2);
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    $fixture['terms']['status'] = 'revoked';
    config(['database.connections.business_contender' => config('database.connections.pgsql')]);
    DB::connection('business_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();

    app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 1,
        function () use ($fixture, $default): void {
            DB::setDefaultConnection('business_contender');
            try {
                expect(fn () => BusinessAuthorityFixture::configure($fixture, 1))->toThrow(QueryException::class);
            } finally {
                DB::setDefaultConnection($default);
                DB::purge('business_contender');
            }
        });
    BusinessAuthorityFixture::configure($fixture, 1);
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 1, fn (): bool => true))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('admits one competing consent release from a given catalog revision', function (): void {
    $staff = [ConsentFixture::staff(), ConsentFixture::staff()];
    $operations = [];
    foreach ($staff as $actor) {
        $operations[] = function () use ($actor): void {
            $result = ConsentFixture::record($actor);
            if ($result['code'] === 'VERSION_CONFLICT') {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2])
        ->and(ConsentRelease::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('result->code', 'VERSION_CONFLICT')->count())->toBe(1);
});

it('holds current consent documents stable until protected acceptance commits', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    config(['database.connections.consent_contender' => config('database.connections.pgsql')]);
    DB::connection('consent_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $withdraw = fn (): array => app(RecordConsentRelease::class)->handle($staff->id, 1, 'withdrawn', [], [], true,
        'fixture:withdrawal', 'Withdrawal after protected acceptance.', (string) Str::uuid());

    app(WithCurrentConsent::class)->handle(function (?array $release) use ($default, $withdraw): void {
        expect($release['revision'])->toBe(1);
        DB::setDefaultConnection('consent_contender');
        try {
            expect(fn () => $withdraw())->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('consent_contender');
        }
    });
    expect($withdraw()['revision'])->toBe(2)
        ->and(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release))->toBeNull();
});

it('commits a repeated application save once across two logins for the same Party', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $secondLogin = User::factory()->for($fixture['authority']['people'][0])->create();
    app(SelectActiveRole::class)->handle($secondLogin->id, 'business', 0, (string) Str::uuid());
    $fixture['authority']['users'][] = $secondLogin;
    $request = (string) Str::uuid();
    expect(runIdentityContenders([
        function () use ($fixture, $request): void {
            BusinessApplicationFixture::save($fixture, 1, $request, actor: 0);
        },
        function () use ($fixture, $request): void {
            BusinessApplicationFixture::save($fixture, 1, $request, actor: 1);
        },
    ]))->toBe([0, 0])
        ->and($fixture['application']->refresh()->revision)->toBe(2)
        ->and(BusinessApplicationVersion::query()->count())->toBe(2)
        ->and(CommandOperation::query()->where('command', 'application.save')->count())->toBe(1);
});

it('admits one competing application edit from the same saved revision', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $operations = [];
    foreach ([0, 1] as $actor) {
        $operations[] = function () use ($fixture, $actor): void {
            $result = BusinessApplicationFixture::save($fixture, target: $actor === 0 ? '8000000' : '9000000', actor: $actor);
            if ($result['code'] === 'VERSION_CONFLICT') {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2])
        ->and($fixture['application']->refresh()->revision)->toBe(2)
        ->and(BusinessApplicationVersion::query()->count())->toBe(2);
});
