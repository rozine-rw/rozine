<?php

declare(strict_types=1);

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\SelectActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Http\Resources\OperationResource;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @return array{User, string, Closure(string, string): void} */
function operationJournalActor(): array
{
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($user->id, 'business', 0, (string) Str::uuid());
    $authorize = function (string $targetType, string $targetId) use ($user, $party): void {
        if ($targetType !== 'fixture' || $targetId !== (string) $user->id) {
            throw new CommandRejection('RECORD_NOT_FOUND', 404);
        }
        app(AuthorizeActiveRole::class)->handle($user->id, 'business', $party->id, 1, fn (): bool => true);
    };

    return [$user, 'party:'.$party->id, $authorize];
}

it('records a command and replays it once across JSON key order and UUID case', function (): void {
    [$user, $actor, $authorize] = operationJournalActor();
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $calls = 0;
    $operation = function () use (&$calls, $user): OperationResult {
        $calls++;
        $user->forceFill(['name' => 'One committed effect'])->save();

        return new OperationResult('APPLICATION_SAVED', ['application' => ['id' => 'fixture', 'target' => '9007199254740993']], 2, ['application.save']);
    };
    $input = ['expected_revision' => 1, 'identity_context_revision' => 1, 'draft' => ['title' => 'Résumé', 'target' => '9007199254740993']];
    $result = $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, $input, $authorize, $operation);
    $reordered = ['draft' => ['target' => '9007199254740993', 'title' => 'Résumé'], 'identity_context_revision' => 1, 'expected_revision' => 1];
    $this->travel(9)->days();
    expect($journal->execute($actor, $user->id, 'application.save', strtoupper($request), 'fixture', (string) $user->id, $reordered, $authorize, $operation))->toBe($result)
        ->and($journal->find($actor, 'application.save', $request, $authorize))->toBe($result)
        ->and($calls)->toBe(1)->and($result['status'])->toBe('completed')->and($result['revision'])->toBe(2)
        ->and($user->refresh()->name)->toBe('One committed effect');
    $record = CommandOperation::query()->firstOrFail();
    expect($record->actor_user_id)->toBe($user->id)->and($record->result)->not->toHaveKey('input');
    $this->assertDatabaseCount('command_operations', 1);
});

it('binds replay to the original permitted body, revision and target', function (): void {
    [$user, $actor, $authorize] = operationJournalActor();
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $operation = fn (): OperationResult => new OperationResult('APPLICATION_SAVED', [], 2);
    $input = ['expected_revision' => 1, 'target' => '3000000'];
    $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, $input, $authorize, $operation);
    foreach ([['expected_revision' => 2, 'target' => '3000000'], ['expected_revision' => 1, 'target' => '3005000']] as $changed) {
        expect(fn () => $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, $changed, $authorize, $operation))
            ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    }
    expect(fn () => $journal->execute($actor, $user->id, 'application.save', $request, 'other', 'other', $input, function (string $type, string $id): void {}, $operation))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $this->assertDatabaseCount('command_operations', 1);
});

it('scopes the operation identity to both authenticated party and command', function (): void {
    [$one, $actorOne, $authorizeOne] = operationJournalActor();
    [$two, $actorTwo, $authorizeTwo] = operationJournalActor();
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $operation = fn (): OperationResult => new OperationResult('APPLICATION_SAVED', [], 1);
    $journal->execute($actorOne, $one->id, 'application.save', $request, 'fixture', (string) $one->id, [], $authorizeOne, $operation);
    expect(fn () => $journal->find($actorTwo, 'application.save', $request, $authorizeTwo))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    $journal->execute($actorTwo, $two->id, 'application.save', $request, 'fixture', (string) $two->id, [], $authorizeTwo, $operation);
    $journal->execute($actorOne, $one->id, 'application.evaluate', $request, 'fixture', (string) $one->id, [], $authorizeOne, $operation);
    $this->assertDatabaseCount('command_operations', 3);
});

