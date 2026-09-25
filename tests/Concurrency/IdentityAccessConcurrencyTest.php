<?php

declare(strict_types=1);

use App\Application\Auditor\AdvanceExpiredAuditOffers;
use App\Application\Auditor\GetAuditOperationsCase;
use App\Application\Auditor\GetOwnAuditConflict;
use App\Application\Auditor\ListOwnAuditConflicts;
use App\Application\Auditor\MarkAuditLocationMoved;
use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Auditor\RequestAuditAssignment;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Auditor\WithdrawAuditorAccreditation;
use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\GetAuditApplication;
use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\Contracts\StatementExtractionQueue;
use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\GetStatementVerification;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\ReadAuditStatement;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Evidence\WithBusinessStatementVerification;
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
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\AuditConflictDeclaration;
use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use App\Models\AuditorCertificate;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorIndependenceVersion;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\BusinessCreditSnapshot;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\ConsentRelease;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleBookmark;
use App\Models\RoleMembership;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditorIndependenceFixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;
use Tests\Support\StatementFixture;

function concurrentIdentityOperator(): User
{
    $user = User::factory()->withTwoFactor()->create();
    IdentityOperator::factory()->create(['user_id' => $user->id]);

    return $user;
}

it('holds source authority through the Business calculation effect', function (string $change): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    $operator = concurrentIdentityOperator();
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    AuditAssignmentFixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    DB::table('signup_counters')->insert(['name' => 'underwriting-effect', 'value' => 0]);
    $changeAuthority = function () use ($fixture, $partner, $operator, $change): void {
        if ($change === 'identity') {
            Party::query()->whereKey($partner['party']->id)->update(['verified_at' => null]);
        } elseif ($change === 'standing') {
            AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
        } elseif ($change === 'membership') {
            app(ChangeMembership::class)->handle($operator->id, $partner['party']->id, 'auditor', 'revoked', 1,
                'case:withdrawal', 'Withdraw membership.', (string) Str::uuid());
        } elseif ($change === 'independence') {
            app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $partner['party']->id, 1,
                [...AuditorIndependenceFixture::facts(), 'financial_interest' => true], now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'new:interest', 'Current interest found.', (string) Str::uuid());
        } else {
            app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 2,
                'new.csv', StatementFixture::csv('200'), (string) Str::uuid());
        }
    };
    config(['database.connections.identity_contender' => config('database.connections.pgsql')]);
    DB::connection('identity_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $guard = app(WithBusinessStatementVerification::class);
    $guard->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        function (array $business, array $identity, ?array $verification) use ($default, $changeAuthority): void {
            expect($verification['current'])->toBeTrue();
            DB::setDefaultConnection('identity_contender');
            try {
                expect($changeAuthority)->toThrow(QueryException::class, '55P03');
            } finally {
                DB::setDefaultConnection($default);
                DB::purge('identity_contender');
            }
            DB::table('signup_counters')->where('name', 'underwriting-effect')->increment('value');
        });
    expect(DB::table('signup_counters')->where('name', 'underwriting-effect')->value('value'))->toBe(1);
    $changeAuthority();
    expect($guard->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        fn (array $business, array $identity, ?array $verification): bool => $verification !== null && $verification['current']))->toBeFalse();
})->with(['identity', 'standing', 'membership', 'independence', 'evidence']);

it('leaves unrelated Auditor authority unlocked during a Business calculation', function (): void {
    $fixture = AuditAssignmentFixture::make(2);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = AuditAssignmentFixture::recipient($fixture, $assignment);
    $other = array_values(array_filter($fixture['partners'], fn (array $candidate): bool => $candidate['party']->id !== $partner['party']->id))[0];
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    AuditAssignmentFixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id']);
    config(['database.connections.identity_contender' => config('database.connections.pgsql')]);
    DB::connection('identity_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    app(WithBusinessStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 'application.evaluate', 1,
        function (array $business, array $identity, ?array $verification) use ($default, $other): void {
            expect($verification['current'])->toBeTrue();
            DB::setDefaultConnection('identity_contender');
            try {
                expect(AuditorFixture::review($other['staff'], $other['party']->id, 3, 'suspend')['code'])->toBe('ACCREDITATION_REVIEWED');
            } finally {
                DB::setDefaultConnection($default);
                DB::purge('identity_contender');
            }
        });
});

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

