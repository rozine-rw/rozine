<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\AuthoritativeClearingStatements;
use App\Application\Operations\Contracts\ReconciliationStore;
use App\Application\Operations\Contracts\ReconciliationVariances;
use App\Application\Operations\RecordReconciliationDay;
use App\Domain\Operations\StatementBalance;
use App\Events\ReconciliationVarianceOpened;
use App\Infrastructure\Operations\EloquentReconciliationStore;
use App\Models\User;
use App\Notifications\Staff\ReconciliationVarianceDetected;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    $this->travelTo(now('Africa/Kigali')->setTime(10, 0));
    Notification::fake();
    Event::fake([ReconciliationVarianceOpened::class]);
    $this->treasury = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($this->treasury, ['treasury'], 'Synthetic reconciliation duty.');
});

/**
 * Substitute only the authoritative balance-feed boundary. Persistent cases and all HTTP reads
 * still use the real store; the fixture creates no provider event or invalid ledger posting.
 *
 * @param  list<array{id: 'deposits'|'withdrawals', ledger: string, statement: string}>  $accounts
 */
function reconciliationBalanceFeed(array $accounts, string $referenceRevision = '1'): void
{
    $real = app(EloquentReconciliationStore::class);
    $source = new class($accounts, $real, $referenceRevision) implements ReconciliationStore
    {
        /** @param list<array{id: 'deposits'|'withdrawals', ledger: string, statement: string}> $rows */
        public function __construct(private array $rows, private EloquentReconciliationStore $store, private string $referenceRevision) {}

        public function accounts(DateTimeInterface $asOf): array
        {
            return array_map(fn (array $row): array => [...$row, 'statement_reference' => 'synthetic:'.$row['id'].':'.$asOf->format('U.u').':'.$this->referenceRevision], $this->rows);
        }

        public function breaks(DateTimeInterface $asOf): array
        {
            return $this->store->breaks($asOf);
        }
    };
    app()->instance(ReconciliationStore::class, $source);
}

it('never closes a one-franc cutoff gap without a provider event and records one case shared by every staff view', function (string $ledger, string $statement): void {
    reconciliationBalanceFeed([
        ['id' => 'deposits', 'ledger' => '0', 'statement' => '0'],
        ['id' => 'withdrawals', 'ledger' => $ledger, 'statement' => $statement],
    ]);
    $compliance = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($compliance, ['compliance'], 'Synthetic reconciliation duty.');

    // A read must fail closed even before the scheduled recorder has run, without writing a case.
    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('day_close.state', 'open_break'));
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
    Notification::assertNothingSent();

    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    $case = DB::table('reconciliation_variances')->sole();
    expect($case->ledger)->toBe($ledger)->and($case->statement)->toBe($statement)
        ->and($case->business_date)->toBe(now('Africa/Kigali')->subDay()->toDateString())
        ->and(json_decode($case->alert_audiences, true))->toBe(['treasury', 'compliance', 'cma'])
        ->and(DB::table('wallet_withdrawal_events')->count())->toBe(0);

    foreach ([['staff.reconciliation.index', 'breaks'], ['staff.exceptions.index', 'exceptions'], ['staff.dashboard', 'breaks']] as [$route, $key]) {
        $this->actingAs($this->treasury)->get(route($route))->assertOk()
            ->assertInertia(fn ($page) => $page->has($key, 1)->where($key.'.0.id', $case->id));
    }
    expect(DB::table('reconciliation_variances')->count())->toBe(1);
    Notification::assertSentToTimes($this->treasury, ReconciliationVarianceDetected::class, 1);
    Notification::assertSentToTimes($compliance, ReconciliationVarianceDetected::class, 1);
    Event::assertDispatchedTimes(ReconciliationVarianceOpened::class, 1);
    Event::assertDispatched(ReconciliationVarianceOpened::class, fn (ReconciliationVarianceOpened $event): bool => $event->variance['id'] === $case->id);
    Sanctum::actingAs($this->treasury, ['staff:reconciliation:read', 'staff:exceptions:read', 'staff:dashboard:read']);
    foreach ([['api.v1.staff.reconciliation.index', 'breaks'], ['api.v1.staff.exceptions.index', 'exceptions'], ['api.v1.staff.dashboard', 'breaks']] as [$route, $key]) {
        $this->getJson(route($route))->assertOk()->assertJsonCount(1, 'data.'.$key)->assertJsonPath('data.'.$key.'.0.id', $case->id);
    }
})->with(['ledger ahead' => ['1', '0'], 'statement ahead' => ['0', '1'],
    'beyond native integers' => ['99999999999999999999', '99999999999999999998']]);

