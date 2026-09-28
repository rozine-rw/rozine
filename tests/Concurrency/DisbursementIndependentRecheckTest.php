<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Models\DisbursementProviderEvent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Support\DisbursementFixture;

beforeEach(fn () => config(['cache.default' => 'database']));

it('retains simultaneous provider key collisions across different payout intents without a database error', function (): void {
    ['intent' => $first] = DisbursementFixture::approved();
    ['intent' => $second] = DisbursementFixture::approved();
    $messages = [
        DisbursementFixture::provider()->callback($first->id, 'succeeded', ['event_id' => 'shared-provider-event']),
        DisbursementFixture::provider()->callback($second->id, 'succeeded', ['event_id' => 'shared-provider-event']),
    ];
    $barrier = tempnam(sys_get_temp_dir(), 'rozine-provider-key-');
    $pids = [];
    foreach ($messages as $index => $message) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Cannot fork independent provider observation.');
        }
        if ($pid === 0) {
            DB::purge();
            app('cache')->purge('database');
            DB::listen(function (QueryExecuted $query) use ($barrier, $index): void {
                if (! str_contains($query->sql, 'from "disbursement_provider_events"')
                    || ! str_contains($query->sql, '"disposition" <>')) {
                    return;
                }
                file_put_contents($barrier.'.ready-'.$index, 'ready');
                $deadline = microtime(true) + 10;
                while (! is_file($barrier.'.ready-'.(1 - $index))) {
                    if (microtime(true) > $deadline) {
                        throw new RuntimeException('Provider event identity barrier timed out.');
                    }
                    usleep(10_000);
                }
            });
            try {
                $event = app(PayoutProvider::class)->verify($message);
                $result = app(DisbursementStore::class)->observe($event, 'callback');
                file_put_contents($barrier.'.result-'.$index, $result['disposition']);
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
        $statuses[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }
    $results = [file_get_contents($barrier.'.result-0'), file_get_contents($barrier.'.result-1')];
    sort($results);
    sort($statuses);
    fwrite(STDERR, 'Provider key contender results: '.json_encode($results)."\n");
    foreach (glob($barrier.'*') ?: [] as $artifact) {
        unlink($artifact);
    }
    expect($statuses)->toBe([0, 0])
        ->and($results)->toBe(['applied', 'key_conflict'])
        ->and(DisbursementProviderEvent::query()->count())->toBe(2);
});