it('creates or resumes one draft under simultaneous create commands from different mandate holders', function (): void {
    $authority = BusinessAuthorityFixture::make('organization', 2);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    $operations = [];
    foreach ($authority['users'] as $user) {
        $request = (string) Str::uuid();
        $operations[] = function () use ($user, $business, $request): void {
            app(CreateBusinessApplication::class)->handle($user->id, 1, $business->id, 0, $request);
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 0])
        ->and(BusinessApplication::query()->count())->toBe(1)
        ->and(BusinessApplicationVersion::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'application.create')->count())->toBe(2)
        ->and(CommandOperation::query()->where('result->code', 'APPLICATION_CREATED')->count())->toBe(1)
        ->and(CommandOperation::query()->where('result->code', 'APPLICATION_RESUMED')->count())->toBe(1);
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

it('serializes competing reconciliation revisions under the Business authority lock', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $operations = [];
    foreach ([0, 1] as $actor) {
        $operations[] = function () use ($fixture, $input, $actor): void {
            $result = StatementFixture::transcribe($fixture, $input, actor: $actor);
            if ($result['code'] === 'VERSION_CONFLICT') {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2])
        ->and(StatementTranscription::query()->count())->toBe(1)
        ->and(StatementEvidence::query()->firstOrFail()->revision)->toBe(2);
});

it('records an identical reconciliation retry once across two logins for one Party', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $source = StatementFixture::ingest($fixture);
    $input = StatementFixture::transcription($source['data']['document_id']);
    $second = User::factory()->for($fixture['authority']['people'][0])->create();
    app(SelectActiveRole::class)->handle($second->id, 'business', 0, (string) Str::uuid());
    $fixture['authority']['users'][] = $second;
    $request = (string) Str::uuid();
    expect(runIdentityContenders([
        function () use ($fixture, $input, $request): void {
            StatementFixture::transcribe($fixture, $input, requestId: $request);
        },
        function () use ($fixture, $input, $request): void {
            StatementFixture::transcribe($fixture, $input, requestId: $request, actor: 1);
        },
    ]))->toBe([0, 0])
        ->and(StatementTranscription::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.reconcile')->count())->toBe(1)
        ->and(StatementEvidence::query()->firstOrFail()->revision)->toBe(2);
});

it('commits one immutable statement receipt across simultaneous logins of the same Party', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $second = User::factory()->for($fixture['authority']['people'][0])->create();
    app(SelectActiveRole::class)->handle($second->id, 'business', 0, (string) Str::uuid());
    $fixture['authority']['users'][] = $second;
    $request = (string) Str::uuid();
    expect(runIdentityContenders([
        function () use ($fixture, $request): void {
            StatementFixture::ingest($fixture, requestId: $request);
        },
        function () use ($fixture, $request): void {
            StatementFixture::ingest($fixture, requestId: $request, actor: 1);
        },
    ]))->toBe([0, 0])
        ->and(StatementEvidence::query()->firstOrFail()->revision)->toBe(1)
        ->and(StatementOriginal::query()->count())->toBe(1)
        ->and(StatementExtraction::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.ingest')->count())->toBe(1);
});

it('admits one competing statement import against the same evidence revision', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $operations = [];
    foreach ([0, 1] as $actor) {
        $operations[] = function () use ($fixture, $actor): void {
            $result = StatementFixture::ingest($fixture, amount: $actor === 0 ? '100' : '200', actor: $actor);
            if ($result['code'] === 'VERSION_CONFLICT') {
                throw new CommandRejection('VERSION_CONFLICT');
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 2])
        ->and(StatementEvidence::query()->firstOrFail()->revision)->toBe(1)
        ->and(StatementOriginal::query()->count())->toBe(1);
});

it('appends one terminal extraction when committed workers race on the same original', function (): void {
    $fixture = BusinessApplicationFixture::make();
    StatementFixture::ingest($fixture);
    $process = function (): void {
        app(StatementExtractionQueue::class)->processPending(1);
    };
    expect(runIdentityContenders([$process, $process]))->toBe([0, 0])
        ->and(StatementExtraction::query()->orderBy('revision')->pluck('revision')->all())->toBe([1, 2])
        ->and(StatementExtraction::query()->where('revision', 2)->firstOrFail()->status)->toBe('text_extracted')
        ->and(StatementOriginal::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.ingest')->count())->toBe(1);
});

it('commits one accreditation original and receipt for concurrent same Party retries', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    $operation = function () use ($fixture, $request): void {
        $result = AuditorFixture::submit($fixture['user'], request: $request);
        if ($result['code'] !== 'ACCREDITATION_SUBMITTED') {
            throw new RuntimeException('Accreditation retry failed.');
        }
    };
    expect(runIdentityContenders([$operation, $operation]))->toBe([0, 0]);
    expect(AuditorProfile::query()->count())->toBe(1)
        ->and(AuditorCertificate::query()->count())->toBe(1)
        ->and(AuditorProfileVersion::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'accreditation.submit')->count())->toBe(1);
});

