<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Identity\ConfigureStaffAccess;
use App\Models\CommandOperation;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementReconciliation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Real-commit disbursement races (#96 5871859618 item 8, 5872328809). The synthetic sources keep
 * their facts in the database cache here, so every forked contender sees the same fixtures.
 */

beforeEach(fn () => config(['cache.default' => 'database']));

/**
 * @param  list<Closure(): mixed>  $operations
 * @return list<int> sorted exit codes: 0 finished, 1 threw
 */
function disbursementContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a disbursement contender.');
        }
        if ($pid === 0) {
            DB::purge();
            // The database cache store holds the parent's connection; each contender needs its own.
            app('cache')->purge('database');
            try {
                $operation();
                exit(0);
            } catch (Throwable $exception) {
                if (getenv('DISBURSEMENT_RACE_DEBUG') === '1') {
                    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n".$exception->getTraceAsString()."\n");
                }
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exit = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        $statuses[] = $exit === false ? throw new RuntimeException('Disbursement contender did not exit normally.') : $exit;
    }
    sort($statuses);

    return $statuses;
}

/** @return list<string> */
function recordedCodes(string $command): array
{
    $codes = array_map(fn (CommandOperation $operation): string => (string) $operation->result['code'], CommandOperation::query()->where('command', $command)->get()->all());
    sort($codes);

    return $codes;
}

it('dispatches only after the outermost commit, and never for a rolled-back approval', function (): void {
    $provider = new class implements PayoutProvider
    {
        /** @var list<int> */
        public array $levels = [];

        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return false;
        }

        public function send(PayoutInstruction $instruction): bool
        {
            $this->levels[] = DB::transactionLevel();

            return true;
        }

        public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent
        {
            return null;
        }

        public function verify(array $message): VerifiedPayoutEvent
        {
            throw new LogicException('Not used in this dispatch test.');
        }
    };
    app()->instance(PayoutProvider::class, $provider);
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];

    expect(fn () => DB::transaction(function () use ($checker, $disbursement, $proof): void {
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        throw new RuntimeException('Roll back the caller transaction.');
    }))->toThrow(RuntimeException::class, 'Roll back the caller transaction.');
    expect(DisbursementIntent::query()->count())->toBe(0)->and($provider->levels)->toBe([]);

    DB::transaction(fn () => DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof));
    expect(DisbursementIntent::query()->count())->toBe(1)->and($provider->levels)->toBe([0])
        ->and(DisbursementDispatch::query()->pluck('phase')->all())->toBe(['queued', 'claimed', 'sent']);
});

it('records one intent when two checkers approve at once', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $first = DisbursementFixture::staff(['approver']);
    $second = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proofs = [$first->id => DisbursementFixture::stepUp($first, $disbursement)['proof'], $second->id => DisbursementFixture::stepUp($second, $disbursement)['proof']];
    $approve = fn ($checker): Closure => fn () => DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proofs[$checker->id]);

    expect(disbursementContenders([$approve($first), $approve($second)]))->toBe([0, 0])
        ->and(DisbursementIntent::query()->count())->toBe(1)
        ->and(recordedCodes('disbursement.approve'))->toBe(['DISBURSEMENT_INTENT_RECORDED', 'DISBURSEMENT_IN_FLIGHT'])
        ->and(DisbursementProviderCall::query()->where('kind', 'send')->count())->toBe(1);
});

it('lets approve and hold race to exactly one effect', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    $compliance = DisbursementFixture::staff(['compliance']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];

    expect(disbursementContenders([
        fn () => DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof),
        fn () => DisbursementFixture::command($compliance, $disbursement, 'hold', 1),
    ]))->toBe([0, 0]);
    $approved = recordedCodes('disbursement.approve') === ['DISBURSEMENT_INTENT_RECORDED'];
    expect(recordedCodes('disbursement.hold'))->toBe([$approved ? 'DISBURSEMENT_IN_FLIGHT' : 'DISBURSEMENT_HELD'])
        ->and(DisbursementIntent::query()->count())->toBe($approved ? 1 : 0)
        ->and(recordedCodes('disbursement.approve'))->toBe([$approved ? 'DISBURSEMENT_INTENT_RECORDED' : 'DISBURSEMENT_STATE_INVALID']);
});

