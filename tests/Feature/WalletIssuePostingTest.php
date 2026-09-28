<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\PrimaryPosting;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/*
 * The S3-D wallet extension for Hussain's review (#96 5871859618): a verified disbursement moves
 * each commitment's committed principal to the system settlement account, exactly once, and never
 * after (or before) a refund of the same commitment.
 */

function issueId(): string
{
    return strtolower((string) Str::ulid());
}

/** @return array{user: User, wallet: LockedWallet, source: PostingSource} a wallet with RWF 20,000 committed of 50,000 */
function committedWallet(): array
{
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);
    $postings = app(WalletPostings::class);

    return DB::transaction(function () use ($fixture, $postings): array {
        $wallet = $postings->lockForParty($fixture['party']->id);
        $source = new PostingSource('primary_commitment', issueId(), issueId());
        $postings->hold($wallet, WalletMoney::of('20000'), $source);
        $postings->commit($wallet, WalletMoney::of('20000'), $source);

        return ['user' => $fixture['user'], 'wallet' => $wallet, 'source' => $source];
    });
}

/** @return array{string, string, string, string} available, held, committed and total */
function issueBuckets(User $user): array
{
    $wallet = app(GetInvestorWallet::class)->handle($user->id, 1)['wallet'];

    return [$wallet['breakdown']['available']['amount'], $wallet['breakdown']['held']['amount'], $wallet['breakdown']['committed']['amount'], $wallet['total']['amount']];
}

function settlementBalance(): string
{
    return (string) DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->whereNull('ledger_accounts.wallet_id')->where('ledger_accounts.kind', 'disbursement_settlement')
        ->selectRaw("coalesce(sum(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0)::text AS balance")->value('balance');
}

/**
 * A raw entry with the given lines, with the deferred checks fired as a commit would.
 *
 * @param  list<array{string, string, string}>  $lines  account kind (settlement is the system account), direction, amount
 */