it('serializes staff approval against Auditor withdrawal without reviving withdrawn claims', function (): void {
    $fixture = AuditorFixture::make();
    $submitted = AuditorFixture::submit($fixture['user']);
    $approve = function () use ($fixture, $submitted): void {
        $result = AuditorFixture::review($fixture['staff'], $fixture['party']->id, 1, 'approve', $submitted['data']['submission_id']);
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    $withdraw = function () use ($fixture, $submitted): void {
        $result = app(WithdrawAuditorAccreditation::class)->handle($fixture['user']->id, 1, 1,
            $submitted['data']['submission_id'], (string) Str::uuid());
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    expect(runIdentityContenders([$approve, $withdraw]))->toBe([0, 2]);
    $profile = AuditorProfile::query()->firstOrFail();
    $winner = CommandOperation::query()->whereIn('command', ['accreditation.review', 'accreditation.withdraw'])->get()
        ->first(fn (CommandOperation $operation): bool => $operation->result['http_status'] === 200);
    expect($profile->revision)->toBe(2)->and($profile->state['submission']['status'])->toBe('none')
        ->and($profile->state['standing']['status'])->toBe($winner?->command === 'accreditation.review' ? 'active' : 'none')
        ->and(AuditorCertificate::query()->count())->toBe(1)
        ->and(AuditorProfileVersion::query()->count())->toBe(2);
});

it('records one verified location and history for concurrent identical staff retries', function (): void {
    $fixture = AuditorFixture::make();
    $request = (string) Str::uuid();
    $time = now('UTC')->format('Y-m-d\TH:i:s\Z');
    $verify = function () use ($fixture, $request, $time): void {
        $result = app(VerifyAuditLocation::class)->handle($fixture['staff']->id, 'office', $fixture['party']->id, 0,
            '-1.9441', '30.0619', 20, $time, 'synthetic:office', 'Reviewed synthetic location.', $request);
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    expect(runIdentityContenders([$verify, $verify]))->toBe([0, 0])
        ->and(AuditLocation::query()->count())->toBe(1)
        ->and(AuditLocationVersion::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'audit.location.verify')->count())->toBe(1);
});

it('serializes a move against re-verification so stale evidence cannot replace the winning revision', function (): void {
    $fixture = AuditorFixture::make();
    $time = now('UTC')->format('Y-m-d\TH:i:s\Z');
    $verify = function (int $revision) use ($fixture, $time): void {
        $result = app(VerifyAuditLocation::class)->handle($fixture['staff']->id, 'office', $fixture['party']->id, $revision,
            '-1.9441', '30.0619', 20, $time, 'synthetic:office', 'Reviewed synthetic location.', (string) Str::uuid());
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    $verify(0);
    $moved = function () use ($fixture, $time): void {
        $result = app(MarkAuditLocationMoved::class)->handle($fixture['staff']->id, 'office', $fixture['party']->id, 1,
            $time, 'Office moved.', (string) Str::uuid());
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    expect(runIdentityContenders([fn () => $verify(1), $moved]))->toBe([0, 2]);
    $location = AuditLocation::query()->firstOrFail();
    $history = AuditLocationVersion::query()->where('revision', 2)->firstOrFail();
    expect($location->revision)->toBe(2)->and($location->state)->toBe($history->snapshot['state']);
    $movedWon = $history->getAttribute('command') === 'audit.location.moved';
    expect($location->state['point'] === null)->toBe($movedWon)
        ->and(AuditLocationVersion::query()->count())->toBe(2);
});

it('records one independence review for concurrent identical Operations retries', function (): void {
    $fixture = AuditorIndependenceFixture::make();
    $request = (string) Str::uuid();
    $this->freezeTime();
    $record = function () use ($fixture, $request): void {
        $result = AuditorIndependenceFixture::record($fixture, request: $request);
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    expect(runIdentityContenders([$record, $record]))->toBe([0, 0])
        ->and(AuditorIndependenceReview::query()->count())->toBe(1)
        ->and(AuditorIndependenceVersion::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'audit.independence.review')->count())->toBe(1);
});

it('locks crossed Business and Auditor identities without reverse-order Party deadlocks', function (): void {
    $left = AuditorIndependenceFixture::make('person', 1);
    $right = AuditorIndependenceFixture::make('person', 1);
    $left['party'] = $right['authority']['people'][0];
    $right['party'] = $left['authority']['people'][0];
    $operations = [];
    foreach ([$left, $right] as $fixture) {
        $operations[] = function () use ($fixture): void {
            DB::transaction(function () use ($fixture): void {
                $result = AuditorIndependenceFixture::record($fixture);
                if ($result['http_status'] !== 200) {
                    throw new CommandRejection($result['code'], $result['http_status']);
                }
            });
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 0])
        ->and(AuditorIndependenceReview::query()->count())->toBe(2)
        ->and(AuditorIndependenceVersion::query()->count())->toBe(2);
});

it('creates one audit offer for simultaneous identical Operations requests', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make();
    $request = (string) Str::uuid();
    $operation = function () use ($fixture, $request): void {
        AuditAssignmentFixture::request($fixture, requestId: $request);
    };
    expect(runIdentityContenders([$operation, $operation]))->toBe([0, 0])
        ->and(AuditAssignment::query()->count())->toBe(1)
        ->and(AuditAssignmentVersion::query()->count())->toBe(1);
});

it('admits only one competing acceptance when an Auditor has two active engagements', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(1);
    $other = AuditAssignmentFixture::make(0);
    $partner = $fixture['partners'][0];
    AuditAssignmentFixture::independence($other['staff'], $other['business'], $partner['party']->id);
    $left = AuditAssignmentFixture::request($fixture);
    $right = AuditAssignmentFixture::request($other);
    AuditAssignmentFixture::engagement($partner['party']->id);
    AuditAssignmentFixture::engagement($partner['party']->id);
    $linked = User::factory()->withTwoFactor()->for($partner['party'])->create();
    app(SelectActiveRole::class)->handle($linked->id, 'auditor', 0, (string) Str::uuid());
    $operations = [];
    foreach ([$left, $right] as $index => $record) {
        $user = $index === 0 ? $partner['user'] : $linked;
        $operations[] = function () use ($user, $record): void {
            $receipt = AuditAssignmentFixture::respond($user, $record);
            if ($receipt['http_status'] !== 200) {
                throw new CommandRejection($receipt['code'], $receipt['http_status']);
            }
        };
    }
    expect(runIdentityContenders($operations))->toBe([0, 3])
        ->and(AuditAssignment::query()->where('party_id', $partner['party']->id)->where('status', 'accepted')->count())->toBe(3);
});

it('advances an expired audit only once across duplicate scheduler workers', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make();
    $record = AuditAssignmentFixture::request($fixture);
    $this->travel(1)->hours();
    $worker = function (): void {
        app(AdvanceExpiredAuditOffers::class)->handle(100);
    };
    expect(runIdentityContenders([$worker, $worker]))->toBe([0, 0])
        ->and($record->refresh()->state['attempt'])->toBe(2)
        ->and(AuditAssignmentVersion::query()->count())->toBe(2);
});

it('never accepts an offer at its deadline while the expiry worker reoffers it', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make();
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = AuditAssignmentFixture::recipient($fixture, $assignment);
    $this->travel(1)->hours();
    expect(runIdentityContenders([
        function () use ($partner, $assignment): void {
            expect(AuditAssignmentFixture::respond($partner['user'], $assignment)['code'])->toBe('ASSIGNMENT_ACCEPTANCE_EXPIRED');
        },
        function (): void {
            app(AdvanceExpiredAuditOffers::class)->handle(100);
        },
    ]))->toBe([0, 0]);
    expect($assignment->refresh()->revision)->toBe(2)->and($assignment->state['attempt'])->toBe(2)
        ->and($assignment->status)->toBe('offered')->and($assignment->party_id)->not->toBe($partner['party']->id);
});

it('does not lock an unrelated Auditor during protected accepted evidence reads', function (): void {
    $fixture = AuditAssignmentFixture::make(2);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = AuditAssignmentFixture::recipient($fixture, $assignment);
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $other = array_values(array_filter($fixture['partners'], fn (array $candidate): bool => $candidate['party']->id !== $partner['party']->id))[0];
    config(['database.connections.unrelated_auditor' => config('database.connections.pgsql')]);
    DB::connection('unrelated_auditor')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    app(WithAcceptedAuditAssignment::class)->handle($partner['user']->id, 1, $assignment->id, function () use ($other, $default): void {
        DB::setDefaultConnection('unrelated_auditor');
        try {
            expect(app(SetAuditorAvailability::class)->handle($other['user']->id, 1, 3, false, (string) Str::uuid())['code'])->toBe('AVAILABILITY_UPDATED');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('unrelated_auditor');
        }
    });
    expect(AuditorProfile::query()->where('party_id', $other['party']->id)->firstOrFail()->state['accepting'])->toBeFalse();
});

it('serializes a Business conflict against another Business dispatch for the same Auditor', function (): void {
    $this->freezeTime();
    $first = AuditAssignmentFixture::make(1);
    $second = AuditAssignmentFixture::make(0);
    $partner = $first['partners'][0];
    AuditAssignmentFixture::independence($second['staff'], $second['business'], $partner['party']->id);
    $assignment = AuditAssignmentFixture::request($first);
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    expect(runIdentityContenders([
        function () use ($partner, $assignment): void {
            AuditAssignmentFixture::respond($partner['user'], $assignment, 'conflict', 'Interest in the first Business.', 'financial_interest');
        },
        function () use ($second): void {
            AuditAssignmentFixture::request($second);
        },
    ]))->toBe([0, 0]);
    expect($assignment->refresh()->status)->toBe('operations')
        ->and(AuditAssignment::query()->where('business_id', $second['business'])->firstOrFail()->party_id)->toBe($partner['party']->id)
        ->and(AuditConflictDeclaration::query()->count())->toBe(1);
});

