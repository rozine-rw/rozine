<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\DispatchDepositIntents;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/*
 * Hussain's independent #172 review (#96 5871460536): the first and last tests are his, verbatim.
 */

it('does not dispatch an intent when its outer transaction rolls back', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $provider = new class implements DepositProvider
    {
        /** @var list<int> */
        public array $transactionLevels = [];

        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return true;
        }

        public function initiate(DepositInstruction $instruction): bool
        {
            $this->transactionLevels[] = DB::transactionLevel();

            return true;
        }

        public function verify(array $message): VerifiedDepositEvent
        {
            throw new LogicException('Not used in this dispatch test.');
        }
    };
    app()->instance(DepositProvider::class, $provider);

    expect(fn () => DB::transaction(function () use ($fixture): void {
        InvestorWalletFixture::deposit($fixture);
        throw new RuntimeException('Roll back the caller transaction.');
    }))->toThrow(RuntimeException::class, 'Roll back the caller transaction.');

    expect(WalletDepositIntent::query()->count())->toBe(0)
        ->and($provider->transactionLevels)->toBe([]);
});

it('dispatches exactly once, at transaction level 0, after the outer transaction commits', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $provider = new class implements DepositProvider
    {
        /** @var list<int> */
        public array $transactionLevels = [];

        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return true;
        }

        public function initiate(DepositInstruction $instruction): bool
        {
            $this->transactionLevels[] = DB::transactionLevel();

            return true;
        }

        public function verify(array $message): VerifiedDepositEvent
        {
            throw new LogicException('Not used in this dispatch test.');
        }
    };
    app()->instance(DepositProvider::class, $provider);

    $intentId = DB::transaction(function () use ($fixture, $provider): string {
        $intentId = (string) InvestorWalletFixture::deposit($fixture)['data']['intent_id'];
        expect($provider->transactionLevels)->toBe([]);

        return $intentId;
    });

    expect($provider->transactionLevels)->toBe([0])
        ->and(WalletDepositDispatch::query()->where('intent_id', $intentId)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'acknowledged'])
        ->and(fn () => DB::transaction(fn () => app(DispatchDepositIntents::class)->handle()))->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});

it('refuses a database release that consumes another source hold', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture)['data']['intent_id']);
    $postings = app(WalletPostings::class);
    $id = fn (): string => strtolower((string) Str::ulid());
    $first = new PostingSource('primary_reservation', $id(), $id());
    $second = new PostingSource('primary_reservation', $id(), $id());
    $wallet = DB::transaction(function () use ($postings, $fixture, $first, $second): LockedWallet {
        $wallet = $postings->lockForParty($fixture['party']->id);
        $postings->hold($wallet, WalletMoney::of('1000'), $first);
        $postings->hold($wallet, WalletMoney::of('1000'), $second);

        return $wallet;
    });
    $accounts = LedgerAccount::query()->where('wallet_id', $wallet->walletId)->get()->keyBy('kind');

    expect(fn () => DB::transaction(function () use ($wallet, $first, $accounts): void {
        $entry = LedgerEntry::factory()->create([
            'wallet_id' => $wallet->walletId,
            'kind' => 'primary_release',
            'source_type' => $first->type,
            'source_id' => $first->id,
            'origin_operation_id' => $first->originOperationId,
        ]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $accounts['investor_held']->id, 'direction' => 'debit', 'amount' => '2000']);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $accounts['investor_available']->id, 'direction' => 'credit', 'amount' => '2000']);
    }))->toThrow(PDOException::class);
});
