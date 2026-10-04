<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\DispatchBusinessDeposits;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessDepositDispatch;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\DepositPolicy;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WalletDepositIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

/** @return array{business: BusinessProfile, user: User, method: BusinessFundingMethod} */
function businessSettlementFixture(string $fee = '0'): array
{
    $authority = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => $fee, 'minimum' => (string) ((int) $fee + 1000), 'maximum' => '1000000']);

    return ['business' => $business, 'user' => $authority['users'][0], 'method' => BusinessFundingMethod::factory()->create(['business_id' => $business->id])];
}

/** @param array{business: BusinessProfile, user: User, method: BusinessFundingMethod} $fixture */
function businessSettlementDeposit(array $fixture, string $amount = '50000'): BusinessDepositIntent
{
    $result = app(RecordBusinessDeposit::class)->handle($fixture['user']->id, 1, $fixture['business']->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => $amount], $fixture['method']->id);

    return BusinessDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole();
}

/**
 * Delivers a signed synthetic provider event for a Business intent's reference.
 *
 * @return array{disposition: string, state: string, credited: bool, replayed: bool}
 */
function businessSettle(string $reference, string $amount, string $state = 'succeeded', ?string $eventId = null, bool $investorPath = false): array
{
    $message = app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(12)),
        'reference' => $reference, 'state' => $state, 'amount' => $amount, 'currency' => 'RWF', 'environment' => 'testing',
        'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]);

    return $investorPath ? app(ApplyProviderOutcome::class)->handle($message) : app(ApplyBusinessProviderOutcome::class)->handle($message);
}

it('dispatches a recorded Business deposit after commit and credits its net once on a verified success', function (): void {
    $fixture = businessSettlementFixture('200');
    $intent = businessSettlementDeposit($fixture);

    expect(BusinessDepositDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'acknowledged']);
    $outcome = businessSettle($intent->provider_reference, '50000');
    $entry = LedgerEntry::query()->where('source_type', 'business_deposit_intent')->sole();

    expect($outcome)->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'credited' => true, 'replayed' => false])
        ->and([$entry->kind, $entry->wallet_id, $entry->getAttribute('wallet_owner')])->toBe(['business_deposit_credit', $intent->wallet_id, 'business'])
        ->and(BusinessDepositCredit::query()->sole()->amount)->toBe('49800');
    $props = $this->actingAs($fixture['user'])->get(route('business.wallet.show', $fixture['business']->id))->assertOk()->viewData('page')['props'];
    expect($props['wallet']['available'])->toBe(['currency' => 'RWF', 'amount' => '49800'])
        ->and($props['wallet']['pending_deposits'])->toBe(['currency' => 'RWF', 'amount' => '0'])
        ->and($props['history']['items'][0])->toMatchArray(['kind' => 'deposit', 'direction' => 'in', 'amount' => ['currency' => 'RWF', 'amount' => '49800'],
            'psp_fee' => ['currency' => 'RWF', 'amount' => '200'], 'counterparty' => 'MTN MoMo +250 788 ···· 456'])
        ->and($props['deposits'][0]['state'])->toBe('succeeded')->and($props['deposits'][0]['credit_receipt']['code'])->toBe('DEPOSIT_CREDITED');
});

it('replays a duplicate, records a changed body as a key conflict and a wrong amount as a mismatch, crediting nothing more', function (): void {
    $fixture = businessSettlementFixture();
    $intent = businessSettlementDeposit($fixture);
    $mismatch = businessSettlementDeposit($fixture, '20000');

    $first = businessSettle($intent->provider_reference, '50000', 'pending', 'synthetic-event-one');
    expect(businessSettle($intent->provider_reference, '50000', 'pending', 'synthetic-event-one')['replayed'])->toBeTrue()
        ->and(businessSettle($intent->provider_reference, '50000', 'succeeded', 'synthetic-event-one')['disposition'])->toBe('key_conflict')
        ->and(businessSettle($mismatch->provider_reference, '19999')['disposition'])->toBe('mismatch')
        ->and(businessSettle($intent->provider_reference, '50000', 'failed')['credited'])->toBeFalse()
        ->and(businessSettle($intent->provider_reference, '50000')['disposition'])->toBe('conflict')
        ->and($first['disposition'])->toBe('duplicate')
        ->and(BusinessDepositCredit::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'business_deposit_credit')->count())->toBe(0);
});

it('routes an event only to the owner its reference is registered to', function (): void {
    $business = businessSettlementDeposit(businessSettlementFixture());
    $investor = InvestorWalletFixture::ready();
    $investorIntent = (string) InvestorWalletFixture::deposit($investor)['data']['intent_id'];
    $reference = WalletDepositIntent::query()->whereKey($investorIntent)->sole()->provider_reference;

    expect(fn () => businessSettle($reference, '50000'))->toThrow(CommandRejection::class, 'DEPOSIT_REFERENCE_UNKNOWN')
        ->and(fn () => businessSettle($business->provider_reference, '50000', investorPath: true))->toThrow(CommandRejection::class, 'DEPOSIT_REFERENCE_UNKNOWN')
        ->and(fn () => businessSettle('syn_'.str_repeat('0', 40), '50000'))->toThrow(CommandRejection::class, 'DEPOSIT_REFERENCE_UNKNOWN')
        ->and(LedgerEntry::query()->count())->toBe(0);
});

it('records a Business send that throws as unacknowledged and refuses to dispatch inside an open transaction', function (): void {
    app()->instance(DepositProvider::class, new class implements DepositProvider
    {
        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return false;
        }

        public function initiate(DepositInstruction $instruction): bool
        {
            throw new RuntimeException('provider timeout');
        }

        public function verify(array $message): VerifiedDepositEvent
        {
            throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
        }
    });
    $intent = businessSettlementDeposit(businessSettlementFixture());

    expect(BusinessDepositDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'unacknowledged'])
        ->and(fn () => DB::transaction(fn () => app(DispatchBusinessDeposits::class)->handle()))->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});

it('refuses a provider event key the other owner already consumed, in either arrival order', function (bool $investorFirst): void {
    $this->freezeSecond();
    $business = businessSettlementDeposit(businessSettlementFixture());
    $investor = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit(InvestorWalletFixture::ready())['data']['intent_id'])->sole();
    $deliveries = [[$investor->provider_reference, $investor->amount, true], [$business->provider_reference, $business->amount, false]];
    [$first, $second] = $investorFirst ? $deliveries : array_reverse($deliveries);

    expect(businessSettle($first[0], $first[1], eventId: 'synthetic-event-shared', investorPath: $first[2]))
        ->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'credited' => true, 'replayed' => false])
        ->and(businessSettle($second[0], $second[1], eventId: 'synthetic-event-shared', investorPath: $second[2]))
        ->toBe(['disposition' => 'key_conflict', 'state' => 'pending', 'credited' => false, 'replayed' => false])
        ->and(businessSettle($second[0], '1', eventId: 'synthetic-event-shared', investorPath: $second[2])['disposition'])->toBe('key_conflict')
        ->and(businessSettle($first[0], $first[1], eventId: 'synthetic-event-shared', investorPath: $first[2])['replayed'])->toBeTrue()
        ->and(LedgerEntry::query()->whereIn('kind', ['deposit_credit', 'business_deposit_credit'])->count())->toBe(1);
})->with(['investor first' => [true], 'business first' => [false]]);