it('commits one conflict and one reassignment for concurrent identical declarations', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make();
    $record = AuditAssignmentFixture::request($fixture);
    $partner = AuditAssignmentFixture::recipient($fixture, $record);
    $request = (string) Str::uuid();
    $declare = function () use ($partner, $record, $request): void {
        AuditAssignmentFixture::respond($partner['user'], $record, 'conflict', 'Current private relationship.', 'other', $request);
    };
    expect(runIdentityContenders([$declare, $declare]))->toBe([0, 0])
        ->and(AuditConflictDeclaration::query()->count())->toBe(1)
        ->and(AuditAssignmentVersion::query()->count())->toBe(2)
        ->and($record->refresh()->state['attempt'])->toBe(2);
});

it('orders assignment locks consistently against office invalidation and availability changes', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $operations = [
        function () use ($fixture): void {
            DB::transaction(fn () => AuditAssignmentFixture::request($fixture));
        },
        function () use ($partner): void {
            DB::transaction(function () use ($partner): void {
                app(MarkAuditLocationMoved::class)->handle($partner['staff']->id, 'office', $partner['party']->id, 1,
                    now('UTC')->format('Y-m-d\TH:i:s\Z'), 'Moved office.', (string) Str::uuid());
            });
        },
        function () use ($partner): void {
            DB::transaction(fn () => app(SetAuditorAvailability::class)->handle($partner['user']->id, 1, 3, false, (string) Str::uuid()));
        },
    ];
    expect(runIdentityContenders($operations))->toBe([0, 0, 0]);
    $record = AuditAssignment::query()->firstOrFail();
    if ($record->status === 'offered') {
        expect(AuditAssignmentFixture::respond($partner['user'], $record)['http_status'])->toBe(403);
    }
    expect(AuditorProfile::query()->firstOrFail()->state['accepting'])->toBeFalse()
        ->and(AuditLocation::query()->where('office_party_id', $partner['party']->id)->firstOrFail()->state['point'])->toBeNull();
});

it('holds accepted-assignment and Business authority throughout a protected statement read', function (string $fault): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    $operator = concurrentIdentityOperator();
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $change = match ($fault) {
        'conflict' => fn (): array => AuditAssignmentFixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest'),
        'evidence' => fn (): array => app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 2,
            'new.csv', StatementFixture::csv('200'), (string) Str::uuid()),
        'standing' => fn (): array => AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend'),
        default => fn (): array => app(ChangeMembership::class)->handle($operator->id, $partner['party']->id, 'auditor', 'revoked', 1,
            'case:revocation', 'Withdraw membership.', (string) Str::uuid()),
    };
    config(['database.connections.audit_statement_contender' => config('database.connections.pgsql')]);
    DB::connection('audit_statement_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();

    app(WithAcceptedAuditAssignment::class)->handle($partner['user']->id, 1, $assignment->id, function () use ($default, $change, $partner, $assignment, $sources): void {
        DB::setDefaultConnection('audit_statement_contender');
        try {
            $change();
            $this->fail('A competing authority change must wait for the protected read.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('audit_statement_contender');
        }
        expect(app(ReadAuditStatement::class)->handle($partner['user']->id, 1, $assignment->id, $sources['document_id'])['content'])->toBe(StatementFixture::csv());
    });
    $change();
    if ($fault === 'evidence') {
        $file = app(GetAuditStatements::class)->handle($partner['user']->id, 1, $assignment->id);
        expect($file['evidence']['revision'])->toBe(3)->and($file['transcription']['current'])->toBeFalse();
    } else {
        try {
            app(ReadAuditStatement::class)->handle($partner['user']->id, 1, $assignment->id, $sources['document_id']);
            $this->fail('New reads must observe the committed authority withdrawal.');
        } catch (IdentityViolation|CommandRejection $exception) {
            expect($exception->getMessage())->toBe(match ($fault) {
                'conflict' => 'ASSIGNMENT_NOT_FOUND', 'standing' => 'ACCREDITATION_SUSPENDED', default => 'ROLE_MEMBERSHIP_REQUIRED',
            });
        }
    }
})->with(['conflict', 'evidence', 'standing', 'membership']);

it('records identical concurrent source verifications once', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    AuditAssignmentFixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    $request = (string) Str::uuid();
    $verify = function () use ($fixture, $assignment, $sources, $request): void {
        AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    };
    expect(runIdentityContenders([$verify, $verify]))->toBe([0, 0]);
    expect(StatementVerification::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.verify')->count())->toBe(1);
});

it('admits one of two competing factual corrections from the same source review revision', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    AuditAssignmentFixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();
    $verify = function () use ($fixture, $assignment, $sources): void {
        AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    };
    expect(runIdentityContenders([$verify, $verify]))->toBe([0, 0]);
    expect(StatementVerification::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'statement.verify')->orderBy('id')->get()->map(fn (CommandOperation $operation): string => $operation->result['code'])->sort()->values()->all())
        ->toBe(['STATEMENT_SOURCE_VERIFIED', 'VERSION_CONFLICT']);
});

it('cannot leave a source verification current after a racing source or authority change', function (string $change): void {
    $fixture = AuditAssignmentFixture::make(1);
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    $operator = concurrentIdentityOperator();
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $operations = [
        function () use ($fixture, $assignment, $sources): void {
            try {
                AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
            } catch (IdentityViolation|CommandRejection $exception) {
                if (! in_array($exception->getMessage(), ['ASSIGNMENT_NOT_FOUND', 'ACCREDITATION_SUSPENDED', 'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED', 'ROLE_MEMBERSHIP_REQUIRED'], true)) {
                    throw $exception;
                }
            }
        },
        function () use ($fixture, $assignment, $partner, $sources, $operator, $change): void {
            if ($change === 'conflict') {
                AuditAssignmentFixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
            } elseif ($change === 'transcription') {
                $input = StatementFixture::transcription($sources['document_id']);
                app(RecordStatementTranscription::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 2,
                    $input['rails'], $input['months'], $input['statements'], (string) Str::uuid());
            } elseif ($change === 'review') {
                app(RecordAuditorIndependence::class)->handle($fixture['staff']->id, $fixture['business'], $partner['party']->id, 1,
                    [...AuditorIndependenceFixture::facts(), 'financial_interest' => true], now('UTC')->format('Y-m-d\TH:i:s\Z'),
                    'new:interest', 'Current interest found.', (string) Str::uuid());
            } elseif ($change === 'standing') {
                AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
            } elseif ($change === 'membership') {
                app(ChangeMembership::class)->handle($operator->id, $partner['party']->id, 'auditor', 'revoked', 1,
                    'case:revocation', 'Withdraw membership.', (string) Str::uuid());
            } else {
                app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 2,
                    'new.csv', StatementFixture::csv('200'), (string) Str::uuid());
            }
        },
    ];
    expect(runIdentityContenders($operations))->toBe([0, 0]);
    $snapshot = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    expect($snapshot === null || ! $snapshot['current'])->toBeTrue();
})->with(['evidence', 'conflict', 'transcription', 'review', 'standing', 'membership']);

