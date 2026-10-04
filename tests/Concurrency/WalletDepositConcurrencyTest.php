<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessProviderEvent;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

/**
 * Each contender runs in its own forked process and connection; exit 0 means it finished.
 *
 * @param  list<Closure(): void>  $operations
 * @return list<int>
 */
function depositContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a deposit contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exit = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        $statuses[] = $exit === false ? throw new RuntimeException('Deposit contender did not exit normally.') : $exit;
    }

    return $statuses;
}

/** @return array<string, string> */
function concurrentEvent(WalletDepositIntent $intent, string $state, string $eventId): array
{
    return app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => $eventId, 'reference' => $intent->provider_reference,
        'state' => $state, 'amount' => $intent->amount, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => '2026-09-28T10:00:00+00:00']);
}

function concurrentIntent(): WalletDepositIntent
{
    $fixture = InvestorWalletFixture::ready();

    return WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id'])->sole();
}

/** The available balance, straight from the committed lines. */
function concurrentAvailable(WalletDepositIntent $intent): string
{
    return (string) (int) LedgerLine::query()->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->where('ledger_accounts.wallet_id', $intent->wallet_id)->where('ledger_accounts.kind', 'investor_available')
        ->selectRaw("coalesce(sum(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) AS balance")->value('balance');
}

it('credits once when the same success is delivered simultaneously', function (): void {
    $intent = concurrentIntent();
    $message = concurrentEvent($intent, 'succeeded', 'synthetic-event-simultaneous');
    $deliver = fn (): Closure => function () use ($message): void {
        expect(app(ApplyProviderOutcome::class)->handle($message)['disposition'])->toBe('applied');
    };

    expect(depositContenders([$deliver(), $deliver()]))->toBe([0, 0])
        ->and(LedgerEntry::query()->count())->toBe(1)->and(WalletDepositCredit::query()->count())->toBe(1)
        ->and(WalletProviderEvent::query()->count())->toBe(1)->and(concurrentAvailable($intent))->toBe('50000');
});

it('credits once when two different success events for one intent race', function (): void {
    $intent = concurrentIntent();
    $deliver = fn (string $id): Closure => function () use ($intent, $id): void {
        expect(app(ApplyProviderOutcome::class)->handle(concurrentEvent($intent, 'succeeded', $id))['disposition'])->toBeIn(['applied', 'duplicate']);
    };

    expect(depositContenders([$deliver('synthetic-event-a'), $deliver('synthetic-event-b')]))->toBe([0, 0])
        ->and(WalletProviderEvent::query()->pluck('disposition')->sort()->values()->all())->toBe(['applied', 'duplicate'])
        ->and(WalletDepositCredit::query()->count())->toBe(1)->and(concurrentAvailable($intent))->toBe('50000');
});

it('credits only one owner when the same provider event key arrives for a Business and an Investor reference at once', function (): void {
    $investor = concurrentIntent();
    $authority = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $recorded = app(RecordBusinessDeposit::class)->handle($authority['users'][0]->id, 1, $business->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => '50000'], BusinessFundingMethod::factory()->create(['business_id' => $business->id])->id);
    $reference = BusinessDepositIntent::query()->whereKey((string) $recorded['data']['intent_id'])->sole()->provider_reference;
    $businessEvent = app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'synthetic-event-shared', 'reference' => $reference,
        'state' => 'succeeded', 'amount' => '50000', 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => '2026-09-28T10:00:00+00:00']);
    $investorEvent = concurrentEvent($investor, 'succeeded', 'synthetic-event-shared');

    expect(depositContenders([fn () => app(ApplyBusinessProviderOutcome::class)->handle($businessEvent), fn () => app(ApplyProviderOutcome::class)->handle($investorEvent)]))
        ->toBe([0, 0]);
    $dispositions = [...WalletProviderEvent::query()->pluck('disposition')->all(), ...BusinessProviderEvent::query()->pluck('disposition')->all()];
    expect($dispositions)->toEqualCanonicalizing(['applied', 'key_conflict'])
        ->and(LedgerEntry::query()->whereIn('kind', ['deposit_credit', 'business_deposit_credit'])->count())->toBe(1);
});

it('lets exactly one final outcome win when a success races a failure', function (): void {
    $intent = concurrentIntent();
    $deliver = fn (string $state): Closure => function () use ($intent, $state): void {
        app(ApplyProviderOutcome::class)->handle(concurrentEvent($intent, $state, 'synthetic-event-'.$state));
    };

    expect(depositContenders([$deliver('succeeded'), $deliver('failed')]))->toBe([0, 0]);
    $applied = WalletProviderEvent::query()->where('disposition', 'applied')->sole();
    $other = WalletProviderEvent::query()->where('disposition', '<>', 'applied')->sole();
    expect($other->disposition)->toBe($applied->state === 'succeeded' ? 'after_final' : 'conflict')
        ->and(WalletDepositCredit::query()->count())->toBe($applied->state === 'succeeded' ? 1 : 0)
        ->and(concurrentAvailable($intent))->toBe($applied->state === 'succeeded' ? '50000' : '0');
});

it('records one intent when the same request is posted twice at once', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $request = (string) Str::uuid();
    $post = fn (): Closure => function () use ($fixture, $request): void {
        expect(InvestorWalletFixture::deposit($fixture, '50000', $request)['code'])->toBe('DEPOSIT_INTENT_RECORDED');
    };

    expect(depositContenders([$post(), $post()]))->toBe([0, 0])
        ->and(WalletDepositIntent::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'wallet.deposit')->count())->toBe(1)
        ->and(LedgerEntry::query()->count())->toBe(0);
});

it('holds the wallet lock through the credit write', function (): void {
    $intent = concurrentIntent();
    $event = 'eloquent.creating: '.WalletDepositCredit::class;
    Event::listen($event, function () use ($intent): void {
        config(['database.connections.wallet_contender' => config('database.connections.pgsql')]);
        $connection = DB::connection('wallet_contender');
        $connection->statement("SET lock_timeout = '500ms'");
        try {
            expect(fn () => $connection->select('SELECT id FROM investor_wallets WHERE id = ? FOR UPDATE', [$intent->wallet_id]))
                ->toThrow(QueryException::class, 'lock timeout');
        } finally {
            DB::purge('wallet_contender');
        }
    });
    try {
        expect(app(ApplyProviderOutcome::class)->handle(concurrentEvent($intent, 'succeeded', 'synthetic-event-locked'))['credited'])->toBeTrue();
    } finally {
        Event::forget($event);
    }
    expect(WalletDepositCredit::query()->count())->toBe(1);
});