it('records no variance for a healthy cutoff and keeps missing or duplicate feed coverage unreconciled', function (string $feed, string $state): void {
    $accounts = match ($feed) {
        'healthy' => [['id' => 'deposits', 'ledger' => '7', 'statement' => '7'], ['id' => 'withdrawals', 'ledger' => '3', 'statement' => '3']],
        'partial' => [['id' => 'deposits', 'ledger' => '0', 'statement' => '0']],
        'duplicate' => [['id' => 'deposits', 'ledger' => '0', 'statement' => '0'], ['id' => 'deposits', 'ledger' => '0', 'statement' => '0']],
        default => [],
    };
    reconciliationBalanceFeed($accounts);
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('day_close.state', $state));
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
    Notification::assertNothingSent();
})->with([
    'healthy' => ['healthy', 'reconciled'],
    'missing feed' => ['missing', 'not_reconciled'],
    'partial feed' => ['partial', 'not_reconciled'],
    'duplicate feed' => ['duplicate', 'not_reconciled'],
]);

it('uses the exact Kigali midnight ledger cutoff with an independently supplied statement', function (): void {
    $day = now('Africa/Kigali')->toImmutable()->startOfDay();
    $this->travelTo($day->subDay()->setTime(23, 59, 59));
    InvestorWalletFixture::policy(maximum: null);
    $investor = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($investor, '5000')['data']['intent_id']);
    $this->travelTo($day->setTime(0, 1));
    independentStatementFeed([['id' => 'deposits', 'amount' => '5000'], ['id' => 'withdrawals', 'amount' => '0']]);
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($investor, '7000')['data']['intent_id']);
    $this->travelTo($day->setTime(10, 0));

    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('accounts.0.ledger.amount', '5000')
            ->where('accounts.0.statement.amount', '5000')->where('day_close.state', 'reconciled'));
});