it('serializes replacement Auditor attestation against a former Auditors competing amendment', function (): void {
    $fixture = AuditAssignmentFixture::make();
    $sources = AuditAssignmentFixture::statements($fixture);
    $assignment = AuditAssignmentFixture::request($fixture);
    $former = AuditAssignmentFixture::recipient($fixture, $assignment);
    AuditAssignmentFixture::respond($former['user'], $assignment);
    $assignment->refresh();
    $first = AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    expect(runIdentityContenders([
        function () use ($fixture, $assignment, $sources): void {
            try {
                AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: 1);
            } catch (CommandRejection $exception) {
                if ($exception->reason !== 'ASSIGNMENT_NOT_FOUND') {
                    throw $exception;
                }
            }
        },
        function () use ($fixture, $assignment, $sources, $former): void {
            AuditAssignmentFixture::respond($former['user'], $assignment, 'conflict', 'New financial interest.', 'financial_interest');
            $assignment->refresh();
            $replacement = AuditAssignmentFixture::recipient($fixture, $assignment);
            AuditAssignmentFixture::respond($replacement['user'], $assignment);
            $assignment->refresh();
            $previous = StatementVerification::query()->orderByDesc('revision')->firstOrFail();
            $receipt = AuditAssignmentFixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: $previous->revision);
            if ($receipt['code'] !== 'STATEMENT_SOURCE_VERIFIED') {
                throw new RuntimeException('Replacement attestation must follow the serialized former amendment.');
            }
        },
    ]))->toBe([0, 0]);
    $read = app(GetStatementVerification::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']);
    expect($read['current'])->toBeTrue()->and($read['payload']['assignment']['party_id'])->not->toBe($former['party']->id)
        ->and(StatementVerification::query()->whereKey($first['data']['verification_id'])->firstOrFail()->sha256)->toBe($first['data']['sha256'])
        ->and(StatementVerification::query()->count())->toBeIn([2, 3]);
});

it('holds the own-conflict identity guard through private receipt and register reads', function (string $mode): void {
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = AuditAssignmentFixture::request($fixture);
    AuditAssignmentFixture::respond($partner['user'], $assignment, 'conflict', 'Private relationship.', 'other');
    $operator = concurrentIdentityOperator();
    $revoke = fn (): array => app(ChangeMembership::class)->handle($operator->id, $partner['party']->id, 'auditor', 'revoked', 1,
        'case:revocation', 'Withdraw membership.', (string) Str::uuid());
    config(['database.connections.conflict_receipt_contender' => config('database.connections.pgsql')]);
    DB::connection('conflict_receipt_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $event = 'eloquent.retrieved: '.AuditConflictDeclaration::class;
    Event::listen($event, function () use ($default, $revoke): void {
        DB::setDefaultConnection('conflict_receipt_contender');
        try {
            $revoke();
            $this->fail('Membership withdrawal must wait until the own-conflict projection completes.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('conflict_receipt_contender');
        }
    });
    $read = $mode === 'receipt'
        ? fn (): array => app(GetOwnAuditConflict::class)->handle($partner['user']->id, 1, $assignment->id)
        : fn (): array => app(ListOwnAuditConflicts::class)->handle($partner['user']->id, 1);
    try {
        expect(json_encode($read(), JSON_THROW_ON_ERROR))->toContain('Private relationship.');
    } finally {
        Event::forget($event);
    }
    $revoke();
    expect($read)->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED');
})->with(['receipt', 'register']);

it('holds current offered assignment and Business authority through the actual application summary read', function (string $fault): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $owner = $fixture['authority']['users'][0];
    $created = app(CreateBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], 0, (string) Str::uuid());
    $id = $created['data']['application']['id'];
    $assignment = AuditAssignmentFixture::request($fixture);
    $operator = concurrentIdentityOperator();
    $change = match ($fault) {
        'conflict' => fn (): array => AuditAssignmentFixture::respond($partner['user'], $assignment, 'conflict', 'New financial interest.', 'other'),
        'draft' => fn (): array => app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], $id, 1,
            BusinessApplicationFixture::fields(), 'raise', (string) Str::uuid()),
        'standing' => fn (): array => AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend'),
        default => fn (): array => app(ChangeMembership::class)->handle($operator->id, $partner['party']->id, 'auditor', 'revoked', 1,
            'case:revocation', 'Withdraw membership.', (string) Str::uuid()),
    };
    config(['database.connections.audit_jobs_contender' => config('database.connections.pgsql')]);
    DB::connection('audit_jobs_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $event = 'eloquent.retrieved: '.BusinessApplication::class;
    Event::listen($event, function () use ($default, $change): void {
        DB::setDefaultConnection('audit_jobs_contender');
        try {
            $change();
            $this->fail('The case summary must hold assignment and Business authority through its read.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('audit_jobs_contender');
        }
    });
    try {
        expect(app(GetAuditApplication::class)->handle($partner['user']->id, 1, $assignment->id)['application']['target'])->toBeNull();
    } finally {
        Event::forget($event);
    }
    $change();
    if ($fault === 'draft') {
        expect(app(GetAuditApplication::class)->handle($partner['user']->id, 1, $assignment->id)['application']['target'])->toBe('8000000');
    } else {
        expect(fn () => app(GetAuditApplication::class)->handle($partner['user']->id, 1, $assignment->id))->toThrow(match ($fault) {
            'membership' => IdentityViolation::class, default => CommandRejection::class,
        });
    }
})->with(['conflict', 'draft', 'standing', 'membership']);

