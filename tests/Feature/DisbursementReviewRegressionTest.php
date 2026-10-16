<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Disbursement\SyntheticPayoutProvider;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementProviderEvent;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;
use Tests\Support\PrimarySourceFixture;

/*
 * Regressions from the adversarial pre-review of #176 at ef065d99 (F1–F5). Each asserts the
 * contract the finding showed was broken, ported to automatic disbursement (BRS v2.0 SVC-R01).
 */

// F1b, ported to automatic disbursement (BRS v2.0 SVC-R01): the worker re-verifies what the intent
// bound. A destination rotated after the system recorded its intent and before the after-commit
// worker ran must not be paid, then or later.
it('F1b: does not dispatch an automatic intent whose destination rotated before the worker ran', function (): void {
    $campaign = DisbursementFixture::sources()->fund();
    DB::transaction(function () use ($campaign): void {
        expect(app(OpenFundedDisbursements::class)->handle()['queued'])->toBe(1);
        DisbursementFixture::sources()->setDestination($campaign->businessId, 'rotated');  // before the after-commit worker runs
    });
    $intent = DisbursementIntent::query()->sole();
    expect(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(0);

    app(DispatchDisbursements::class)->handle();

    expect(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(0)
        ->and(DisbursementDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all())->toBe(['queued'])
        ->and(DisbursementClosing::query()->count())->toBe(0);
});

// F2 (5868306136 "event-key collision"): a replay of an already-recorded conflicting event must be harmless.
it('F2: treats an exact replay of a key-conflict callback as a duplicate instead of a database error', function (): void {
    ['intent' => $intent] = DisbursementFixture::paid();
    app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'pending', ['event_id' => 'evt-shared']));
    $conflicting = DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['event_id' => 'evt-shared']);
    expect(app(RecordPayoutEvent::class)->handle($conflicting)['disposition'])->toBe('key_conflict');

    $replay = app(RecordPayoutEvent::class)->handle($conflicting);   // provider retries the same webhook

    expect($replay['disposition'])->toBe('duplicate')
        ->and(DisbursementProviderEvent::query()->where('intent_id', $intent->id)->count())->toBe(2);
});

// F3: a non-final observation (a lagging query or out-of-order webhook) after an applied success.
it('F3: still settles a verified success when a later query answers pending/unknown before reconciliation ran', function (): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::paid();
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

    $source = DB::transaction(function () use ($postings, $fixture): PostingSource {
        $wallet = $postings->lockForParty($fixture['party']->id);
        $source = PrimarySourceFixture::reservation($wallet, '20000');
        $postings->hold($wallet, WalletMoney::of('20000'), $source);
        PrimaryReservationFixture::terminalVersion($source, 'confirmed');
        $postings->commit($wallet, WalletMoney::of('20000'), $source);

        return $source;
    });

    expect(fn () => DB::transaction(function () use ($postings, $fixture, $id, $source): void {
        $postings->issue($postings->lockForParty($fixture['party']->id), WalletMoney::of('20000'), $source, new PostingCause('disbursement_closing', $id()));  // no such closing
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(PDOException::class, 'must name an issued disbursement closing');
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

    DisbursementFixture::paid();
    app(ReconcileDisbursements::class)->handle();

    expect($racing->callbackError)->toBeNull()
        ->and(DisbursementClosing::query()->value('kind'))->toBe('issued');
});
