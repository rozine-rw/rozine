<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\ManageDisbursements;
use App\Models\DisbursementProviderEvent;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Cross-intent provider event identity races (#96 5876182472, 5876362074), from Hussain's
 * reproducer. Each contender pauses at the identity lookup until the other arrives or a short
 * grace passes: without the identity lock both pass the lookup together and the second insert
 * fails; with it the second waits, then classifies against the committed first.
 */

beforeEach(fn () => config(['cache.default' => 'database']));

/**
 * Runs two observation contenders in their own processes and connections.
 *
 * @param  list<Closure(): string>  $contenders  each returns its disposition
 * @return array{statuses: list<int>, results: list<string>}
 */
function identityContenders(array $contenders): array
{
    $barrier = tempnam(sys_get_temp_dir(), 'rozine-provider-key-');
    $pids = [];
    foreach ($contenders as $index => $contender) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Cannot fork independent provider observation.');
        }
        if ($pid === 0) {
            DB::purge();
            app('cache')->purge('database');
            DB::listen(function (QueryExecuted $query) use ($barrier, $index): void {
                if (! str_contains($query->sql, 'from "disbursement_provider_events"') || ! str_contains($query->sql, '"disposition" <>')) {
                    return;
                }
                file_put_contents($barrier.'.ready-'.$index, 'ready');
                $deadline = microtime(true) + 1.5;
                while (! is_file($barrier.'.ready-'.(1 - $index)) && microtime(true) < $deadline) {
                    usleep(10_000);
                }
            });
            try {
                file_put_contents($barrier.'.result-'.$index, $contender());
                exit(0);
            } catch (Throwable $exception) {
                file_put_contents($barrier.'.result-'.$index, $exception::class.': '.$exception->getCode().' '.substr($exception->getMessage(), 0, 180));
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exit = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        $statuses[] = $exit === false ? -1 : $exit;
    }
    $results = [(string) file_get_contents($barrier.'.result-0'), (string) file_get_contents($barrier.'.result-1')];
    foreach (glob($barrier.'*') ?: [] as $artifact) {
        unlink($artifact);
    }
    sort($results);
    sort($statuses);

    return ['statuses' => $statuses, 'results' => $results];
}

/** @param array<string, string> $message */
function observeCallback(array $message): string
{
    return app(DisbursementStore::class)->observe(app(PayoutProvider::class)->verify($message), 'callback')['disposition'];
}

it('retains simultaneous provider key collisions across different payout intents without a database error', function (): void {
    ['intent' => $first] = DisbursementFixture::approved();
    ['intent' => $second] = DisbursementFixture::approved();
    $one = DisbursementFixture::provider()->callback($first->id, 'succeeded', ['event_id' => 'shared-provider-event']);
    $two = DisbursementFixture::provider()->callback($second->id, 'succeeded', ['event_id' => 'shared-provider-event']);

    expect(identityContenders([fn () => observeCallback($one), fn () => observeCallback($two)]))
        ->toBe(['statuses' => [0, 0], 'results' => ['applied', 'key_conflict']])
        ->and(DisbursementProviderEvent::query()->where('provider_event_id', 'shared-provider-event')->count())->toBe(2);
});

it('treats the same event content observed for two different intents at once as one observation and a duplicate', function (): void {
    ['intent' => $first] = DisbursementFixture::approved();
    ['intent' => $second] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($first->id, 'succeeded', ['event_id' => 'replayed-provider-event']);
    $event = app(PayoutProvider::class)->verify($message);

    // One side is the callback, the other the same authenticated content answering a query about another intent.
    $outcome = identityContenders([
        fn () => observeCallback($message),
        fn () => app(DisbursementStore::class)->observe($event, 'query', $second->id)['disposition'],
    ]);
    // Whichever records it first keeps the one row (applied for its own intent, or unverifiable for
    // the other intent it does not match); the second is an exact replay and adds nothing.
    expect($outcome['statuses'])->toBe([0, 0])
        ->and($outcome['results'])->toBeIn([['applied', 'duplicate'], ['duplicate', 'unverifiable']])
        ->and(DisbursementProviderEvent::query()->where('provider_event_id', 'replayed-provider-event')->count())->toBe(1);
});

it('retains a key collision between a callback and a staff requery for different intents at once', function (): void {
    ['intent' => $first] = DisbursementFixture::approved();
    ['disbursement' => $disbursement, 'intent' => $second] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    $callback = DisbursementFixture::provider()->callback($first->id, 'succeeded', ['event_id' => 'requeried-provider-event']);
    DisbursementFixture::provider()->scriptQuery($second->id, 'succeeded', ['event_id' => 'requeried-provider-event']);

    $outcome = identityContenders([
        fn () => observeCallback($callback),
        function () use ($treasury, $disbursement): string {
            $result = app(ManageDisbursements::class)->command(User::query()->findOrFail($treasury->id)->id, $disbursement->id, 'requery', 3,
                'Asked the provider again.', (string) Str::uuid());

            return (string) ($result['data']['observation']['disposition'] ?? $result['code']);
        },
    ]);
    expect($outcome['statuses'])->toBe([0, 0])
        ->and($outcome['results'])->toBe(['applied', 'key_conflict'])
        ->and(DisbursementProviderEvent::query()->where('provider_event_id', 'requeried-provider-event')->count())->toBe(2);
});