it('serializes duplicate and competing Operations resolutions without restarting a closed case', function (bool $duplicate): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(0);
    $assignment = AuditAssignmentFixture::request($fixture);
    $requestId = (string) Str::uuid();
    $close = function () use ($fixture, $assignment, $requestId): void {
        $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'close', 'Close case.', $requestId);
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    $redispatch = function () use ($fixture, $assignment): void {
        $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'redispatch', 'Check candidates.', (string) Str::uuid());
        if ($result['http_status'] !== 200) {
            throw new CommandRejection($result['code'], $result['http_status']);
        }
    };
    expect(runIdentityContenders([$close, $duplicate ? $close : $redispatch]))->toBe($duplicate ? [0, 0] : [0, 2])
        ->and($assignment->refresh()->revision)->toBe(2)->and($assignment->state['attempt'])->toBe(0)
        ->and($assignment->state['original_dispatch_at'])->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'))
        ->and(AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->count())->toBe(2);
})->with([true, false]);

it('holds staff assignment-management authority through an Operations case read', function (): void {
    $fixture = AuditAssignmentFixture::make(0);
    $assignment = AuditAssignmentFixture::request($fixture);
    $withdraw = fn (): array => app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Withdraw management.', (string) Str::uuid(), ['analyst']);
    config(['database.connections.audit_operations_contender' => config('database.connections.pgsql')]);
    DB::connection('audit_operations_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $event = 'eloquent.retrieved: '.AuditAssignment::class;
    Event::listen($event, function () use ($default, $withdraw): void {
        if (DB::transactionLevel() === 0) {
            return;
        }
        DB::setDefaultConnection('audit_operations_contender');
        try {
            $withdraw();
            $this->fail('Staff revocation must wait for the Operations projection.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('audit_operations_contender');
        }
    });
    try {
        expect(app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id)['status'])->toBe('operations');
    } finally {
        Event::forget($event);
    }
    $withdraw();
    expect(fn () => app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('never resets an engagement when a new staff request races its closure', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    AuditAssignmentFixture::respond($fixture['partners'][0]['user'], $assignment, 'decline', 'Unavailable.');
    $assignment->refresh();
    $prior = $assignment->state;
    expect(runIdentityContenders([
        function () use ($fixture, $assignment): void {
            expect(app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 2, 'close', 'Close engagement.', (string) Str::uuid())['code'])->toBe('ASSIGNMENT_CLOSED');
        },
        function () use ($fixture, $assignment): void {
            $result = app(RequestAuditAssignment::class)->handle($fixture['staff']->id, $fixture['business'], 'flash', 'Request again.', (string) Str::uuid());
            expect($result['code'])->toBeIn(['AUDIT_ASSIGNMENT_RESUMED', 'AUDIT_ENGAGEMENT_CLOSED'])->and($result['data']['assignment_id'])->toBe($assignment->id);
        },
    ]))->toBe([0, 0]);
    expect(AuditAssignment::query()->where('business_id', $fixture['business'])->count())->toBe(1)
        ->and($assignment->refresh()->status)->toBe('closed')->and($assignment->revision)->toBe(3)
        ->and($assignment->state['tried'])->toBe($prior['tried'])->and($assignment->state['attempt'])->toBe($prior['attempt'])
        ->and($assignment->state['original_dispatch_at'])->toBe($prior['original_dispatch_at'])->and($assignment->state['complete_by'])->toBe($prior['complete_by']);
});

it('takes no staff row lock during preflight and rechecks a revocation before protected case access', function (): void {
    $fixture = AuditAssignmentFixture::make(0);
    $assignment = AuditAssignmentFixture::request($fixture);
    config(['database.connections.audit_preflight_contender' => config('database.connections.pgsql')]);
    DB::connection('audit_preflight_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $event = 'eloquent.retrieved: '.BusinessProfile::class;
    $changed = false;
    Event::listen($event, function () use ($fixture, $default, &$changed): void {
        if ($changed) {
            return;
        }
        $changed = true;
        DB::setDefaultConnection('audit_preflight_contender');
        try {
            app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Withdraw after preflight.', (string) Str::uuid(), ['analyst']);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('audit_preflight_contender');
        }
    });
    try {
        DB::transaction(function () use ($fixture, $assignment): void {
            expect(fn () => app(GetAuditOperationsCase::class)->handle($fixture['staff']->id, $assignment->id))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
        });
    } finally {
        Event::forget($event);
    }
    expect($changed)->toBeTrue()->and($assignment->refresh()->revision)->toBe(1);
});

it('serializes redispatch with a same-Party acceptance on another Business at the capacity limit', function (): void {
    $this->freezeTime();
    $first = AuditAssignmentFixture::make(1);
    $second = AuditAssignmentFixture::make(0);
    $partner = $first['partners'][0];
    $pending = AuditAssignmentFixture::request($second);
    AuditAssignmentFixture::independence($second['staff'], $second['business'], $partner['party']->id);
    $offered = AuditAssignmentFixture::request($first);
    AuditAssignmentFixture::engagement($partner['party']->id);
    AuditAssignmentFixture::engagement($partner['party']->id);
    expect(runIdentityContenders([
        function () use ($second, $pending): void {
            $result = app(ResolveAuditAssignment::class)->handle($second['staff']->id, $pending->id, 1, 'redispatch', 'Recheck capacity.', (string) Str::uuid());
            expect($result['code'])->toBeIn(['ASSIGNMENT_REDISPATCHED', 'ASSIGNMENT_REDISPATCH_PENDING']);
        },
        function () use ($partner, $offered): void {
            expect(AuditAssignmentFixture::respond($partner['user'], $offered)['code'])->toBe('ASSIGNMENT_ACCEPTED');
        },
    ]))->toBe([0, 0]);
    if ($pending->refresh()->status === 'offered') {
        expect(AuditAssignmentFixture::respond($partner['user'], $pending)['code'])->toBe('AUDITOR_CAPACITY_REACHED');
    }
    expect(AuditAssignment::query()->where('party_id', $partner['party']->id)->where('status', 'accepted')->count())->toBe(3)
        ->and($pending->state['original_dispatch_at'])->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'));
});

it('does not redispatch or restart a case while the expiry worker advances the same expired offer', function (): void {
    $this->freezeTime();
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $prior = $assignment->state;
    $this->travel(1)->hours();
    expect(runIdentityContenders([
        function () use ($fixture, $assignment): void {
            $result = app(ResolveAuditAssignment::class)->handle($fixture['staff']->id, $assignment->id, 1, 'redispatch', 'Retry while expiring.', (string) Str::uuid());
            expect($result['code'])->toBeIn(['ASSIGNMENT_NOT_IN_OPERATIONS', 'VERSION_CONFLICT']);
        },
        function (): void {
            expect(app(AdvanceExpiredAuditOffers::class)->handle(100))->toBe(1);
        },
    ]))->toBe([0, 0]);
    expect($assignment->refresh()->status)->toBe('operations')->and($assignment->revision)->toBe(2)
        ->and($assignment->state['tried'])->toBe($prior['tried'])->and($assignment->state['attempt'])->toBe(1)
        ->and($assignment->state['original_dispatch_at'])->toBe($prior['original_dispatch_at'])->and($assignment->state['complete_by'])->toBe($prior['complete_by']);
});

it('records credit facts once under simultaneous identical retries', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $write = function () use ($fixture, $request): void {
        expect(BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, requestId: $request)['code'])->toBe('CREDIT_FACTS_RECORDED');
    };
    expect(runIdentityContenders([$write, $write]))->toBe([0, 0])
        ->and(BusinessCreditSnapshot::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'business.credit.fixture')->count())->toBe(1);
});

