<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

function postingId(): string
{
    return strtolower((string) Str::ulid());
}

/** @return array{user: User, party: Party, wallet: LockedWallet} a wallet holding RWF 50,000 available */
function fundedWallet(string $amount = '50000'): array
{
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, $amount)['data']['intent_id']);

    return [...$fixture, 'wallet' => app(WalletPostings::class)->lockForParty($fixture['party']->id)];
}

/** @return array{string, string, string, string} available, held, committed and total */
function postingBuckets(User $user): array
{
    $wallet = app(GetInvestorWallet::class)->handle($user->id, 1)['wallet'];

    return [$wallet['breakdown']['available']['amount'], $wallet['breakdown']['held']['amount'], $wallet['breakdown']['committed']['amount'], $wallet['total']['amount']];
}

function postingMoney(string $amount): WalletMoney
{
    return WalletMoney::of($amount);
}

it('holds, commits and refunds exactly, keeping each bucket once in the total', function (): void {
    ['user' => $user, 'wallet' => $wallet] = fundedWallet();
    $postings = app(WalletPostings::class);
    $source = PrimaryReservationFixture::postingSource($wallet, '20000');

    $hold = $postings->hold($wallet, postingMoney('20000'), $source);
    expect([$hold->kind, $hold->walletId, $hold->sourceType, $hold->sourceId, $hold->originOperationId, $hold->amount, $hold->replayed])
        ->toBe(['primary_hold', $wallet->walletId, 'primary_reservation', $source->id, $source->originOperationId, '20000', false])
        ->and(postingBuckets($user))->toBe(['30000', '20000', '0', '50000']);

    $commit = $postings->commit($wallet, postingMoney('20000'), $source);
    expect($commit->kind)->toBe('primary_commit')->and($commit->entryId)->not->toBe($hold->entryId)
        ->and(postingBuckets($user))->toBe(['30000', '0', '20000', '50000']);

    $refund = $postings->refund($wallet, postingMoney('20000'), $source);
    expect($refund->kind)->toBe('primary_refund')->and(postingBuckets($user))->toBe(['50000', '0', '0', '50000'])
        ->and(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toEqualCanonicalizing(['primary_hold', 'primary_commit', 'primary_refund'])
        ->and(LedgerLine::query()->whereIn('entry_id', [$hold->entryId, $commit->entryId, $refund->entryId])->count())->toBe(6);
});

it('releases a hold back to available and ends its lifecycle', function (): void {
    ['user' => $user, 'wallet' => $wallet] = fundedWallet();
    $postings = app(WalletPostings::class);
    $source = PrimaryReservationFixture::postingSource($wallet, '20000');
    $postings->hold($wallet, postingMoney('20000'), $source);
    $release = $postings->release($wallet, postingMoney('20000'), $source);

    expect($release->kind)->toBe('primary_release')->and(postingBuckets($user))->toBe(['50000', '0', '0', '50000'])
        ->and(fn () => $postings->commit($wallet, postingMoney('20000'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(fn () => $postings->refund($wallet, postingMoney('20000'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and($postings->hold($wallet, postingMoney('20000'), $source)->replayed)->toBeTrue()
        ->and(fn () => $postings->hold($wallet, postingMoney('20000'), new PostingSource('primary_reservation', $source->id, postingId())))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(LedgerEntry::query()->where('source_id', $source->id)->count())->toBe(2)
        ->and(postingBuckets($user))->toBe(['50000', '0', '0', '50000']);
});

it('refuses a hold beyond available without posting anything', function (): void {
    ['user' => $user, 'wallet' => $wallet] = fundedWallet();
    expect(fn () => app(WalletPostings::class)->hold($wallet, postingMoney('50001'), new PostingSource('primary_reservation', postingId(), postingId())))
        ->toThrow(CommandRejection::class, 'INSUFFICIENT_AVAILABLE_FUNDS')
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0)
        ->and(postingBuckets($user))->toBe(['50000', '0', '0', '50000']);
});

it('returns the original posting for an identical retry and refuses any changed retry', function (): void {
    ['wallet' => $wallet] = fundedWallet();
    ['wallet' => $other] = fundedWallet();
    $postings = app(WalletPostings::class);
    $source = PrimaryReservationFixture::postingSource($wallet, '20000');
    $hold = $postings->hold($wallet, postingMoney('20000'), $source);
    $again = $postings->hold($wallet, postingMoney('20000'), $source);

    expect($again->replayed)->toBeTrue()->and($again->entryId)->toBe($hold->entryId)->and($again->recordedAt)->toBe($hold->recordedAt)
        ->and(fn () => $postings->hold($wallet, postingMoney('20001'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->hold($other, postingMoney('20000'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->hold($wallet, postingMoney('20000'), new PostingSource('primary_reservation', $source->id, postingId())))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT');
    $commit = $postings->commit($wallet, postingMoney('20000'), $source);
    expect($postings->commit($wallet, postingMoney('20000'), $source)->entryId)->toBe($commit->entryId)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->count())->toBe(2);
});

it('lets no other operation, wallet or amount consume a hold', function (): void {
    ['wallet' => $wallet] = fundedWallet();
    ['wallet' => $other] = fundedWallet();
    $postings = app(WalletPostings::class);
    $source = PrimaryReservationFixture::postingSource($wallet, '20000');
    $postings->hold($wallet, postingMoney('20000'), $source);
    $foreign = new PostingSource('primary_reservation', $source->id, postingId());

    expect(fn () => $postings->commit($wallet, postingMoney('20000'), $foreign))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->release($wallet, postingMoney('20000'), $foreign))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->release($other, postingMoney('20000'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->commit($wallet, postingMoney('19999'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->commit($wallet, postingMoney('20000'), new PostingSource('primary_commitment', $source->id, $source->originOperationId)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
    $postings->commit($wallet, postingMoney('20000'), $source);
    expect(fn () => $postings->refund($wallet, postingMoney('20001'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $postings->release($wallet, postingMoney('20000'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(LedgerEntry::query()->where('source_id', $source->id)->count())->toBe(2);
});

it('refuses movements with nothing open to follow', function (string $movement): void {
    ['wallet' => $wallet] = fundedWallet();
    expect(fn () => app(WalletPostings::class)->{$movement}($wallet, postingMoney('1000'), new PostingSource('primary_reservation', postingId(), postingId())))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
})->with(['commit', 'release', 'refund']);

it('validates the source and the locked wallet', function (): void {
    ['wallet' => $wallet] = fundedWallet();
    expect(fn () => new PostingSource('deposit', postingId(), postingId()))->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID')
        ->and(fn () => new PostingSource('primary_reservation', 'R-1', postingId()))->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID')
        ->and(fn () => new PostingSource('primary_reservation', postingId(), strtoupper(postingId())))->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID')
        ->and(fn () => app(WalletPostings::class)->hold(new LockedWallet($wallet->walletId, Party::factory()->create()->id), postingMoney('1000'),
            new PostingSource('primary_reservation', postingId(), postingId())))->toThrow(WalletViolation::class, 'WALLET_POSTING_WALLET_INVALID');
});

it('creates the one wallet per Party when a posting path locks it first', function (): void {
    $party = Party::factory()->verified()->create();
    $locked = app(WalletPostings::class)->lockForParty($party->id);
    expect(app(WalletPostings::class)->lockForParty($party->id))->toEqual($locked)
        ->and(InvestorWallet::query()->where('party_id', $party->id)->sole()->id)->toBe($locked->walletId)
        ->and(fn () => app(WalletPostings::class)->hold($locked, postingMoney('1'), new PostingSource('primary_reservation', postingId(), postingId())))
        ->toThrow(CommandRejection::class, 'INSUFFICIENT_AVAILABLE_FUNDS');
});

it('keeps the lifecycle and non-negative buckets at the database boundary too', function (): void {
    ['wallet' => $wallet] = fundedWallet();
    $available = LedgerAccount::query()->where('wallet_id', $wallet->walletId)->where('kind', 'investor_available')->sole();
    $held = LedgerAccount::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => 'investor_held']);
    $post = function (string $kind, string $source, string $operation, string $from, string $to, string $amount) use ($wallet): void {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $kind, 'source_type' => 'primary_reservation',
            'source_id' => $source, 'origin_operation_id' => $operation]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $from, 'direction' => 'debit', 'amount' => $amount]);
        LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $to, 'direction' => 'credit', 'amount' => $amount]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    };
    $source = PrimaryReservationFixture::postingSource($wallet, '5000');
    $operation = postingId();
    $post('primary_hold', $source->id, $source->originOperationId, $available->id, $held->id, '5000');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    expect(fn () => DB::transaction(fn () => $post('primary_hold', postingId(), $operation, $available->id, $held->id, '50001')))
        ->toThrow(QueryException::class, 'would overdraw an Investor bucket')
        ->and(fn () => DB::transaction(fn () => $post('primary_release', postingId(), $operation, $held->id, $available->id, '5000')))
        ->toThrow(QueryException::class, 'must follow its open hold')
        ->and(fn () => DB::transaction(fn () => $post('primary_release', $source->id, $operation, $held->id, $available->id, '5000')))
        ->toThrow(QueryException::class, 'must follow its open hold')
        ->and(fn () => DB::transaction(fn () => $post('primary_hold', $source->id, $operation, $available->id, $held->id, '5000')))
        ->toThrow(QueryException::class, 'must be the first posting')
        ->and(fn () => DB::transaction(fn () => LedgerEntry::factory()->create(['kind' => 'primary_hold', 'source_type' => 'primary_reservation'])))
        ->toThrow(QueryException::class, 'ledger_entry_source')
        ->and(fn () => DB::transaction(fn () => LedgerEntry::factory()->create(['kind' => 'primary_hold', 'source_type' => 'wallet_deposit_intent', 'origin_operation_id' => postingId()])))
        ->toThrow(QueryException::class, 'ledger_entry_source');
});

it('records the originating deposit operation on a deposit credit entry', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $result = InvestorWalletFixture::deposit($fixture);
    InvestorWalletFixture::settle($result['data']['intent_id']);
    expect(LedgerEntry::query()->where('kind', 'deposit_credit')->sole()->origin_operation_id)->toBe($result['operation_id']);
});

/**
 * Posts a raw primary entry with the given lines and fires the deferred checks, as a commit would.
 *
 * @param  list<array{string, string, string}>  $lines  account kind, direction, amount
 */
function rawPrimaryPosting(LockedWallet $wallet, string $kind, PostingSource $source, array $lines): void
{
    DB::transaction(function () use ($wallet, $kind, $source, $lines): void {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $kind, 'source_type' => $source->type,
            'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId]);
        foreach ($lines as [$account, $direction, $amount]) {
            $id = LedgerAccount::query()->where('wallet_id', $wallet->walletId)->where('kind', $account)->value('id')
                ?? LedgerAccount::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $account])->id;
            LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $id, 'direction' => $direction, 'amount' => $amount]);
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
}

it('binds each raw primary movement to its own source anchor amount and bucket shape', function (string $kind, string $on, string $lines, string $message): void {
    ['wallet' => $wallet] = fundedWallet();
    $postings = app(WalletPostings::class);
    $held = PrimaryReservationFixture::postingSource($wallet, '5000');
    $other = PrimaryReservationFixture::postingSource($wallet, '5000');
    $committed = PrimaryReservationFixture::postingSource($wallet, '5000');
    $postings->hold($wallet, postingMoney('5000'), $held);
    $postings->hold($wallet, postingMoney('5000'), $other);
    $postings->hold($wallet, postingMoney('5000'), $committed);
    $postings->commit($wallet, postingMoney('5000'), $committed);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');

    $parsed = array_map(function (string $line): array {
        [$account, $direction, $amount] = explode(':', $line);

        return [$account, $direction, $amount];
    }, explode(',', $lines));
    expect(fn () => rawPrimaryPosting($wallet, $kind, $on === 'held' ? $held : $committed, $parsed))->toThrow(QueryException::class, $message)
        ->and(LedgerEntry::query()->where('source_id', $held->id)->count())->toBe(1);
})->with([
    'partial release' => ['primary_release', 'held', 'investor_held:debit:500,investor_available:credit:500', 'must move exactly its source anchor amount'],
    'excessive release' => ['primary_release', 'held', 'investor_held:debit:10000,investor_available:credit:10000', 'must move exactly its source anchor amount'],
    'excessive commit' => ['primary_commit', 'held', 'investor_held:debit:10000,investor_committed:credit:10000', 'must move exactly its source anchor amount'],
    'excessive refund' => ['primary_refund', 'committed', 'investor_committed:debit:10000,investor_available:credit:10000', 'must move exactly its source anchor amount'],
    'release crediting committed' => ['primary_release', 'held', 'investor_held:debit:5000,investor_committed:credit:5000', 'must move one amount from its source bucket'],
    'commit from available' => ['primary_commit', 'held', 'investor_available:debit:5000,investor_committed:credit:5000', 'must move one amount from its source bucket'],
    'extra third line' => ['primary_release', 'held', 'investor_held:debit:5000,investor_available:credit:3000,investor_available:credit:2000',
        'must move one amount from its source bucket'],
]);

it('keeps a raw same-transaction hold, commit and refund valid at the database boundary', function (): void {
    ['user' => $user, 'wallet' => $wallet] = fundedWallet();
    $source = PrimaryReservationFixture::postingSource($wallet, '10000');
    DB::transaction(function () use ($wallet, $source): void {
        rawPrimaryPosting($wallet, 'primary_hold', $source, [['investor_available', 'debit', '10000'], ['investor_held', 'credit', '10000']]);
        rawPrimaryPosting($wallet, 'primary_commit', $source, [['investor_held', 'debit', '10000'], ['investor_committed', 'credit', '10000']]);
        rawPrimaryPosting($wallet, 'primary_refund', $source, [['investor_committed', 'debit', '10000'], ['investor_available', 'credit', '10000']]);
    });
    expect(LedgerEntry::query()->where('source_id', $source->id)->count())->toBe(3)
        ->and(postingBuckets($user))->toBe(['50000', '0', '0', '50000']);
});
