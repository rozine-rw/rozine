<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\ChangeFeed;
use App\Domain\Operations\ChangeCursor;
use App\Domain\Operations\ChangeScope;
use App\Models\BusinessProfile;
use App\Models\Party;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
 * The append-only change feed (S4-E): the database stamps each row with its writer's transaction,
 * keeps exactly one audience per row, and refuses every change but the guarded prune of an old row.
 */

function feedParty(): string
{
    return Party::factory()->verified()->create()->id;
}

/** @return array<string, mixed> */
function recordChange(ChangeScope $scope, string $topic, string $subject, ?int $revision = null): array
{
    return DB::transaction(function () use ($scope, $topic, $subject, $revision): array {
        app(ChangeFeed::class)->record($scope, $topic, $subject, $revision);

        return (array) DB::selectOne('SELECT *, created_xid = pg_current_xact_id() AS own FROM change_feed ORDER BY id DESC LIMIT 1');
    });
}

it('stamps each change with its own transaction and time, whatever the writer supplies', function (): void {
    $party = feedParty();
    $row = recordChange(ChangeScope::party($party), 'wallet', 'w1');

    expect($row['own'])->toBeTrue()->and($row['party_id'])->toBe($party)->and($row['revision'])->toBe(1)
        ->and($row['business_id'])->toBeNull()->and($row['staff_queue'])->toBeNull();

    DB::insert("INSERT INTO change_feed (created_xid, party_id, topic, subject, revision, created_at) VALUES ('3', ?, 'wallet', 'w1', 9, '2000-01-01')", [$party]);
    $forged = DB::selectOne('SELECT created_xid = pg_current_xact_id() AS own, created_at > now() - interval \'1 minute\' AS fresh FROM change_feed WHERE revision = 9');

    expect($forged->own)->toBeTrue()->and($forged->fresh)->toBeTrue();
});

it('gives a subject without a revision of its own the next one', function (): void {
    $scope = ChangeScope::staffQueue('applications');
    recordChange($scope, 'staff_queue', 'applications');
    recordChange($scope, 'staff_queue', 'applications', 5);

    expect(recordChange($scope, 'staff_queue', 'applications')['revision'])->toBe(6);
});

it('keeps each change to exactly one audience that matches its topic', function (string $sql): void {
    $party = feedParty();
    $business = BusinessProfile::factory()->create()->id;

    expect(fn () => DB::transaction(fn () => DB::insert($sql, ['party' => $party, 'business' => $business])))->toThrow(QueryException::class);
})->with([
    'no audience' => ["INSERT INTO change_feed (topic, subject, revision) VALUES ('wallet', 'w1', 1)"],
    'two audiences' => ["INSERT INTO change_feed (party_id, business_id, topic, subject, revision) VALUES (:party, :business, 'wallet', 'w1', 1)"],
    'a wallet for a business' => ["INSERT INTO change_feed (business_id, topic, subject, revision) VALUES (:business, 'wallet', 'w1', 1)"],
    'a campaign for a party' => ["INSERT INTO change_feed (party_id, topic, subject, revision) VALUES (:party, 'campaign', 'c1', 1)"],
    'an unknown queue' => ["INSERT INTO change_feed (staff_queue, topic, subject, revision) VALUES ('payouts', 'staff_queue', 'payouts', 1)"],
    'a queue under another subject' => ["INSERT INTO change_feed (staff_queue, topic, subject, revision) VALUES ('applications', 'staff_queue', 'other', 1)"],
    'a subject outside its shape' => ["INSERT INTO change_feed (party_id, topic, subject, revision) VALUES (:party, 'wallet', 'w-1', 1)"],
    'a zero revision' => ["INSERT INTO change_feed (party_id, topic, subject, revision) VALUES (:party, 'wallet', 'w1', 0)"],
]);

it('refuses to change or delete a recorded change', function (Closure $change): void {
    /** @var Closure(): bool $change */
    recordChange(ChangeScope::party(feedParty()), 'wallet', 'w1');

    expect(fn () => DB::transaction($change))->toThrow(QueryException::class, 'Change feed rows are append-only');
})->with([
    'update' => [fn () => DB::unprepared('UPDATE change_feed SET revision = 2')],
    'delete' => [fn () => DB::unprepared('DELETE FROM change_feed')],
    'delete a young row even when pruning' => [fn () => DB::unprepared("SET LOCAL rozine.change_feed_prune = 'on'; DELETE FROM change_feed")],
]);