function rawIssuePosting(LockedWallet $wallet, string $kind, PostingSource $source, array $lines, ?string $causeId): void
{
    DB::transaction(function () use ($wallet, $kind, $source, $lines, $causeId): void {
        $entry = LedgerEntry::factory()->create(['wallet_id' => $wallet->walletId, 'kind' => $kind, 'source_type' => $source->type,
            'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId,
            'cause_type' => $causeId === null ? null : 'disbursement_closing', 'cause_id' => $causeId]);
        foreach ($lines as [$account, $direction, $amount]) {
            $system = in_array($account, ['disbursement_settlement', 'deposit_clearing'], true);
            $id = LedgerAccount::query()->where('wallet_id', $system ? null : $wallet->walletId)->where('kind', $account)->value('id')
                ?? LedgerAccount::factory()->create(['wallet_id' => $system ? null : $wallet->walletId, 'kind' => $account])->id;
            LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $id, 'direction' => $direction, 'amount' => $amount]);
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
}

it('issues exactly the committed amount to settlement, keeping the origin and recording the closing cause', function (): void {
    ['user' => $user, 'wallet' => $wallet, 'source' => $source] = committedWallet();
    $cause = new PostingCause('disbursement_closing', issueId());
    $receipt = DB::transaction(fn () => app(WalletPostings::class)->issue($wallet, WalletMoney::of('20000'), $source, $cause));

    expect([$receipt->kind, $receipt->amount, $receipt->originOperationId, $receipt->causeType, $receipt->causeId, $receipt->replayed])
        ->toBe(['primary_issue', '20000', $source->originOperationId, 'disbursement_closing', $cause->id, false])
        ->and(issueBuckets($user))->toBe(['30000', '0', '0', '30000'])
        ->and(settlementBalance())->toBe('20000')
        ->and(LedgerEntry::query()->whereKey($receipt->entryId)->sole()->payload['cause'])->toBe(['type' => 'disbursement_closing', 'id' => $cause->id]);

    $replay = DB::transaction(fn () => app(WalletPostings::class)->issue($wallet, WalletMoney::of('20000'), $source, $cause));
    expect([$replay->entryId, $replay->replayed])->toBe([$receipt->entryId, true])
        ->and(fn () => DB::transaction(fn () => app(WalletPostings::class)->issue($wallet, WalletMoney::of('20000'), $source, new PostingCause('disbursement_closing', issueId()))))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => DB::transaction(fn () => app(WalletPostings::class)->issue($wallet, WalletMoney::of('19999'), $source, $cause)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => DB::transaction(fn () => app(WalletPostings::class)->refund($wallet, WalletMoney::of('20000'), $source)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(settlementBalance())->toBe('20000');
});

it('refuses an issue after a refund, before a commit, of a reservation, or outside a transaction', function (): void {
    ['wallet' => $wallet, 'source' => $source] = committedWallet();
    $postings = app(WalletPostings::class);
    $cause = new PostingCause('disbursement_closing', issueId());
    DB::transaction(fn () => $postings->refund($wallet, WalletMoney::of('20000'), $source));
    expect(fn () => DB::transaction(fn () => $postings->issue($wallet, WalletMoney::of('20000'), $source, $cause)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');

    $held = new PostingSource('primary_commitment', issueId(), issueId());
    DB::transaction(fn () => $postings->hold($wallet, WalletMoney::of('1000'), $held));
    expect(fn () => DB::transaction(fn () => $postings->issue($wallet, WalletMoney::of('1000'), $held, $cause)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(fn () => DB::transaction(fn () => $postings->issue($wallet, WalletMoney::of('1000'), new PostingSource('primary_reservation', issueId(), issueId()), $cause)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID')
        ->and(fn () => new PostingCause('refund_request', issueId()))->toThrow(WalletViolation::class, 'WALLET_POSTING_CAUSE_INVALID')
        ->and(fn () => new PostingCause('disbursement_closing', 'not-a-ulid'))->toThrow(WalletViolation::class, 'WALLET_POSTING_CAUSE_INVALID');
});

it('makes issue and refund mutually exclusive terminals in the domain', function (): void {
    expect(PrimaryPosting::follows('primary_issue'))->toBe('primary_commit')
        ->and(fn () => PrimaryPosting::assertAllowed('primary_issue', ['primary_hold', 'primary_commit', 'primary_refund']))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(fn () => PrimaryPosting::assertAllowed('primary_refund', ['primary_hold', 'primary_commit', 'primary_issue']))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(fn () => PrimaryPosting::assertAllowed('primary_issue', ['primary_hold']))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID');
    PrimaryPosting::assertAllowed('primary_issue', ['primary_hold', 'primary_commit']);
});

it('refuses raw issue postings that are partial, excessive, misrouted or uncaused', function (string $lines, ?string $cause, string $message): void {
    ['wallet' => $wallet, 'source' => $source] = committedWallet();
    $parsed = array_map(function (string $line): array {
        [$account, $direction, $amount] = explode(':', $line);

        return [$account, $direction, $amount];
    }, explode(',', $lines));
    $cause = $cause === 'cause' ? issueId() : $cause;
    expect(fn () => rawIssuePosting($wallet, 'primary_issue', $source, $parsed, $cause))
        ->toThrow(QueryException::class, $message)
        ->and(LedgerEntry::query()->where('source_id', $source->id)->where('kind', 'primary_issue')->count())->toBe(0);
})->with([
    'partial issue' => ['investor_committed:debit:10000,disbursement_settlement:credit:10000', 'cause', 'must move exactly its source anchor amount'],
    'excessive issue' => ['investor_committed:debit:30000,disbursement_settlement:credit:30000', 'cause', 'must move exactly its source anchor amount'],
    'credit to available' => ['investor_committed:debit:20000,investor_available:credit:20000', 'cause', 'must move one amount from its source bucket'],
    'debit from held' => ['investor_held:debit:20000,disbursement_settlement:credit:20000', 'cause', 'must move one amount from its source bucket'],
    'split settlement' => ['investor_committed:debit:20000,disbursement_settlement:credit:10000,disbursement_settlement:credit:10000', 'cause', 'must move one amount from its source bucket'],
    'deposit clearing instead of settlement' => ['investor_committed:debit:20000,deposit_clearing:credit:20000', 'cause', 'must move one amount from its source bucket'],
    'no cause' => ['investor_committed:debit:20000,disbursement_settlement:credit:20000', null, 'ledger_entry_source'],
    'malformed cause' => ['investor_committed:debit:20000,disbursement_settlement:credit:20000', 'closing-1', 'ledger_entry_source'],
]);

it('refuses a raw refund after an issue and a raw issue after a refund, but keeps a raw commit then issue valid', function (): void {
    ['user' => $user, 'wallet' => $wallet, 'source' => $source] = committedWallet();
    rawIssuePosting($wallet, 'primary_issue', $source, [['investor_committed', 'debit', '20000'], ['disbursement_settlement', 'credit', '20000']], issueId());
    expect(issueBuckets($user))->toBe(['30000', '0', '0', '30000'])
        ->and(fn () => rawIssuePosting($wallet, 'primary_refund', $source, [['investor_committed', 'debit', '20000'], ['investor_available', 'credit', '20000']], null))
        ->toThrow(QueryException::class, 'issued or refunded, never both')
        ->and(fn () => rawIssuePosting($wallet, 'primary_issue', $source, [['investor_committed', 'debit', '20000'], ['disbursement_settlement', 'credit', '20000']], issueId()))
        ->toThrow(QueryException::class);

    ['wallet' => $other, 'source' => $refunded] = committedWallet();
    rawIssuePosting($other, 'primary_refund', $refunded, [['investor_committed', 'debit', '20000'], ['investor_available', 'credit', '20000']], null);
    expect(fn () => rawIssuePosting($other, 'primary_issue', $refunded, [['investor_committed', 'debit', '20000'], ['disbursement_settlement', 'credit', '20000']], issueId()))
        ->toThrow(QueryException::class, 'issued or refunded, never both')
        ->and(fn () => rawIssuePosting($other, 'primary_hold', new PostingSource('primary_commitment', issueId(), issueId()),
            [['investor_available', 'debit', '1000'], ['investor_held', 'credit', '1000']], issueId()))
        ->toThrow(QueryException::class, 'ledger_entry_source');
});

it('refuses to roll the extension back once an issue is recorded, and restores the #172 rules when none is', function (): void {
    $migration = require database_path('migrations/2026_09_29_100200_add_primary_issue_to_wallet_ledger.php');
    $migration->down();
    expect(DB::selectOne("SELECT pg_get_constraintdef(oid) AS def FROM pg_constraint WHERE conname = 'ledger_account_owner'")->def)->not->toContain('disbursement_settlement');
    $migration->up();

    ['wallet' => $wallet, 'source' => $source] = committedWallet();
    DB::transaction(fn () => app(WalletPostings::class)->issue($wallet, WalletMoney::of('20000'), $source, new PostingCause('disbursement_closing', issueId())));
    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Recorded issue postings require a forward migration');
});
