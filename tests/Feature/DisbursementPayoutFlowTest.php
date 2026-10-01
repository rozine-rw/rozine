<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\Contracts\StaffConnections;
use App\Application\Disbursement\DispatchDisbursements;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Disbursement\UnavailableFundedCampaigns;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use App\Models\DisbursementProviderEvent;
use App\Models\DisbursementReconciliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Dispatch, provider observations and reconciliation (MC-08, §10.8, #96 5871859618). A send happens
 * once, after commit and a fresh locked recheck; reconcile and requery only observe the same
 * operation; only a matching, unblocked final observation issues or refunds, exactly once.
 */

/**
 * Approved inside an outer transaction, with $then running before the commit that dispatches.
 *
 * @return array{disbursement: Disbursement, maker: User, checker: User, intent: DisbursementIntent, campaign_id: string, business_id: string}
 */
function approvedThen(Closure $then): array
{
    ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    DB::transaction(function () use ($checker, $disbursement, $proof, $then, $maker, $campaign): void {
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        $then($maker, $checker, $campaign);
    });

    return ['disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker, 'campaign_id' => $campaign->campaignId,
        'business_id' => $campaign->businessId, 'intent' => DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole()];
}

function sends(DisbursementIntent $intent): int
{
    return DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count();
}

function queries(DisbursementIntent $intent): int
{
    return DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'query')->count();
}

/** @return list<string> */
function phases(DisbursementIntent $intent): array
{
    return array_values(array_map(strval(...), DisbursementDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all()));
}

/**
 * @param  array<string, string>  $overrides
 * @return array{disposition: string, decision: string}
 */
function callbackFor(DisbursementIntent $intent, string $state, array $overrides = []): array
{
    return app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, $state, $overrides));
}

it('refuses to run the worker, the reconciler or a requery query inside an open transaction', function (): void {
    ['disbursement' => $disbursement, 'maker' => $maker] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    expect(fn () => DB::transaction(fn () => app(DispatchDisbursements::class)->handle()))->toThrow(LogicException::class, 'DISBURSEMENT_DISPATCH_TRANSACTION_OPEN')
        ->and(fn () => DB::transaction(fn () => app(ReconcileDisbursements::class)->handle()))->toThrow(LogicException::class, 'DISBURSEMENT_RECONCILE_TRANSACTION_OPEN')
        ->and(fn () => DB::transaction(fn () => DisbursementFixture::command($treasury, $disbursement, 'requery', 3)))->toThrow(LogicException::class, 'DISBURSEMENT_QUERY_TRANSACTION_OPEN');
});

it('closes deterministically when the worker recheck fails before any send', function (): void {
    ['intent' => $intent, 'disbursement' => $disbursement, 'campaign_id' => $campaignId, 'checker' => $checker] = approvedThen(
        fn (User $maker, User $checker, $campaign) => DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['exposure']));
    $closing = DisbursementClosing::query()->sole();
    expect(phases($intent))->toBe(['queued', 'recheck_failed'])->and(sends($intent))->toBe(0)
        ->and([$closing->kind, $closing->cause, $closing->causes, $closing->intent_id])->toBe(['failed_closing', 'worker_recheck', ['exposure'], $intent->id])
        ->and(DisbursementFixture::sources()->effects($campaignId)[0]['cause'])->toBe('worker_recheck')
        ->and(DisbursementFixture::detail($checker, $disbursement)['state'])->toBe('failed_closing');
});

it('leaves an intent queued and unsent while any pre-send input is lapsed or unavailable, then sends once it is current', function (string $case): void {
    ['intent' => $intent, 'maker' => $maker, 'checker' => $checker, 'business_id' => $businessId, 'campaign_id' => $campaignId] = approvedThen(
        function (User $maker, User $checker, $campaign) use ($case): void {
            match ($case) {
                'maker revoked' => app(ConfigureStaffAccess::class)->handle($maker->id, true, 'Moved.', (string) Str::uuid(), ['compliance']),
                'checker revoked' => app(ConfigureStaffAccess::class)->handle($checker->id, true, 'Moved.', (string) Str::uuid(), ['analyst']),
                'connection unavailable' => DisbursementFixture::sources()->setConnection(null, 'unavailable'),
                'destination rotated' => DisbursementFixture::sources()->setDestination($campaign->businessId, 'rotated'),
                default => DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'unavailable', ['conditions_precedent']),
            };
        });
    expect(phases($intent))->toBe(['queued'])->and(sends($intent))->toBe(0)->and(DisbursementClosing::query()->count())->toBe(0)
        ->and(app(DispatchDisbursements::class)->handle())->toBe(['claimed' => 0, 'sent' => 0]);
    if ($case === 'destination rotated') {
        return;
    }
    if (in_array($case, ['maker revoked', 'checker revoked'], true)) {
        // A restored role does not revive an act made before the gap (#96 answer 4): still unsent.
        app(ConfigureStaffAccess::class)->handle(($case === 'maker revoked' ? $maker : $checker)->id, true, 'Back.', (string) Str::uuid(),
            [$case === 'maker revoked' ? 'treasury' : 'approver']);
        expect(app(DispatchDisbursements::class)->handle())->toBe(['claimed' => 0, 'sent' => 0])->and(sends($intent))->toBe(0);

        return;
    }
    $case === 'connection unavailable' ? DisbursementFixture::sources()->setConnection(null, 'available')
        : DisbursementFixture::sources()->scriptRecheck($campaignId, 'passed');
    expect(app(DispatchDisbursements::class)->handle())->toBe(['claimed' => 1, 'sent' => 1])->and(phases($intent))->toBe(['queued', 'claimed', 'sent']);
})->with(['maker revoked', 'checker revoked', 'connection unavailable', 'destination rotated', 'recheck unavailable']);

