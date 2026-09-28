<?php

declare(strict_types=1);

use App\Application\Operations\ReadChanges;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Tests\Support\InvestorWalletFixture;

/*
 * With real commits on separate connections: the beacon cursor never advances past a transaction
 * that is still open, so a change that commits late, even under an earlier feed id, is delivered.
 */

afterEach(function (): void {
    foreach (['feed_early', 'feed_late'] as $name) {
        DB::purge($name);
    }
});

function feedWriter(string $name): Connection
{
    config(['database.connections.'.$name => config('database.connections.pgsql')]);
    $connection = DB::connection($name);
    $connection->beginTransaction();

    return $connection;
}

function writeWalletChange(Connection $connection, string $party, string $subject): int
{
    return (int) $connection->scalar("INSERT INTO change_feed (party_id, topic, subject, revision) VALUES (?, 'wallet', ?, 1) RETURNING id", [$party, $subject]);
}

/** @return array{subjects: list<string>, cursor: string, reset: bool} */
function beacon(int $userId, string $after): array
{
    $read = app(ReadChanges::class)->handle($userId, ['wallet'], $after);

    return ['subjects' => array_column($read['changes'], 'subject'), 'cursor' => $read['next_cursor'], 'reset' => $read['reset']];
}

it('waits for an open transaction whose earlier id a later transaction has already passed', function (): void {
    $investor = InvestorWalletFixture::investor();
    $party = $investor['party']->id;
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    $early = feedWriter('feed_early');
    $earlyId = writeWalletChange($early, $party, 'early');
    $late = feedWriter('feed_late');
    $lateId = writeWalletChange($late, $party, 'late');
    $late->commit();

    $whileOpen = beacon($investor['user']->id, $cursor);
    $early->commit();
    $afterCommit = beacon($investor['user']->id, $whileOpen['cursor']);

    expect($earlyId)->toBeLessThan($lateId)
        ->and($whileOpen)->toMatchArray(['subjects' => [], 'reset' => false])
        ->and($afterCommit)->toMatchArray(['subjects' => ['early', 'late'], 'reset' => false])
        ->and(beacon($investor['user']->id, $afterCommit['cursor'])['subjects'])->toBe([]);
});

it('delivers a late commit that an id cursor would have skipped', function (): void {
    $investor = InvestorWalletFixture::investor();
    $party = $investor['party']->id;
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    // The older transaction takes its id first, the way a command's journal insert does, and writes its feed row last.
    $older = feedWriter('feed_early');
    $older->scalar('SELECT pg_current_xact_id()');
    $younger = feedWriter('feed_late');
    $youngerId = writeWalletChange($younger, $party, 'younger');
    $olderId = writeWalletChange($older, $party, 'older');
    $older->commit();

    $first = beacon($investor['user']->id, $cursor);
    $younger->commit();
    $second = beacon($investor['user']->id, $first['cursor']);

    expect($youngerId)->toBeLessThan($olderId)
        ->and($first['subjects'])->toBe(['older'])
        ->and(DB::table('change_feed')->where('id', '>', $olderId)->count())->toBe(0)
        ->and($second['subjects'])->toBe(['younger']);
});

it('moves on past a rolled-back transaction without delivering it', function (): void {
    $investor = InvestorWalletFixture::investor();
    $party = $investor['party']->id;
    $cursor = app(ReadChanges::class)->cursor($investor['user']->id);
    $abandoned = feedWriter('feed_early');
    writeWalletChange($abandoned, $party, 'abandoned');
    $committed = feedWriter('feed_late');
    writeWalletChange($committed, $party, 'committed');
    $committed->commit();

    $blocked = beacon($investor['user']->id, $cursor);
    $abandoned->rollBack();

    expect($blocked['subjects'])->toBe([])
        ->and(beacon($investor['user']->id, $blocked['cursor'])['subjects'])->toBe(['committed']);
});