it('reauthorizes replay and lookup after role revocation before disclosing any outcome', function (): void {
    [$user, $actor, $authorize] = operationJournalActor();
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $operation = fn (): OperationResult => new OperationResult('APPLICATION_SAVED', ['private' => 'business facts'], 1);
    $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, [], $authorize, $operation);
    RoleMembership::query()->where('party_id', $user->party_id)->update(['status' => 'revoked', 'revision' => 2]);
    expect(fn () => $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, [], $authorize, $operation))->toThrow(IdentityViolation::class)
        ->and(fn () => $journal->find($actor, 'application.save', $request, $authorize))->toThrow(IdentityViolation::class);
    $this->assertDatabaseCount('command_operations', 1);
});

it('rolls back rejected effects while retaining an immutable correlated denial', function (): void {
    [$user, $actor, $authorize] = operationJournalActor();
    $original = $user->name;
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $calls = 0;
    $operation = function () use ($user, &$calls): never {
        $calls++;
        $user->forceFill(['name' => 'Must roll back'])->save();
        throw new CommandRejection('VERSION_CONFLICT', 409, 7, ['expected_revision' => ['Refresh the application.']]);
    };
    $result = $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, ['expected_revision' => 1], $authorize, $operation);
    expect($user->refresh()->name)->toBe($original)->and($result['status'])->toBe('rejected')->and($result['code'])->toBe('VERSION_CONFLICT')
        ->and($result['revision'])->toBe(7)->and($result['http_status'])->toBe(409);
    expect($journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, ['expected_revision' => 1], $authorize, $operation))->toBe($result)
        ->and($calls)->toBe(1);
    $response = (new OperationResource($result))->response();
    $body = $response->getData(true);
    expect($response->getStatusCode())->toBe(409)->and($body['code'])->toBe('VERSION_CONFLICT')
        ->and($body['field_errors']['expected_revision'])->toBe(['Refresh the application.'])
        ->and($body['errors'])->toBe($body['field_errors'])
        ->and($body)->not->toHaveKey('http_status')->not->toHaveKey('request_hash')->not->toHaveKey('actor_key');
});

it('returns a fresh response clock without changing the recorded outcome or validation errors', function (): void {
    $this->freezeTime();
    [$user, $actor, $authorize] = operationJournalActor();
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    $recorded = now()->toIso8601String();
    $operation = fn (): never => throw new CommandRejection('INVALID_ACCEPTED_PRINCIPAL', 422, 3, ['accepted_principal' => ['Choose an amount on the permitted grid.']]);
    $outcome = $journal->execute($actor, $user->id, 'application.evaluate', $request, 'fixture', (string) $user->id, [], $authorize, $operation);
    $this->travel(2)->hours();
    $replay = $journal->execute($actor, $user->id, 'application.evaluate', $request, 'fixture', (string) $user->id, [], $authorize, $operation);
    $response = (new OperationResource($replay))->response();
    $body = $response->getData(true);
    expect($replay)->toBe($outcome)
        ->and($response->getStatusCode())->toBe(422)
        ->and($body['server_time'])->toBe(now()->toIso8601String())->not->toBe($recorded)
        ->and($body['recorded_at'])->toBe($recorded)
        ->and($body['errors']['accepted_principal'])->toBe(['Choose an amount on the permitted grid.'])
        ->and($body['field_errors'])->toBe($body['errors']);
});

it('never records an unknown result after an unexpected failure and permits the original safe retry', function (): void {
    [$user, $actor, $authorize] = operationJournalActor();
    $original = $user->name;
    $journal = app(OperationJournal::class);
    $request = (string) Str::uuid();
    expect(fn () => $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, [], $authorize, function () use ($user): never {
        $user->forceFill(['name' => 'Uncommitted'])->save();
        throw new RuntimeException('Provider unavailable before committing.');
    }))->toThrow(RuntimeException::class);
    expect($user->refresh()->name)->toBe($original);
    expect(fn () => $journal->find($actor, 'application.save', $request, $authorize))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    $result = $journal->execute($actor, $user->id, 'application.save', $request, 'fixture', (string) $user->id, [], $authorize, fn (): OperationResult => new OperationResult('APPLICATION_SAVED', [], 1));
    expect($result['status'])->toBe('completed');
    $this->assertDatabaseCount('command_operations', 1);
});