it('never claims for a maker whose authority was revoked before the worker\'s locked recheck', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    DB::transaction(function () use ($checker, $disbursement, $proof): void {
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        DisbursementFixture::sources()->setConnection(null, 'unavailable');
    });
    DisbursementFixture::sources()->setConnection(null, 'available');

    expect(disbursementContenders([
        fn () => app(DispatchDisbursements::class)->handle(),
        fn () => app(ConfigureStaffAccess::class)->handle($maker->id, true, 'Revoked.', (string) Str::uuid(), ['compliance']),
    ]))->toBe([0, 0]);
    $claimed = DisbursementDispatch::query()->where('phase', 'claimed')->exists();
    expect(DisbursementProviderCall::query()->where('kind', 'send')->count())->toBe($claimed ? 1 : 0)
        ->and(DisbursementClosing::query()->count())->toBe(0);
    if (! $claimed) {
        expect(app(DispatchDisbursements::class)->handle())->toBe(['claimed' => 0, 'sent' => 0]);
    }
});

it('never closes twice when a success and a failure arrive together', function (): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    $success = DisbursementFixture::provider()->callback($intent->id, 'succeeded');
    $failure = DisbursementFixture::provider()->callback($intent->id, 'failed');

    expect(disbursementContenders([fn () => app(RecordPayoutEvent::class)->handle($success), fn () => app(RecordPayoutEvent::class)->handle($failure)]))->toBe([0, 0]);
    // Either one final outcome reconciled before the other arrived, or both were observed first and
    // the conflict stays an unresolved exception. Never two closings, never an effect without one.
    $closings = DisbursementClosing::query()->count();
    expect($closings)->toBeLessThanOrEqual(1)
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toHaveCount($closings)
        ->and(DisbursementReconciliation::query()->whereIn('decision', ['matched_success', 'matched_failure'])->count())->toBe($closings)
        ->and(DisbursementReconciliation::query()->where('decision', 'exception')->exists())->toBeTrue();
});

it('issues once when duplicate successes race the scheduled reconciler', function (): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    $message = DisbursementFixture::provider()->callback($intent->id, 'succeeded');

    expect(disbursementContenders([
        fn () => app(RecordPayoutEvent::class)->handle($message),
        fn () => app(RecordPayoutEvent::class)->handle($message),
        fn () => app(ReconcileDisbursements::class)->handle(),
    ]))->toContain(0)
        ->and(DisbursementClosing::query()->where('kind', 'issued')->count())->toBe(1)
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toHaveCount(1)
        ->and(DisbursementProviderCall::query()->where('kind', 'send')->count())->toBe(1);
});

it('refuses raw concurrent closings and a raw refund closing after an issue', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::approved();
    DisbursementFixture::provider()->callback($intent->id, 'succeeded');
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded'));
    $raw = fn (string $cause): Closure => fn () => DB::transaction(fn () => DB::table('disbursement_closings')->insert(['id' => strtolower((string) Str::ulid()),
        'disbursement_id' => $disbursement->id, 'intent_id' => $intent->id, 'reconciliation_id' => null, 'kind' => 'failed_closing', 'cause' => $cause,
        'causes' => '["mandate"]', 'operation_id' => null, 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()]));

    expect(disbursementContenders([$raw('worker_recheck'), $raw('worker_recheck')]))->toBe([1, 1])
        ->and(DisbursementClosing::query()->sole()->kind)->toBe('issued')
        ->and(fn () => $raw('worker_recheck')())->toThrow(QueryException::class);
});

// PROVISIONAL (#96 5874488658): this probes the synthetic source's advisory Business lock. A probe
// on the real S3-C Business row lock must replace it before joint activation.
it('takes the Business lock before the disbursement row on the issue path', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($intent->id, 'succeeded');
    $probe = tempnam(sys_get_temp_dir(), 'lock-order');

    expect(disbursementContenders([
        function () use ($campaign, $disbursement, $probe): void {
            DB::transaction(function () use ($campaign, $disbursement, $probe): void {
                DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['synthetic-business:'.$campaign->businessId]);
                usleep(1_500_000);
                try {
                    DB::select('SELECT id FROM disbursements WHERE id = ? FOR UPDATE NOWAIT', [$disbursement->id]);
                    file_put_contents($probe, DisbursementClosing::query()->exists() ? 'closed-early' : 'free');
                } catch (QueryException) {
                    file_put_contents($probe, 'disbursement-locked-first');
                }
            });
        },
        function () use ($message): void {
            usleep(400_000);
            app(RecordPayoutEvent::class)->handle($message);
        },
    ]))->toBe([0, 0])
        ->and(file_get_contents($probe))->toBe('free')
        ->and(DisbursementClosing::query()->sole()->kind)->toBe('issued');
    unlink($probe);
});