it('admits one credit facts revision when independent writers compete', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $write = function () use ($fixture): void {
        $result = BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('CREDIT_FACTS_RECORDED');
    };
    expect(runIdentityContenders([$write, $write]))->toBe([0, 2])
        ->and(BusinessCreditSnapshot::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'business.credit.fixture')->where('result->code', 'VERSION_CONFLICT')->count())->toBe(1);
});

it('holds current credit facts stable through a protected effect and observes withdrawal afterward', function (): void {
    $fixture = BusinessApplicationFixture::make();
    BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id);
    config(['database.connections.credit_contender' => config('database.connections.pgsql')]);
    DB::connection('credit_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $withdraw = fn (): array => app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['authority']['staff']->id, $fixture['business']->id, 1,
        null, 'synthetic:withdrawal', 'Withdraw fixture after protected effect.', (string) Str::uuid());
    app(BusinessCreditFactsStore::class)->withCurrent($fixture['business']->id, function (?array $facts) use ($default, $withdraw): void {
        expect($facts['revision'])->toBe(1);
        DB::setDefaultConnection('credit_contender');
        try {
            expect(fn () => $withdraw())->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('credit_contender');
        }
    });
    expect($withdraw()['revision'])->toBe(2)
        ->and(app(BusinessCreditFactsStore::class)->withCurrent($fixture['business']->id, fn (?array $facts): ?array => $facts))->toBeNull();
});

it('retains staff authority through credit facts publication', function (): void {
    $fixture = BusinessApplicationFixture::make();
    config(['database.connections.credit_staff_contender' => config('database.connections.pgsql')]);
    DB::connection('credit_staff_contender')->statement("SET lock_timeout = '500ms'");
    $default = DB::getDefaultConnection();
    $revoke = fn (): array => app(ConfigureStaffAccess::class)->handle($fixture['authority']['staff']->id, false, 'Withdraw staff access.', (string) Str::uuid());
    $event = 'eloquent.creating: '.BusinessCreditSnapshot::class;
    Event::listen($event, function () use ($default, $revoke): void {
        DB::setDefaultConnection('credit_staff_contender');
        try {
            expect(fn () => $revoke())->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('credit_staff_contender');
        }
    });
    try {
        expect(BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id)['code'])->toBe('CREDIT_FACTS_RECORDED');
    } finally {
        Event::forget($event);
    }
    $revoke();
    expect(fn () => BusinessCreditFactsFixture::record($fixture['authority']['staff'], $fixture['business']->id, 1))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
});

it('publishes one application quote for simultaneous identical evaluations', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $request = (string) Str::uuid();
    $evaluate = function () use ($fixture, $request): void {
        expect(BusinessQuoteFixture::evaluate($fixture, request: $request)['code'])->toBe('APPLICATION_EVALUATED');
    };
    expect(runIdentityContenders([$evaluate, $evaluate]))->toBe([0, 0])
        ->and(BusinessApplicationQuote::query()->count())->toBe(1)
        ->and($fixture['application']->refresh()->revision)->toBe(3)
        ->and(CommandOperation::query()->where('command', 'application.evaluate')->count())->toBe(1);
});

it('serializes competing application quotes at one draft revision', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $evaluate = function () use ($fixture): void {
        $result = BusinessQuoteFixture::evaluate($fixture);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_EVALUATED');
    };
    expect(runIdentityContenders([$evaluate, $evaluate]))->toBe([0, 2])
        ->and(BusinessApplicationQuote::query()->count())->toBe(1)
        ->and($fixture['application']->refresh()->revision)->toBe(3);
});

it('holds credit facts and source Auditor identity through actual application quote publication', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $default = DB::getDefaultConnection();
    $event = 'eloquent.creating: '.BusinessApplicationQuote::class;
    Event::listen($event, function () use ($fixture, $default): void {
        foreach (['credit', 'auditor'] as $source) {
            config(['database.connections.quote_contender' => config('database.connections.pgsql')]);
            DB::connection('quote_contender')->statement("SET lock_timeout = '500ms'");
            DB::setDefaultConnection('quote_contender');
            try {
                expect(fn () => $source === 'credit'
                    ? app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
                        'synthetic:withdrawn', 'Withdraw during quote publication.', (string) Str::uuid())
                    : Party::query()->whereKey($fixture['audit']['partners'][0]['party']->id)->update(['verified_at' => null]))->toThrow(QueryException::class);
            } finally {
                DB::setDefaultConnection($default);
                DB::purge('quote_contender');
            }
        }
    });
    try {
        expect(BusinessQuoteFixture::evaluate($fixture)['data']['quote']['status'])->toBe('ready');
    } finally {
        Event::forget($event);
    }
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
        'synthetic:withdrawn', 'Withdraw after publication.', (string) Str::uuid());
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull();
});

it('records one signature and submission for simultaneous identical acceptance commands', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $submit = function () use ($fixture, $accepted, $request): void {
        expect(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $request)['code'])->toBe('APPLICATION_SUBMITTED');
    };
    expect(runIdentityContenders([$submit, $submit]))->toBe([0, 0])
        ->and(BusinessApplicationSignature::query()->count())->toBe(1)
        ->and(BusinessApplicationSubmission::query()->count())->toBe(1)
        ->and($fixture['application']->refresh()->revision)->toBe(5)
        ->and(CommandOperation::query()->where('command', 'application.submit')->count())->toBe(1);
});