it('records an unacknowledged or failed send as unsent, unknown and never resent', function (string $answer): void {
    ['intent' => $intent, 'checker' => $checker, 'disbursement' => $disbursement] = approvedThen(fn () => DisbursementFixture::provider()->scriptSend($answer === 'nack' ? 'nack' : 'throw'));
    expect(phases($intent))->toBe(['queued', 'claimed', 'unsent'])->and(sends($intent))->toBe(1)
        ->and(DisbursementFixture::detail($checker, $disbursement)['provider']['state'])->toBe('unknown')
        ->and(app(DispatchDisbursements::class)->handle())->toBe(['claimed' => 0, 'sent' => 0])->and(sends($intent))->toBe(1);
})->with(['nack', 'throw']);

it('marks an interrupted claim unknown for a provider without idempotent sends, and resends it only when idempotent and unobserved', function (string $case): void {
    DisbursementFixture::provider()->scriptSend('ack', $case !== 'not idempotent');
    ['intent' => $intent] = approvedThen(fn () => DisbursementFixture::sources()->setConnection(null, 'unavailable'));
    DisbursementFixture::sources()->setConnection(null, 'available');
    expect($intent->idempotent_sends)->toBe($case !== 'not idempotent');
    expect(app(DisbursementStore::class)->claim($intent->id, 5, false))->toHaveCount(1);
    if ($case === 'observed') {
        callbackFor($intent, 'pending');
    }
    $this->travel(6)->minutes();
    $result = app(DispatchDisbursements::class)->handle();
    expect(phases($intent))->toBe(['queued', 'claimed', $case === 'idempotent' ? 'sent' : 'unsent'])
        ->and(sends($intent))->toBe($case === 'idempotent' ? 2 : 1)
        ->and(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('source', 'recovery')->count())->toBe($case === 'idempotent' ? 1 : 0)
        ->and($result['sent'])->toBe($case === 'idempotent' ? 1 : 0);
})->with(['not idempotent', 'idempotent', 'observed']);

it('does not resend an idempotent interrupted claim whose fresh pre-send recheck fails', function (): void {
    DisbursementFixture::provider()->scriptSend('ack', true);
    ['intent' => $intent, 'campaign_id' => $campaignId] = approvedThen(fn () => DisbursementFixture::sources()->setConnection(null, 'unavailable'));
    DisbursementFixture::sources()->setConnection(null, 'available');
    app(DisbursementStore::class)->claim($intent->id, 5, true);
    DisbursementFixture::sources()->scriptRecheck($campaignId, 'failed', ['mandate']);
    $this->travel(6)->minutes();
    app(DispatchDisbursements::class)->handle();
    expect(phases($intent))->toBe(['queued', 'claimed', 'unsent'])->and(sends($intent))->toBe(1)->and(DisbursementClosing::query()->count())->toBe(0);
});

it('requeries the same operation without ever sending again, once per recorded request', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::approved();
    $treasury = DisbursementFixture::staff(['treasury']);
    $key = (string) Str::uuid();
    $first = DisbursementFixture::command($treasury, $disbursement, 'requery', 3, null, $key);
    expect([$first['code'], $first['data']['observation']])->toBe(['PROVIDER_QUERY_RECORDED', null])
        ->and([sends($intent), queries($intent)])->toBe([1, 1])
        ->and(DisbursementFixture::command($treasury, $disbursement, 'requery', 3, null, $key)['operation_id'])->toBe($first['operation_id'])
        ->and([sends($intent), queries($intent)])->toBe([1, 1]);
    DisbursementFixture::provider()->scriptQuery($intent->id, 'failed');
    $failed = DisbursementFixture::command($treasury, $disbursement, 'requery', 3);
    expect($failed['data']['observation'])->toBe(['state' => 'failed', 'disposition' => 'applied'])->and([sends($intent), queries($intent)])->toBe([1, 2])
        ->and(DisbursementClosing::query()->sole()->cause)->toBe('reconciled_failure')
        ->and(fn () => DisbursementFixture::command($treasury, $disbursement, 'requery', 4))->toThrow(CommandRejection::class, 'DISBURSEMENT_STATE_INVALID');
});

