<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Infrastructure\Disbursement\EloquentDisbursementStore;
use App\Models\DisbursementClosing;
use App\Models\DisbursementProviderEvent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

it('settles the matching intent even when another intent observed its exact event first', function (bool $wrongQueryFirst): void {
    ['intent' => $matching] = DisbursementFixture::approved();
    ['intent' => $other] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($matching->id, 'succeeded', ['event_id' => 'independent-cross-intent-event']);
    $store = app(DisbursementStore::class);
    if ($wrongQueryFirst) {
        $wrong = $store->observe(app(PayoutProvider::class)->verify($message), 'query', $other->id);
        expect($wrong['disposition'])->toBe('unverifiable')
            ->and($store->reconcile($other->id))->toBe('exception');
    }

    $callback = app(RecordPayoutEvent::class)->handle($message);
    expect([
        'callback' => $callback,
        'matching_observations' => DisbursementProviderEvent::query()->where('intent_id', $matching->id)->count(),
        'matching_closing' => DisbursementClosing::query()->where('intent_id', $matching->id)->value('kind'),
        'other_closings' => DisbursementClosing::query()->where('intent_id', $other->id)->count(),
    ])->toBe([
        'callback' => ['disposition' => 'applied', 'decision' => 'matched_success'],
        'matching_observations' => 1,
        'matching_closing' => 'issued',
        'other_closings' => 0,
    ]);
})->with(['normal callback control' => false, 'wrong intent query precedes callback' => true]);

/*
 * Cross-intent exact replays (#96 P2b): same-content evidence observed against another intent is
 * retained as refused (unverifiable) evidence for that intent and claims nothing, so it never
 * suppresses the matching intent's authenticated outcome, in either arrival order.
 */

beforeEach(fn () => config(['cache.default' => 'database']));

it('keeps the wrong-intent observation refused when the matching callback arrives first', function (): void {
    ['intent' => $matching] = DisbursementFixture::approved();
    ['intent' => $other] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($matching->id, 'succeeded', ['event_id' => 'matching-first-event']);
    $store = app(DisbursementStore::class);

    expect(app(RecordPayoutEvent::class)->handle($message))->toBe(['disposition' => 'applied', 'decision' => 'matched_success'])
        ->and($store->observe(app(PayoutProvider::class)->verify($message), 'query', $other->id)['disposition'])->toBe('unverifiable')
        ->and($store->reconcile($other->id))->toBe('exception')
        ->and(DisbursementClosing::query()->where('intent_id', $matching->id)->value('kind'))->toBe('issued')
        ->and(DisbursementClosing::query()->where('intent_id', $other->id)->count())->toBe(0)
        ->and(DisbursementProviderEvent::query()->where('provider_event_id', 'matching-first-event')->orderBy('id')->pluck('disposition')->all())
        ->toBe(['applied', 'unverifiable']);
});

it('keeps an exact same-intent replay idempotent, a recorded conflict included', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    $pending = DisbursementFixture::provider()->callback($intent->id, 'unknown', ['event_id' => 'replayed-same-intent']);
    $conflicting = DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['event_id' => 'replayed-same-intent']);
    $wrong = DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['event_id' => 'replayed-unverifiable', 'amount' => '1']);
    $record = fn (array $message): string => app(RecordPayoutEvent::class)->handle($message)['disposition'];

    expect([$record($pending), $record($pending), $record($conflicting), $record($conflicting), $record($wrong), $record($wrong)])
        ->toBe(['applied', 'duplicate', 'key_conflict', 'duplicate', 'unverifiable', 'duplicate'])
        ->and(DisbursementProviderEvent::query()->where('intent_id', $intent->id)->count())->toBe(3);
});

/**
 * Two contenders in their own processes. The first holds the provider event identity lock for a
 * moment; the second starts only once pg_locks shows that lock granted, so it must wait on it.
 *
 * @param  list<Closure(): mixed>  $contenders  first holds the lock, second contends
 * @return list<int>
 */
function lockedIdentityContenders(string $provider, string $eventId, array $contenders): array
{
    $key = EloquentDisbursementStore::providerEventLockKey($provider, $eventId);
    $pids = [];
    foreach ($contenders as $index => $contender) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Cannot fork a cross-intent contender.');
        }
        if ($pid === 0) {
            DB::purge();
            app('cache')->purge('database');
            try {
                if ($index === 0) {
                    DB::listen(function (QueryExecuted $query): void {
                        if (str_contains($query->sql, 'from "disbursement_provider_events"') && str_contains($query->sql, '"disposition" not in')) {
                            usleep(700_000);
                        }
                    });
                } else {
                    $deadline = microtime(true) + 10;
                    while (DB::selectOne("SELECT count(*) AS held FROM pg_locks WHERE locktype = 'advisory' AND granted
                        AND classid::bigint = ((hashtextextended(?, 0) >> 32) & 4294967295) AND objid::bigint = (hashtextextended(?, 0) & 4294967295)",
                        [$key, $key])->held < 1) {
                        if (microtime(true) > $deadline) {
                            throw new RuntimeException('The identity lock was never observed.');
                        }
                        usleep(5_000);
                    }
                }
                $contender();
                exit(0);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
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

    return $statuses;
}

it('settles only the matching intent when its callback and another intent\'s query or requery race for one event', function (string $path, bool $matchingHoldsLock): void {
    ['intent' => $matching] = DisbursementFixture::approved();
    ['disbursement' => $otherDisbursement, 'intent' => $other] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    $eventId = 'raced-cross-intent-'.$path.'-'.($matchingHoldsLock ? 'matching' : 'other');
    $message = DisbursementFixture::provider()->callback($matching->id, 'succeeded', ['event_id' => $eventId]);
    $event = app(PayoutProvider::class)->verify($message);
    $callback = fn () => app(RecordPayoutEvent::class)->handle($message);
    $wrong = match ($path) {
        'query' => function () use ($event, $other): void {
            app(DisbursementStore::class)->observe($event, 'query', $other->id);
            app(DisbursementStore::class)->reconcile($other->id);
        },
        default => function () use ($message, $other, $otherDisbursement, $treasury): void {
            // The provider answers the other intent's requery with the matching intent's exact event.
            $fields = collect($message)->except('signature')->all();
            DisbursementFixture::provider()->scriptQuery($other->id, 'succeeded', $fields);
            app(ManageDisbursements::class)->command($treasury->id, $otherDisbursement->id, 'requery', 3,
                'Asked the provider again.', (string) Str::uuid());
        },
    };

    expect(lockedIdentityContenders('synthetic', $eventId, $matchingHoldsLock ? [$callback, $wrong] : [$wrong, $callback]))->toBe([0, 0]);
    app(DisbursementStore::class)->reconcile($matching->id);
    expect(DisbursementClosing::query()->where('intent_id', $matching->id)->value('kind'))->toBe('issued')
        ->and(DisbursementClosing::query()->where('intent_id', $other->id)->count())->toBe(0)
        ->and(DisbursementProviderEvent::query()->where('intent_id', $matching->id)->where('provider_event_id', $eventId)->value('disposition'))->toBe('applied')
        ->and(DisbursementProviderEvent::query()->where('intent_id', $other->id)->where('provider_event_id', $eventId)->value('disposition'))->toBe('unverifiable');
})->with(['query' => 'query', 'requery' => 'requery'])->with(['matching holds the lock' => true, 'wrong intent holds the lock' => false]);
