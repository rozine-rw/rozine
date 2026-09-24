<?php

declare(strict_types=1);

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\FindBusinessOperation;
use App\Application\Business\GetBusinessApplication;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Business\ApplicationDraft;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationVersion;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;

it('creates one resumable application and history version for the same operation identity', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $user = $fixture['authority']['users'][0];
    $request = (string) Str::uuid();
    $action = app(CreateBusinessApplication::class);
    $first = $action->handle($user->id, 1, $fixture['business']->id, 0, $request);
    expect($action->handle($user->id, 1, $fixture['business']->id, 0, $request))->toBe($first)
        ->and($first['code'])->toBe('APPLICATION_CREATED')
        ->and($first['data']['application']['draft'])->toEqual((new ApplicationDraft)->empty())
        ->and(app(FindBusinessOperation::class)->handle($user->id, 1, 'create', $request))->toBe($first);
    $this->assertDatabaseCount('business_applications', 2);
    $this->assertDatabaseCount('business_application_versions', 2);
    expect($action->handle($user->id, 1, $fixture['business']->id, 2, (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT');
});

it('preserves every draft version and does not restore old input when a save is replayed', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $first = BusinessApplicationFixture::save($fixture, 1, $request);
    expect($first['code'])->toBe('APPLICATION_SAVED');
    BusinessApplicationFixture::save($fixture, 2, target: '10000000');
    expect(BusinessApplicationFixture::save($fixture, 1, $request))->toBe($first)
        ->and(app(FindBusinessOperation::class)->handle($fixture['authority']['users'][0]->id, 1, 'save', $request))->toBe($first);
    $before = BusinessApplicationVersion::query()->orderBy('revision')->firstOrFail()->snapshot;
    $current = app(GetBusinessApplication::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $fixture['application']->id);
    expect($before['draft']['target'])->toBeNull()
        ->and($current['draft']['target'])->toBe('10000000')->and($current['revision'])->toBe(3)
        ->and($current['step'])->toBe('raise');
    $this->assertDatabaseCount('business_application_versions', 3);
    expect(fn () => BusinessApplicationFixture::save($fixture, 1, $request, '9000000'))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('records stale and invalid command denials without changing the saved draft', function (): void {
    $fixture = BusinessApplicationFixture::make();
    BusinessApplicationFixture::save($fixture);
    expect(BusinessApplicationFixture::save($fixture)['code'])->toBe('VERSION_CONFLICT');
    $invalid = BusinessApplicationFixture::save($fixture, 2, target: '8e6');
    expect($invalid['code'])->toBe('APPLICATION_INPUT_INVALID')->and($invalid['http_status'])->toBe(422)
        ->and($invalid['field_errors'])->toHaveKey('target');
    $this->assertDatabaseCount('business_application_versions', 2);
    expect($fixture['application']->refresh()->draft['target'])->toBe('8000000');
});

it('does not allow a draft command to submit or bypass the future quote and signature workflow', function (string $step): void {
    $fixture = BusinessApplicationFixture::make();
    $result = app(SaveBusinessApplication::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $fixture['application']->id,
        1, BusinessApplicationFixture::fields(), $step, (string) Str::uuid());
    expect($result['code'])->toBe('APPLICATION_STEP_INVALID')->and($fixture['application']->refresh()->step)->toBe('business');
})->with(['submitted', 'review', 'unknown']);

it('does not let a view-only person create or change applications', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $authority = $fixture['authority'];
    $authority['terms']['required_signatories'] = [$authority['people'][0]->id];
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][1]->id
        ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority, 1);
    $viewer = $authority['users'][1];
    expect(app(GetBusinessApplication::class)->handle($viewer->id, 1, $fixture['business']->id, $fixture['application']->id)['id'])->toBe($fixture['application']->id)
        ->and(fn () => app(CreateBusinessApplication::class)->handle($viewer->id, 1, $fixture['business']->id, 0, (string) Str::uuid()))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN')
        ->and(fn () => BusinessApplicationFixture::save($fixture, actor: 1))->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
});