it('serializes creation against final submission without opening another application for the Business', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $create = function () use ($fixture): void {
        $result = app(CreateBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1,
            $fixture['audit']['business'], 0, (string) Str::uuid());
        expect($result['code'])->toBeIn(['APPLICATION_RESUMED', 'APPLICATION_PENDING_REVIEW']);
    };
    $submit = function () use ($fixture, $accepted): void {
        expect(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4)['code'])->toBe('APPLICATION_SUBMITTED');
    };
    expect(runIdentityContenders([$create, $submit]))->toBe([0, 0])
        ->and(BusinessApplication::query()->count())->toBe(1)
        ->and(BusinessApplicationSubmission::query()->count())->toBe(1)
        ->and($fixture['application']->refresh()->status)->toBe('submitted');
    expect(app(CreateBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1,
        $fixture['audit']['business'], 0, (string) Str::uuid())['code'])->toBe('APPLICATION_PENDING_REVIEW');
});

it('serializes draft autosave against final submission and requires fresh intent from the losing command', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $save = function () use ($fixture): void {
        $result = app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1,
            $fixture['audit']['business'], $fixture['application']->id, 4,
            [...BusinessApplicationFixture::fields('12000000'), 'title' => 'Changed during signing'], null, (string) Str::uuid());
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_SAVED');
    };
    $submit = function () use ($fixture, $accepted): void {
        $result = BusinessQuoteFixture::submit($fixture, $accepted, revision: 4);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_SUBMITTED');
    };
    expect(runIdentityContenders([$save, $submit]))->toBe([0, 2]);
    $application = $fixture['application']->refresh();
    $submitted = $application->status === 'submitted';
    expect($application->revision)->toBe(5)
        ->and(BusinessApplicationSubmission::query()->count())->toBe($submitted ? 1 : 0)
        ->and(BusinessApplicationSignature::query()->count())->toBe($submitted ? 1 : 0)
        ->and($application->draft['title'])->toBe($submitted ? BusinessApplicationFixture::fields('12000000')['title'] : 'Changed during signing')
        ->and($application->current_quote_id === null)->toBe(! $submitted);
});

it('serializes requoting against a pending company signature at the same revision', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $evaluate = function () use ($fixture): void {
        $result = BusinessQuoteFixture::evaluate($fixture, 4);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_EVALUATED');
    };
    $sign = function () use ($fixture, $accepted): void {
        $result = BusinessQuoteFixture::submit($fixture, $accepted, revision: 4);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_SIGNATURE_RECORDED');
    };
    expect(runIdentityContenders([$evaluate, $sign]))->toBe([0, 2]);
    $signed = BusinessApplicationSignature::query()->exists();
    expect($fixture['application']->refresh()->revision)->toBe(5)->and($fixture['application']->status)->toBe('draft')
        ->and(BusinessApplicationQuote::query()->count())->toBe($signed ? 1 : 2)
        ->and(array_column(BusinessQuoteFixture::review($fixture)['acceptance']['signers'], 'state'))->toBe($signed ? ['signed', 'pending'] : ['pending', 'pending'])
        ->and(BusinessApplicationSubmission::query()->count())->toBe(0);
});

it('serializes two required company signatures and requires fresh intent after revision conflict', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $sign = fn (int $index): Closure => function () use ($fixture, $accepted, $index): void {
        $result = BusinessQuoteFixture::submit($fixture, $accepted, $index, 4);
        if ($result['code'] === 'VERSION_CONFLICT') {
            throw new CommandRejection('VERSION_CONFLICT');
        }
        expect($result['code'])->toBe('APPLICATION_SIGNATURE_RECORDED');
    };
    expect(runIdentityContenders([$sign(0), $sign(1)]))->toBe([0, 2])
        ->and(BusinessApplicationSignature::query()->count())->toBe(1)
        ->and(BusinessApplicationSubmission::query()->count())->toBe(0)
        ->and($fixture['application']->refresh()->revision)->toBe(5);
    $signed = BusinessApplicationSignature::query()->firstOrFail()->actor_party_id;
    $remaining = $signed === $fixture['audit']['authority']['people'][0]->id ? 1 : 0;
    expect(BusinessQuoteFixture::submit($fixture, $accepted, $remaining, 5)['code'])->toBe('APPLICATION_SUBMITTED')
        ->and(BusinessApplicationSignature::query()->count())->toBe(2)
        ->and(BusinessApplicationSubmission::query()->count())->toBe(1);
});

it('holds every acceptance authority and source through the actual final submission write', function (string $source): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $operator = concurrentIdentityOperator();
    $default = DB::getDefaultConnection();
    $event = 'eloquent.creating: '.BusinessApplicationSubmission::class;
    $change = function () use ($fixture, $source, $operator): void {
        if ($source === 'consent') {
            app(RecordConsentRelease::class)->handle($fixture['audit']['staff']->id, 1, 'withdrawn', [], [], true,
                'fixture:withdrawal', 'Withdraw during final application submission.', (string) Str::uuid());
        } elseif ($source === 'credit') {
            app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
                'synthetic:withdrawal', 'Withdraw during final application submission.', (string) Str::uuid());
        } elseif ($source === 'mandate') {
            $authority = $fixture['audit']['authority'];
            $authority['profile']['name'] = 'Changed business name';
            BusinessAuthorityFixture::configure($authority, 1);
        } elseif ($source === 'signer') {
            app(ChangeMembership::class)->handle($operator->id, $fixture['audit']['authority']['people'][0]->id, 'business', 'revoked', 1,
                'case:withdrawal', 'Withdraw signer membership during final submission.', (string) Str::uuid());
        } else {
            Party::query()->whereKey($fixture['audit']['partners'][0]['party']->id)->update(['verified_at' => null]);
        }
    };
    Event::listen($event, function () use ($default, $change): void {
        config(['database.connections.acceptance_contender' => config('database.connections.pgsql')]);
        DB::connection('acceptance_contender')->statement("SET lock_timeout = '500ms'");
        DB::setDefaultConnection('acceptance_contender');
        try {
            expect($change)->toThrow(QueryException::class);
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('acceptance_contender');
        }
    });
    try {
        expect(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('APPLICATION_SUBMITTED');
    } finally {
        Event::forget($event);
    }
    $change();
    expect(BusinessApplicationSubmission::query()->count())->toBe(1);
})->with(['consent', 'credit', 'mandate', 'signer', 'auditor']);
