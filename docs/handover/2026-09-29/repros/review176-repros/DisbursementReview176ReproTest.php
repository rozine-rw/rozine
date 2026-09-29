<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Disbursement\SyntheticPayoutProvider;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementProviderEvent;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;
use Tests\Support\InvestorWalletFixture;

/*
 * Adversarial repros for #176 at ef065d99 (pre-Hussain review). Each test asserts the CONTRACT;
 * a failing test is a finding.
 */

/** @param list<string> $roles */
function r176Roles(User $user, array $roles): void
{
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Role change.', (string) Str::uuid(), $roles);
}

// F1 (5871859618 answer 4): a revoked maker's authorization is void; restoring the role must not revive it.
it('F1a: does not revive a revoked maker authorization when the role is restored before approve', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    r176Roles($maker, ['compliance']);   // revoked: approve would now be MAKER_AUTHORIZATION_VOID
    r176Roles($maker, ['treasury']);     // restored, with no fresh authorization recorded

    $approve = DisbursementFixture::command($checker, $disbursement, 'approve', 1, DisbursementFixture::stepUp($checker, $disbursement)['proof']);

    expect($approve['code'])->toBe('MAKER_AUTHORIZATION_VOID')
        ->and(DisbursementIntent::query()->count())->toBe(0);
});

it('F1b: does not dispatch on a maker authorization revoked after approval and restored before the worker', function (): void {
    ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    DB::transaction(function () use ($checker, $disbursement, $proof, $maker): void {
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        r176Roles($maker, ['compliance']);  // revoked before the after-commit worker runs: stays queued
    });
    $intent = DisbursementIntent::query()->sole();
    expect(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(0);

    r176Roles($maker, ['treasury']);
    app(DispatchDisbursements::class)->handle();

    expect(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(0);
});

// F2 (5868306136 "event-key collision"): a replay of an already-recorded conflicting event must be harmless.
it('F2: treats an exact replay of a key-conflict callback as a duplicate instead of a database error', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'pending', ['event_id' => 'evt-shared']));
    $conflicting = DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['event_id' => 'evt-shared']);
    expect(app(RecordPayoutEvent::class)->handle($conflicting)['disposition'])->toBe('key_conflict');

    $replay = app(RecordPayoutEvent::class)->handle($conflicting);   // provider retries the same webhook

    expect($replay['disposition'])->toBe('duplicate')
        ->and(DisbursementProviderEvent::query()->where('intent_id', $intent->id)->count())->toBe(2);
});

// F3: a non-final observation (a lagging query or out-of-order webhook) after an applied success.
it('F3: still settles a verified success when a later query answers pending/unknown before reconciliation ran', function (): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    app()->instance(FundedCampaigns::class, new UnavailableFundedCampaigns);
    expect(app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded'))['decision'])
        ->toBe('funding_unavailable');
    DisbursementFixture::provider()->scriptQuery($intent->id, 'unknown');
    app(ReconcileDisbursements::class)->handle();                  // scheduled tick during the outage
    app()->forgetInstance(FundedCampaigns::class);
    DisbursementFixture::provider()->scriptQuery($intent->id, null);

    app(ReconcileDisbursements::class)->handle();                  // funding back

    expect(DisbursementClosing::query()->where('intent_id', $intent->id)->value('kind'))->toBe('issued');
});

// F4 (5871859618: "immutable originating operation plus separate closing/cause provenance").
it('F4: refuses a primary_issue whose cause is not an issued disbursement closing', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, '50000')['data']['intent_id']);
    $postings = app(WalletPostings::class);
    $id = fn (): string => strtolower((string) Str::ulid());

    expect(fn () => DB::transaction(function () use ($postings, $fixture, $id): void {
        $wallet = $postings->lockForParty($fixture['party']->id);
        $source = new PostingSource('primary_reservation', $id(), $id());
        $postings->hold($wallet, WalletMoney::of('20000'), $source);
        $postings->commit($wallet, WalletMoney::of('20000'), $source);
        $postings->issue($wallet, WalletMoney::of('20000'), $source, new PostingCause('disbursement_closing', $id()));  // no such closing
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(PDOException::class);
    expect(LedgerEntry::query()->where('kind', 'primary_issue')->count())->toBe(0);
});

// F5: an authenticated callback that arrives while the send is in flight (before recordDispatch).
it('F5: records a success callback that races the send without a database error, and settles it later', function (): void {
    $synthetic = app(SyntheticPayoutProvider::class);
    $racing = new class($synthetic) implements PayoutProvider
    {
        public ?string $callbackError = null;

        public function __construct(private SyntheticPayoutProvider $inner) {}

        public function name(): string
        {
            return $this->inner->name();
        }

        public function idempotentSends(): bool
        {
            return $this->inner->idempotentSends();
        }

        public function send(PayoutInstruction $instruction): bool
        {
            $acknowledged = $this->inner->send($instruction);
            // The provider's webhook lands before send() returns to the worker.
            try {
                app(RecordPayoutEvent::class)->handle($this->inner->callback($instruction->intentId, 'succeeded'));
            } catch (Throwable $exception) {
                $this->callbackError = $exception::class.': '.substr($exception->getMessage(), 0, 160);
            }

            return $acknowledged;
        }

        public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent
        {
            return $this->inner->query($instruction);
        }

        public function verify(array $message): VerifiedPayoutEvent
        {
            return $this->inner->verify($message);
        }
    };
    app()->instance(PayoutProvider::class, $racing);

    DisbursementFixture::approved();
    app(ReconcileDisbursements::class)->handle();

    expect($racing->callbackError)->toBeNull()
        ->and(DisbursementClosing::query()->value('kind'))->toBe('issued');
});

// V1 (verification, expected to pass): a proof minted for one disbursement never approves another.
it('V1: refuses a proof minted for another disbursement at the same revision', function (): void {
    ['disbursement' => $first] = DisbursementFixture::funded();
    ['disbursement' => $second] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $first, 'authorize', 0);
    DisbursementFixture::command($maker, $second, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $first)['proof'];

    expect(DisbursementFixture::command($checker, $second, 'approve', 1, $proof)['code'])->toBe('STEP_UP_INVALID')
        ->and(DisbursementIntent::query()->count())->toBe(0);
});