it('refuses a requery before dispatch and a requery with a stale revision', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $treasury = DisbursementFixture::staff(['treasury']);
    expect(fn () => DisbursementFixture::command($treasury, $disbursement, 'requery', 0))->toThrow(CommandRejection::class, 'DISBURSEMENT_STATE_INVALID');
    ['disbursement' => $dispatched] = DisbursementFixture::approved();
    expect(DisbursementFixture::command($treasury, $dispatched, 'requery', 1)['code'])->toBe('VERSION_CONFLICT');
});

it('issues once from an authenticated effective instant on the Kigali date, and duplicates add nothing', function (string $effectiveAt, string $date, array $due): void {
    ['disbursement' => $disbursement, 'intent' => $intent, 'campaign' => $campaign, 'checker' => $checker] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['effective_at' => $effectiveAt, 'event_id' => 'evt-success']);
    expect(app(RecordPayoutEvent::class)->handle($message))->toBe(['disposition' => 'applied', 'decision' => 'matched_success'])
        ->and(app(RecordPayoutEvent::class)->handle($message))->toBe(['disposition' => 'duplicate', 'decision' => 'closed'])
        ->and(callbackFor($intent, 'succeeded', ['effective_at' => $effectiveAt]))->toBe(['disposition' => 'duplicate', 'decision' => 'closed']);
    $closing = DisbursementClosing::query()->sole();
    $effects = DisbursementFixture::sources()->effects($campaign->campaignId);
    expect([$closing->kind, $closing->effective_date?->format('Y-m-d'), array_slice($closing->due_dates ?? [], 0, 3)])->toBe(['issued', $date, $due])
        ->and($effects)->toHaveCount(1)->and($effects[0]['holdings'][0]['ordinals'])->toBe($campaign->commitments[0]->ordinals)
        ->and($effects[0]['exposure_reservation_id'])->toBe($campaign->exposureReservationId)
        ->and(DisbursementReconciliation::query()->where('decision', 'matched_success')->count())->toBe(1);
    $detail = DisbursementFixture::detail($checker, $disbursement);
    expect([$detail['state'], $detail['provider']['reconciliation'], $detail['issue']['effective_date'], $detail['allowed_actions']])
        ->toBe(['succeeded', 'matched', $date, []]);
})->with([
    'Jan 31 in a leap year' => ['2028-01-31T08:00:00+00:00', '2028-01-31', ['2028-02-29', '2028-03-31', '2028-04-30']],
    'Kigali midnight' => ['2026-12-31T22:30:00+00:00', '2027-01-01', ['2027-02-01', '2027-03-01', '2027-04-01']],
]);

it('refunds once on a reconciled final failure, and keeps a later success as a blocked conflict with no effect', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent, 'campaign' => $campaign, 'checker' => $checker] = DisbursementFixture::approved();
    expect(callbackFor($intent, 'failed'))->toBe(['disposition' => 'applied', 'decision' => 'matched_failure'])
        ->and(callbackFor($intent, 'succeeded'))->toBe(['disposition' => 'conflict', 'decision' => 'closed']);
    $effects = DisbursementFixture::sources()->effects($campaign->campaignId);
    expect($effects)->toHaveCount(1)->and($effects[0]['kind'])->toBe('failed_closing')
        ->and(array_column($effects[0]['refunds'], 'fee'))->toBe(['0', '0'])
        ->and(DisbursementClosing::query()->sole()->cause)->toBe('reconciled_failure')
        ->and(DisbursementReconciliation::query()->where('decision', 'exception')->sole()->causes)->toBe(['conflict'])
        ->and(sends($intent))->toBe(1);
    $detail = DisbursementFixture::detail($checker, $disbursement);
    expect([$detail['state'], $detail['provider']['reconciliation'], $detail['refund']['commitments']])->toBe(['failed_closing', 'exception', 2]);
});