it('scopes applications and operations to the current verified business authority', function (): void {
    $one = BusinessApplicationFixture::make();
    $two = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    BusinessApplicationFixture::save($one, 1, $request);
    expect(fn () => app(GetBusinessApplication::class)->handle($two['authority']['users'][0]->id, 1, $one['business']->id, $one['application']->id))
        ->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => app(GetBusinessApplication::class)->handle($one['authority']['users'][0]->id, 1, $one['business']->id, $two['application']->id))
        ->toThrow(CommandRejection::class, 'APPLICATION_NOT_FOUND')
        ->and(fn () => app(FindBusinessOperation::class)->handle($two['authority']['users'][0]->id, 1, 'save', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    $authority = $one['authority'];
    $authority['terms']['status'] = 'revoked';
    BusinessAuthorityFixture::configure($authority, 1);
    expect(fn () => BusinessApplicationFixture::save($one, 1, $request))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED')
        ->and(fn () => app(FindBusinessOperation::class)->handle($authority['users'][0]->id, 1, 'save', $request))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('rechecks active role and current context even for repeated commands', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    BusinessApplicationFixture::save($fixture, 1, $request);
    RoleMembership::query()->where('party_id', $fixture['authority']['people'][0]->id)->update(['status' => 'revoked']);
    expect(fn () => BusinessApplicationFixture::save($fixture, 1, $request))->toThrow(IdentityViolation::class);
});

it('blocks draft editing after submission while retaining the historical record', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $fixture['application']->forceFill(['status' => 'submitted', 'step' => 'submitted'])->save();
    expect(BusinessApplicationFixture::save($fixture)['code'])->toBe('APPLICATION_NOT_EDITABLE');
    $this->assertDatabaseCount('business_application_versions', 1);
});

it('denies unknown operation commands and unlinked accounts', function (): void {
    $user = User::factory()->create();
    $action = app(FindBusinessOperation::class);
    expect(fn () => $action->handle($user->id, 0, 'audit.seal', (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $action->handle($user->id, 0, 'save', (string) Str::uuid()))->toThrow(IdentityViolation::class, 'IDENTITY_NOT_LINKED');
});

it('does not authorize journal records with the wrong target type or a vanished application', function (string $target): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$fixture['authority']['people'][0]->id, 'command' => 'application.save',
        'request_id' => $request, 'target_type' => $target, 'target_id' => strtolower((string) Str::ulid())]);
    expect(fn () => app(FindBusinessOperation::class)->handle($fixture['authority']['users'][0]->id, 1, 'save', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
})->with(['unexpected', 'application']);

it('protects immutable application snapshots from updates and deletes', function (string $operation): void {
    $version = BusinessApplicationVersion::factory()->create();
    expect(fn () => DB::transaction(fn (): mixed => $operation === 'update'
        ? $version->forceFill(['snapshot' => []])->save()
        : $version->delete()))->toThrow(QueryException::class, 'Business application versions are immutable');
    expect(BusinessApplication::query()->count())->toBe(1);
})->with(['update', 'delete']);

it('does not return a previous Party outcome if account identity changes before locked authorization', function (): void {
    $fixture = BusinessApplicationFixture::make('organization', 2);
    $request = (string) Str::uuid();
    BusinessApplicationFixture::save($fixture, 1, $request);
    $user = $fixture['authority']['users'][0];
    $nextParty = $fixture['authority']['people'][1];
    $nextMembership = RoleMembership::query()->where('party_id', $nextParty->id)->firstOrFail();
    $repository = app(IdentityRepository::class);
    $calls = 0;
    $mock = $this->createMock(IdentityRepository::class);
    $mock->method('forUser')->willReturnCallback(function (int $id) use ($repository, $user, $nextParty, $nextMembership, &$calls): array {
        $snapshot = $repository->forUser($id);
        if ($calls++ === 0) {
            $user->forceFill(['party_id' => $nextParty->id, 'active_membership_id' => $nextMembership->id])->save();
        }

        return $snapshot;
    });
    $this->app->instance(IdentityRepository::class, $mock);
    expect(fn () => app(FindBusinessOperation::class)->handle($user->id, 1, 'save', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});
