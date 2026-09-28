<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;
use Tests\Support\InvestorWalletFixture;

/*
 * Real-commit regressions for the S3-D wallet extension that Hussain ran against it (#96
 * 5872558912): an issue racing a refund of the same commitment ends it once, and an outer rollback
 * discards a nested issue together with the settlement account it created.
 */

/**
 * @param  list<Closure(): void>  $operations
 * @return list<int> sorted exit codes: 0 success, 1 refused
 */
function issueContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork an issue contender.');
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
        $statuses[] = $exit === false ? throw new RuntimeException('Issue contender did not exit normally.') : $exit;
    }
    sort($statuses);

    return $statuses;
}

/** @return array{fixture: array<string, mixed>, source: PostingSource} RWF 20,000 committed of 50,000 under one reservation source */
function committedReservation(): array
{
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);
    $source = new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid()));
    DB::transaction(function () use ($fixture, $source): void {
        $postings = app(WalletPostings::class);
        $wallet = $postings->lockForParty($fixture['party']->id);
        $postings->hold($wallet, WalletMoney::of('20000'), $source);
        $postings->commit($wallet, WalletMoney::of('20000'), $source);
    });

    return ['fixture' => $fixture, 'source' => $source];
}

it('ends a commitment once when an issue and a refund race', function (): void {
    ['fixture' => $fixture, 'source' => $source] = committedReservation();
    $closing = DisbursementFixture::issuedClosing();
    $end = fn (string $movement): Closure => function () use ($fixture, $source, $movement, $closing): void {
        DB::transaction(function () use ($fixture, $source, $movement, $closing): void {
            $postings = app(WalletPostings::class);
            $wallet = $postings->lockForParty($fixture['party']->id);
            $movement === 'issue'
                ? $postings->issue($wallet, WalletMoney::of('20000'), $source, new PostingCause('disbursement_closing', $closing))
                : $postings->refund($wallet, WalletMoney::of('20000'), $source);
        });
    };

    expect(issueContenders([$end('issue'), $end('refund')]))->toBe([0, 1]);
    $terminal = LedgerEntry::query()->where('source_id', $source->id)->whereIn('kind', ['primary_issue', 'primary_refund'])->pluck('kind')->all();
    $wallet = app(GetInvestorWallet::class)->handle($fixture['user']->id, 1)['wallet'];
    expect($terminal)->toHaveCount(1)
        ->and($wallet['breakdown']['committed']['amount'])->toBe('0')
        ->and($wallet['breakdown']['available']['amount'])->toBe($terminal[0] === 'primary_issue' ? '30000' : '50000')
        ->and(LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'disbursement_settlement')->exists())->toBe($terminal[0] === 'primary_issue');
});

it('discards a nested issue and the settlement account it created when the outer transaction rolls back', function (): void {
    ['fixture' => $fixture, 'source' => $source] = committedReservation();
    $closing = DisbursementFixture::issuedClosing();
    expect(fn () => DB::transaction(function () use ($fixture, $source, $closing): void {
        DB::transaction(function () use ($fixture, $source, $closing): void {
            $postings = app(WalletPostings::class);
            $postings->issue($postings->lockForParty($fixture['party']->id), WalletMoney::of('20000'), $source,
                new PostingCause('disbursement_closing', $closing));
        });
        throw new RuntimeException('Roll back the reconciliation transaction.');
    }))->toThrow(RuntimeException::class, 'Roll back the reconciliation transaction.');

    expect(LedgerEntry::query()->where('source_id', $source->id)->pluck('kind')->all())->toEqualCanonicalizing(['primary_hold', 'primary_commit'])
        ->and(LedgerAccount::query()->whereNull('wallet_id')->where('kind', 'disbursement_settlement')->exists())->toBeFalse()
        ->and(app(GetInvestorWallet::class)->handle($fixture['user']->id, 1)['wallet']['breakdown']['committed']['amount'])->toBe('20000');
});