it('retains unexplained cases after a later read matches and refuses changed cutoff evidence', function (): void {
    $accounts = [['id' => 'deposits', 'ledger' => '1', 'statement' => '0'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']];
    reconciliationBalanceFeed($accounts);
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    $id = DB::table('reconciliation_variances')->value('id');
    $accounts[0]['ledger'] = '2';
    reconciliationBalanceFeed($accounts);
    expect(fn () => app(RecordReconciliationDay::class)->handle())->toThrow(RuntimeException::class, 'RECONCILIATION_CUTOFF_CHANGED');
    $accounts[0]['ledger'] = '0';
    reconciliationBalanceFeed($accounts);
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('day_close.state', 'open_break')->where('breaks.0.id', $id));
    expect(DB::table('reconciliation_variances')->sole()->ledger)->toBe('1');
    Notification::assertSentToTimes($this->treasury, ReconciliationVarianceDetected::class, 1);
});

it('publishes no event or alert from a rolled-back recording', function (): void {
    reconciliationBalanceFeed([['id' => 'deposits', 'ledger' => '1', 'statement' => '0'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']]);
    expect(fn () => DB::transaction(function (): void {
        app(RecordReconciliationDay::class)->handle();
        throw new RuntimeException('Synthetic rollback.');
    }))->toThrow(RuntimeException::class, 'Synthetic rollback.');
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
    Event::assertNotDispatched(ReconciliationVarianceOpened::class);
    Notification::assertNothingSent();
});

it('protects retained evidence from updates, deletes and migration rollback', function (): void {
    reconciliationBalanceFeed([['id' => 'deposits', 'ledger' => '1', 'statement' => '0'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']]);
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    $migration = require database_path('migrations/2026_11_27_190732_create_reconciliation_variances_table.php');
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
    expect(fn () => DB::transaction(fn () => DB::table('reconciliation_variances')->update(['ledger' => '2'])))
        ->toThrow(QueryException::class, 'immutable');
    expect(fn () => DB::transaction(fn () => DB::table('reconciliation_variances')->delete()))
        ->toThrow(QueryException::class, 'immutable');
    expect(DB::table('reconciliation_variances')->sole()->ledger)->toBe('1');
});

it('supports empty-schema rollback and reapplies the new constraints', function (): void {
    $migration = require database_path('migrations/2026_11_27_190732_create_reconciliation_variances_table.php');
    $migration->down();
    $migration->up();
    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
});

it('does not notify an analyst or a disabled reconciliation operator', function (): void {
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Synthetic observer.');
    $disabled = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($disabled, ['treasury'], 'Synthetic former operator.');
    app(ConfigureStaffAccess::class)->handle($disabled->id, false, 'Synthetic suspension.', (string) Str::uuid(), ['treasury']);
    reconciliationBalanceFeed([['id' => 'deposits', 'ledger' => '1', 'statement' => '0'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']]);

    expect(Artisan::call('reconciliation:record-day'))->toBe(0);
    Notification::assertNotSentTo([$analyst, $disabled], ReconciliationVarianceDetected::class);
    Notification::assertSentToTimes($this->treasury, ReconciliationVarianceDetected::class, 1);
});

/** @param list<array{id: string, amount: string}> $balances */
function independentStatementFeed(array $balances, bool $wrongCutoff = false): void
{
    app()->instance(AuthoritativeClearingStatements::class, new class($balances, $wrongCutoff) implements AuthoritativeClearingStatements
    {
        /** @param list<array{id: string, amount: string}> $balances */
        public function __construct(private array $balances, private bool $wrongCutoff) {}

        public function at(DateTimeInterface $asOf): array
        {
            $cutoff = DateTimeImmutable::createFromInterface($asOf);
            if ($this->wrongCutoff) {
                $cutoff = $cutoff->modify('-1 second');
            }

            return array_map(fn (array $row): StatementBalance => new StatementBalance($row['id'], $row['amount'], $cutoff, 'synthetic-independent:'.$row['id'].':'.$cutoff->format('U.u')), $this->balances);
        }
    });
}

it('does not manufacture a healthy close from empty provider outcome tables when no independent statement is configured', function (): void {
    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('day_close.state', 'not_reconciled')->where('accounts', []));
    expect(app(RecordReconciliationDay::class)->handle())->toBe(['state' => 'unavailable', 'recorded' => 0]);
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
    Notification::assertNothingSent();
});

it('only reconciles complete independent statement coverage at the exact cutoff', function (string $scenario): void {
    [$balances, $wrongCutoff, $closed] = match ($scenario) {
        'complete' => [[['id' => 'deposits', 'amount' => '0'], ['id' => 'withdrawals', 'amount' => '0']], false, true],
        'partial' => [[['id' => 'deposits', 'amount' => '0']], false, false],
        'duplicate' => [[['id' => 'deposits', 'amount' => '0'], ['id' => 'deposits', 'amount' => '0']], false, false],
        'wrong cutoff' => [[['id' => 'deposits', 'amount' => '0'], ['id' => 'withdrawals', 'amount' => '0']], true, false],
        default => throw new InvalidArgumentException('Unknown statement fixture.'),
    };
    independentStatementFeed($balances, $wrongCutoff);
    $this->actingAs($this->treasury)->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('day_close.state', $closed ? 'reconciled' : 'not_reconciled'));
})->with(['complete', 'partial', 'duplicate', 'wrong cutoff']);

it('refuses to silently rebind a retained variance to a different statement reference', function (): void {
    $accounts = [['id' => 'deposits', 'ledger' => '1', 'statement' => '0'], ['id' => 'withdrawals', 'ledger' => '0', 'statement' => '0']];
    reconciliationBalanceFeed($accounts);
    expect(app(RecordReconciliationDay::class)->handle()['recorded'])->toBe(1);
    $reference = DB::table('reconciliation_variances')->sole()->statement_reference;
    reconciliationBalanceFeed($accounts, '2');
    expect(fn () => app(RecordReconciliationDay::class)->handle())->toThrow(RuntimeException::class, 'RECONCILIATION_CUTOFF_CHANGED');
    expect(DB::table('reconciliation_variances')->sole()->statement_reference)->toBe($reference);
});

it('refuses a date or instant other than the claimed Kigali daily close at the persistence boundary', function (): void {
    $cutoff = now('Africa/Kigali')->toImmutable()->subDay()->endOfDay();
    $rows = [['id' => 'deposits', 'ledger' => '1', 'statement' => '0', 'statement_reference' => 'synthetic-reference']];
    $store = app(ReconciliationVariances::class);
    expect(fn () => $store->record($cutoff->toDateString(), $cutoff->subSecond(), $rows))->toThrow(RuntimeException::class, 'RECONCILIATION_CUTOFF_INVALID');
    expect(fn () => $store->record($cutoff->addDay()->toDateString(), $cutoff, $rows))->toThrow(RuntimeException::class, 'RECONCILIATION_CUTOFF_INVALID');
    expect(DB::table('reconciliation_variances')->count())->toBe(0);
    Notification::assertNothingSent();
});