it('retains key collisions, unverifiable and after-final observations as blocking exceptions', function (string $case): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    if ($case === 'key collision') {
        callbackFor($intent, 'pending', ['event_id' => 'evt-shared']);
        expect(callbackFor($intent, 'succeeded', ['event_id' => 'evt-shared']))->toBe(['disposition' => 'key_conflict', 'decision' => 'exception']);
    }
    if ($case === 'amount mismatch') {
        expect(callbackFor($intent, 'succeeded', ['amount' => '1']))->toBe(['disposition' => 'unverifiable', 'decision' => 'exception']);
    }
    if ($case === 'environment mismatch') {
        expect(callbackFor($intent, 'succeeded', ['environment' => 'production']))->toBe(['disposition' => 'unverifiable', 'decision' => 'exception']);
    }
    if ($case === 'no effective instant') {
        expect(callbackFor($intent, 'succeeded', ['effective_at' => '']))->toBe(['disposition' => 'unverifiable', 'decision' => 'exception']);
    }
    expect(callbackFor($intent, 'succeeded'))->toMatchArray(['decision' => 'exception'])
        ->and(DisbursementClosing::query()->count())->toBe(0)->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toBe([])
        ->and(DisbursementProviderEvent::query()->where('intent_id', $intent->id)->count())->toBeGreaterThanOrEqual(2);
})->with(['key collision', 'amount mismatch', 'environment mismatch', 'no effective instant']);

it('never reverses a reconciled success on a later failure', function (): void {
    ['intent' => $intent, 'campaign' => $campaign] = DisbursementFixture::approved();
    callbackFor($intent, 'succeeded');
    expect(callbackFor($intent, 'failed'))->toBe(['disposition' => 'after_final', 'decision' => 'closed'])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toHaveCount(1)
        ->and(DisbursementClosing::query()->sole()->kind)->toBe('issued');
});

it('reconciles from a scheduled query, records each query, and waits on an open or unanswered one', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    expect(app(ReconcileDisbursements::class)->handle())->toBe(['queried' => 1, 'observed' => 0, 'decisions' => ['open' => 1]]);
    DisbursementFixture::provider()->scriptQuery($intent->id, 'pending');
    expect(app(ReconcileDisbursements::class)->handle())->toBe(['queried' => 1, 'observed' => 1, 'decisions' => ['open' => 1]])
        ->and([sends($intent), queries($intent)])->toBe([1, 2]);
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    expect(app(ReconcileDisbursements::class)->handle()['decisions'])->toBe(['matched_success' => 1])
        ->and(app(ReconcileDisbursements::class)->handle())->toBe(['queried' => 0, 'observed' => 0, 'decisions' => []]);
});

it('keeps an observation and reports the funding source unavailable instead of closing', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    app()->instance(FundedCampaigns::class, new UnavailableFundedCampaigns);
    expect(callbackFor($intent, 'succeeded'))->toBe(['disposition' => 'applied', 'decision' => 'funding_unavailable'])
        ->and(DisbursementClosing::query()->count())->toBe(0)->and(DisbursementProviderEvent::query()->count())->toBe(1);
});

it('refuses an unauthenticated or unmatched callback', function (): void {
    ['intent' => $intent] = DisbursementFixture::approved();
    $message = DisbursementFixture::provider()->callback($intent->id, 'succeeded');
    expect(fn () => app(RecordPayoutEvent::class)->handle([...$message, 'amount' => '1']))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(fn () => app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['reference' => 'rzd_unknown'])))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNMATCHED')
        ->and(fn () => app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'succeeded', ['observed_at' => 'yesterday'])))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_MALFORMED')
        ->and(DisbursementProviderEvent::query()->count())->toBe(0);
});

it('binds only unavailable adapters where synthetic disbursements are not allowed', function (): void {
    config(['isolation.live_money_enabled' => true]);
    expect(get_class(app(FundedCampaigns::class)))->toBe(UnavailableFundedCampaigns::class)
        ->and(app(PayoutDestinations::class)->verified('b', 'testing'))->toBeNull()
        ->and(app(StaffConnections::class)->connection(1, 'b', []))->toBe('unavailable')
        ->and(app(PayoutProvider::class)->name())->toBe('unavailable')
        ->and(app(PayoutProvider::class)->idempotentSends())->toBeFalse()
        ->and(fn () => app(FundedCampaigns::class)->funded(null, 5))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE')
        ->and(fn () => app(DispatchDisbursements::class)->handle())->toThrow(LogicException::class, 'DISBURSEMENT_SYNTHETIC_ONLY')
        ->and(fn () => app(PayoutProvider::class)->verify([]))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(fn () => app(PayoutProvider::class)->send(new PayoutInstruction('i', 'o', 'r', '1', 'RWF', 'd', 'x', 'testing')))->toThrow(LogicException::class, 'PAYOUT_PROVIDER_UNAVAILABLE')
        ->and(fn () => app(PayoutProvider::class)->query(new PayoutInstruction('i', 'o', 'r', '1', 'RWF', 'd', 'x', 'testing')))->toThrow(LogicException::class, 'PAYOUT_PROVIDER_UNAVAILABLE');
    $unavailable = new UnavailableFundedCampaigns;
    foreach (['lockBusiness', 'lockFunded'] as $method) {
        expect(fn () => $unavailable->{$method}('x'))->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
    }
});
