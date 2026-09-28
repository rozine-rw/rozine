<?php

declare(strict_types=1);

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/**
 * Runs each contender in its own forked process and connection. Exit 0 is success, 2 is the
 * expected refusal and 1 anything else.
 *
 * @param  list<Closure(): void>  $operations
 * @return list<int>
 */
function walletContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a wallet contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (CommandRejection $rejection) {
                exit($rejection->reason === 'INSUFFICIENT_AVAILABLE_FUNDS' ? 2 : 1);
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
        $statuses[] = $exit === false ? throw new RuntimeException('Wallet contender did not exit normally.') : $exit;
    }
    sort($statuses);

    return $statuses;
}

it('refuses a posting outside the caller\'s transaction', function (): void {
    $fixture = InvestorWalletFixture::ready();
    expect(fn () => app(WalletPostings::class)->lockForParty($fixture['party']->id))->toThrow(WalletViolation::class, 'WALLET_POSTING_TRANSACTION_REQUIRED');
    $wallet = DB::transaction(fn () => app(WalletPostings::class)->lockForParty($fixture['party']->id));
    expect(fn () => app(WalletPostings::class)->hold($wallet, WalletMoney::of('1'), new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid()))))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_TRANSACTION_REQUIRED');
});

it('never overdraws available when two holds race on one wallet', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);
    $hold = fn (): Closure => function () use ($fixture): void {
        DB::transaction(function () use ($fixture): void {
            $postings = app(WalletPostings::class);
            $postings->hold($postings->lockForParty($fixture['party']->id), WalletMoney::of('30000'),
                new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid())));
        });
    };

    expect(walletContenders([$hold(), $hold()]))->toBe([0, 2])
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(1)
        ->and(LedgerLine::query()->whereIn('entry_id', LedgerEntry::query()->where('kind', 'primary_hold')->select('id'))->where('direction', 'debit')->sum('amount'))
        ->toBe('30000');
});

it('posts one commit when the same hold is committed and released at once', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);
    $source = new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid()));
    DB::transaction(function () use ($fixture, $source): void {
        $postings = app(WalletPostings::class);
        $postings->hold($postings->lockForParty($fixture['party']->id), WalletMoney::of('20000'), $source);
    });
    $end = fn (string $movement): Closure => function () use ($fixture, $source, $movement): void {
        DB::transaction(function () use ($fixture, $source, $movement): void {
            $postings = app(WalletPostings::class);
            $postings->{$movement}($postings->lockForParty($fixture['party']->id), WalletMoney::of('20000'), $source);
        });
    };

    expect(walletContenders([$end('commit'), $end('release')]))->toBe([0, 1])
        ->and(LedgerEntry::query()->where('source_id', $source->id)->whereIn('kind', ['primary_commit', 'primary_release'])->count())->toBe(1);
});