it('rejects mutation or deletion of committed outcomes at the database boundary', function (string $mutation): void {
    $record = CommandOperation::factory()->create();
    expect(fn () => DB::transaction(function () use ($record, $mutation): void {
        $query = DB::table('command_operations')->where('id', $record->id);
        if ($mutation === 'update') {
            $query->update(['result' => '{}']);
        } else {
            $query->delete();
        }
    }))->toThrow(QueryException::class);
    expect($record->refresh()->result['code'])->toBe('FIXTURE_SAVED');
})->with(['update', 'delete']);

it('canonicalizes exact JSON values and UTF-16 property ordering without coercing money', function (): void {
    $json = app(CanonicalJson::class);
    expect($json->encode(['z' => null, 'amount' => '9007199254740993', 'list' => [true, false, 0, -1, 9007199254740991], "\u{20ac}" => "\n\t\"\\", 'a' => []]))
        ->toBe('{"a":[],"amount":"9007199254740993","list":[true,false,0,-1,9007199254740991],"z":null,"€":"\\n\\t\\"\\\\"}')
        ->and($json->encode([]))->toBe('{}')
        ->and($json->encode(["\u{E000}" => 2, "\u{1F600}" => 1]))->toBe('{"😀":1,"":2}');
});

it('refuses values outside the exact canonical profile instead of producing a misleading digest', function (mixed $invalid): void {
    expect(fn () => app(CanonicalJson::class)->encode(['value' => $invalid]))->toThrow(CommandRejection::class, 'CANONICAL_VALUE_INVALID');
})->with([
    'fraction' => 0.1, 'large integer' => 9007199254740992, 'large negative' => -9007199254740992,
    'invalid utf8' => "\xB1", 'object' => fn (): stdClass => new stdClass,
    'line separator' => "\u{2028}", 'paragraph separator' => "\u{2029}",
]);

it('validates operation lookup identity without querying arbitrary scopes', function (string $actor, string $command, string $request): void {
    expect(fn () => app(OperationJournal::class)->find($actor, $command, $request, function (string $type, string $id): void {}))
        ->toThrow(CommandRejection::class, 'OPERATION_INPUT_INVALID');
})->with([
    ['arbitrary', 'application.save', 'f29d3498-2d39-4206-ac9a-61911c3f3c21'],
    ['staff:'.str_repeat('1', 90), 'application.save', 'f29d3498-2d39-4206-ac9a-61911c3f3c21'],
    ['staff:1', 'not a command', 'f29d3498-2d39-4206-ac9a-61911c3f3c21'],
    ['staff:1', 'a.'.str_repeat('b', 100), 'f29d3498-2d39-4206-ac9a-61911c3f3c21'],
    ['staff:1', 'application.save', 'not-a-uuid'],
]);

it('validates attributable actor and target metadata before recording anything', function (int $userId, string $type, string $id): void {
    expect(fn () => app(OperationJournal::class)->execute('staff:1', $userId, 'application.save', (string) Str::uuid(), $type, $id, [],
        function (string $type, string $id): void {}, fn (): OperationResult => new OperationResult('OK', [], 1)))
        ->toThrow(CommandRejection::class, 'OPERATION_INPUT_INVALID');
    $this->assertDatabaseCount('command_operations', 0);
})->with([[0, 'fixture', 'one'], [1, '', 'one'], [1, str_repeat('a', 81), 'one'], [1, 'fixture', ''], [1, 'fixture', str_repeat('a', 256)]]);