it('refuses a change outside a transaction or outside its audience', function (Closure $record, string $message): void {
    expect($record)->toThrow($message);
})->with([
    'no transaction' => [fn () => app(ChangeFeed::class)->record(ChangeScope::staffQueue('applications'), 'staff_queue', 'applications'), 'CHANGE_FEED_TRANSACTION_REQUIRED'],
    'another topic' => [fn () => DB::transaction(fn () => app(ChangeFeed::class)->record(ChangeScope::staffQueue('applications'), 'wallet', 'applications')), 'CHANGE_INVALID'],
    'a bad subject' => [fn () => DB::transaction(fn () => app(ChangeFeed::class)->record(ChangeScope::staffQueue('applications'), 'staff_queue', 'a b')), 'CHANGE_INVALID'],
    'a zero revision' => [fn () => DB::transaction(fn () => app(ChangeFeed::class)->record(ChangeScope::staffQueue('applications'), 'staff_queue', 'applications', 0)), 'CHANGE_INVALID'],
    'an unknown queue' => [fn () => ChangeScope::staffQueue('payouts'), 'CHANGE_SCOPE_INVALID'],
    'a malformed party' => [fn () => ChangeScope::party('nope'), 'CHANGE_SCOPE_INVALID'],
    'a malformed business' => [fn () => ChangeScope::business('nope'), 'CHANGE_SCOPE_INVALID'],
]);

it('prunes only changes past the retention and keeps each subject\'s latest', function (): void {
    $party = feedParty();
    foreach ([1, 2, 3] as $revision) {
        recordChange(ChangeScope::party($party), 'wallet', 'w1', $revision);
    }
    recordChange(ChangeScope::party($party), 'wallet', 'w2', 1);
    DB::statement('ALTER TABLE change_feed DISABLE TRIGGER change_feed_protected');
    DB::update("UPDATE change_feed SET created_at = now() - interval '72 hours' WHERE revision < 3");
    DB::statement('ALTER TABLE change_feed ENABLE TRIGGER change_feed_protected');

    expect(fn () => app(ChangeFeed::class)->prune(23))->toThrow(InvalidArgumentException::class, 'CHANGE_RETENTION_TOO_SHORT')
        ->and(app(ChangeFeed::class)->prune(48))->toBe(2)
        ->and(DB::table('change_feed')->orderBy('id')->get(['subject', 'revision'])->map(fn (object $row): string => $row->subject.'@'.$row->revision)->all())
        ->toBe(['w1@3', 'w2@1'])
        ->and(app(ChangeFeed::class)->prune(24))->toBe(0);
});

it('refuses to roll the feed back once it holds a change', function (): void {
    recordChange(ChangeScope::party(feedParty()), 'wallet', 'w1');

    expect(fn () => (require database_path('migrations/2026_09_28_180000_create_change_feed_table.php'))->down())
        ->toThrow(QueryException::class, 'Recorded changes require a forward migration');
});

it('parses only the cursors it issues', function (string $cursor, bool $valid): void {
    $parsed = ChangeCursor::parse($cursor);

    expect($parsed !== null)->toBe($valid)
        ->and($parsed?->encode())->toBe($valid ? $cursor : null);
})->with([
    'issued' => ['1.745.12.1790000000.3', true],
    'largest xid' => ['1.9223372036854775807.0.1790000000.0', true],
    'another version' => ['2.745.12.1790000000.3', false],
    'past the largest xid' => ['1.9223372036854775808.0.1790000000.0', false],
    'past the largest id' => ['1.745.9223372036854775808.1790000000.0', false],
    'a zero horizon' => ['1.0.0.1790000000.0', false],
    'leading zeros' => ['1.0745.0.1790000000.0', false],
    'too few parts' => ['1.745.0.1790000000', false],
    'not a number' => ['1.abc.0.1790000000.0', false],
]);

it('expires a cursor past its lifetime or from the future', function (): void {
    $cursor = new ChangeCursor(745, 0, 1_000_000, 1);

    expect($cursor->expired(1_000_000 + 86_400, 86_400))->toBeFalse()
        ->and($cursor->expired(1_000_000 + 86_401, 86_400))->toBeTrue()
        ->and($cursor->expired(1_000_000 - 301, 86_400))->toBeTrue()
        ->and($cursor->expired(1_000_000 - 300, 86_400))->toBeFalse();
});

it('prunes only through the guarded command, never below the cursor lifetime', function (string $hours, int $status, string $output): void {
    $party = feedParty();
    recordChange(ChangeScope::party($party), 'wallet', 'w1', 1);
    recordChange(ChangeScope::party($party), 'wallet', 'w1', 2);
    DB::statement('ALTER TABLE change_feed DISABLE TRIGGER change_feed_protected');
    DB::update("UPDATE change_feed SET created_at = now() - interval '72 hours'");
    DB::statement('ALTER TABLE change_feed ENABLE TRIGGER change_feed_protected');

    expect(Artisan::call('changes:prune', ['--hours' => $hours]))->toBe($status)
        ->and(Artisan::output())->toContain($output);
})->with([
    'default retention' => ['48', 0, 'Pruned 1 changes.'],
    'the minimum' => ['24', 0, 'Pruned 1 changes.'],
    'below the cursor lifetime' => ['23', 2, 'from 24 to 8760 hours'],
    'not a number' => ['soon', 2, 'from 24 to 8760 hours'],
]);
