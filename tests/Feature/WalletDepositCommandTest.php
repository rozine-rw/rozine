<?php

declare(strict_types=1);

use App\Application\Wallet\FindWalletOperation;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\RoleMembership;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

it('records an immutable intent receipt and its dispatch outbox row without crediting anything', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $request = (string) Str::uuid();
    $result = InvestorWalletFixture::deposit($fixture, '50000', $request);
    $intent = WalletDepositIntent::query()->sole();
    $operation = CommandOperation::query()->where('command', 'wallet.deposit')->sole();

    expect($result['status'])->toBe('completed')->and($result['code'])->toBe('DEPOSIT_INTENT_RECORDED')
        ->and($result['policy_version'])->toBe($fixture['policy']->version)
        ->and($result['data']['receipt'])->toMatchArray(['receipt_id' => $intent->id, 'operation_id' => $operation->id, 'request_id' => $request,
            'code' => 'DEPOSIT_INTENT_RECORDED', 'amount' => ['currency' => 'RWF', 'amount' => '50000'], 'units' => null,
            'policy_version' => $fixture['policy']->version, 'disclosure_version' => null])
        ->and($intent->operation_id)->toBe($operation->id)->and($intent->party_id)->toBe($fixture['party']->id)
        ->and([$intent->amount, $intent->fee, $intent->credited, $intent->provider])->toBe(['50000', '0', '50000', 'synthetic'])
        ->and($operation->target_type)->toBe('investor_wallet')->and($operation->target_id)->toBe($intent->wallet_id)
        ->and(WalletDepositDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all())->toBe(['queued'])
        ->and(LedgerEntry::query()->count())->toBe(0)->and(LedgerLine::query()->count())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(0)
        ->and(json_encode($result))->not->toContain($intent->provider_reference);
});

it('replays the same key and body and refuses a changed body under the same key', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $request = (string) Str::uuid();
    $first = InvestorWalletFixture::deposit($fixture, '50000', $request);
    expect(InvestorWalletFixture::deposit($fixture, '50000', strtoupper($request)))->toBe($first)
        ->and(fn () => InvestorWalletFixture::deposit($fixture, '60000', $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(WalletDepositIntent::query()->count())->toBe(1)
        ->and(WalletDepositDispatch::query()->count())->toBe(1);
});

it('refuses without an applicable versioned policy and journals the refusal', function (Closure $policy): void {
    $policy();
    $fixture = InvestorWalletFixture::investor();
    $fixture['method'] = InvestorWalletFixture::method($fixture['party']);
    $request = (string) Str::uuid();
    $refused = InvestorWalletFixture::deposit($fixture, '50000', $request);
    expect($refused['status'])->toBe('rejected')->and($refused['code'])->toBe('POLICY_INPUT_REQUIRED')->and($refused['http_status'])->toBe(409)
        ->and(InvestorWalletFixture::policy(version: 'synthetic-deposit-policy-future')->effective_at->isPast())->toBeTrue()
        ->and(InvestorWalletFixture::deposit($fixture, '50000', $request))->toBe($refused)
        ->and(WalletDepositIntent::query()->count())->toBe(0);
})->with([
    'no policy' => [fn () => null],
    'latest version withdrawn' => [function (): void {
        DepositPolicy::factory()->create(['effective_at' => now()->subMinutes(2)]);
        DepositPolicy::factory()->withdrawn()->create(['effective_at' => now()->subMinute()]);
    }],
    'not yet effective' => [fn () => DepositPolicy::factory()->create(['effective_at' => now()->addMinutes(5)])],
]);

it('refuses a method that is not the caller\'s verified, unrevoked method without disclosing it', function (Closure $method): void {
    $fixture = InvestorWalletFixture::ready();
    $refused = InvestorWalletFixture::deposit($fixture, '50000', methodId: $method($fixture));
    expect($refused['code'])->toBe('DEPOSIT_METHOD_UNVERIFIED')->and($refused['http_status'])->toBe(409)
        ->and(array_keys($refused['data']))->toBe(['wallet_id'])
        ->and(WalletDepositIntent::query()->count())->toBe(0);
})->with([
    'another Party' => [fn (): string => InvestorFundingMethod::factory()->create()->id],
    'unverified' => [fn (array $fixture): string => InvestorFundingMethod::factory()->unverified()->create(['party_id' => $fixture['party']->id])->id],
    'revoked' => [fn (array $fixture): string => InvestorFundingMethod::factory()->revoked()->create(['party_id' => $fixture['party']->id])->id],
    'verified later' => [fn (array $fixture): string => InvestorFundingMethod::factory()->create(['party_id' => $fixture['party']->id, 'verified_at' => now()->addHour()])->id],
    'unknown' => [fn (): string => strtolower((string) Str::ulid())],
]);

it('refuses amounts outside the policy bounds as a journaled field error', function (string $amount, string $currency, string $message): void {
    $fixture = InvestorWalletFixture::ready();
    $refused = InvestorWalletFixture::deposit($fixture, $amount, currency: $currency);
    expect($refused['code'])->toBe('VALIDATION_FAILED')->and($refused['http_status'])->toBe(422)
        ->and($refused['field_errors'])->toBe(['amount' => [$message]])
        ->and(WalletDepositIntent::query()->count())->toBe(0);
})->with([
    'below minimum' => ['999', 'RWF', 'Deposit at least RWF 1000.'],
    'above maximum' => ['1000001', 'RWF', 'Deposit at most RWF 1000000.'],
    'zero' => ['0', 'RWF', 'Enter a whole RWF amount of at most 12 digits.'],
    'leading zero' => ['05000', 'RWF', 'Enter a whole RWF amount of at most 12 digits.'],
    'thirteen digits' => ['1000000000000', 'RWF', 'Enter a whole RWF amount of at most 12 digits.'],
    'another currency' => ['50000', 'USD', 'Enter a whole RWF amount of at most 12 digits.'],
]);

it('records a synthetic nonzero fee explicitly and credits only the net amount on success', function (): void {
    InvestorWalletFixture::policy('150', '1000', null, 'synthetic-deposit-policy-fee-150');
    $fixture = InvestorWalletFixture::ready();
    $result = InvestorWalletFixture::deposit($fixture, '5000');
    $intent = WalletDepositIntent::query()->sole();
    expect($result['policy_version'])->toBe('synthetic-deposit-policy-fee-150')
        ->and([$intent->amount, $intent->fee, $intent->credited])->toBe(['5000', '150', '4850']);
});

it('keeps deposits open under the 11.4 high-risk hold but honours an order whose scope covers deposits', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorAccountRestriction::factory()->create(['party_id' => $fixture['party']->id]);
    expect(InvestorWalletFixture::deposit($fixture)['code'])->toBe('DEPOSIT_INTENT_RECORDED');
    InvestorAccountRestriction::factory()->externalOrder(['withdrawals'])->create(['party_id' => $fixture['party']->id]);
    expect(InvestorWalletFixture::deposit($fixture)['code'])->toBe('DEPOSIT_INTENT_RECORDED');
    InvestorAccountRestriction::factory()->externalOrder()->create(['party_id' => $fixture['party']->id, 'effective_at' => now()->subHours(2), 'expires_at' => now()->subHour()]);
    InvestorAccountRestriction::factory()->externalOrder()->create(['party_id' => $fixture['party']->id, 'effective_at' => now()->addHour()]);
    expect(InvestorWalletFixture::deposit($fixture)['code'])->toBe('DEPOSIT_INTENT_RECORDED');
    InvestorAccountRestriction::factory()->externalOrder()->create(['party_id' => $fixture['party']->id]);
    $refused = InvestorWalletFixture::deposit($fixture);
    expect($refused['code'])->toBe('RESTRICTION_ACTIVE')->and($refused['http_status'])->toBe(409)
        ->and(WalletDepositIntent::query()->count())->toBe(3);
});

it('resolves the Party from current investor authority only', function (): void {
    $fixture = InvestorWalletFixture::ready();
    expect(fn () => InvestorWalletFixture::deposit($fixture, context: 0))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    RoleMembership::query()->where('party_id', $fixture['party']->id)->update(['status' => 'suspended']);
    expect(fn () => InvestorWalletFixture::deposit($fixture))->toThrow(IdentityViolation::class)
        ->and(WalletDepositIntent::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'wallet.deposit')->count())->toBe(0);
});

it('looks up the recorded result for its own Party only, and never resends', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $other = InvestorWalletFixture::ready();
    $request = (string) Str::uuid();
    $result = InvestorWalletFixture::deposit($fixture, '50000', $request);
    $find = app(FindWalletOperation::class);
    expect($find->handle($fixture['user']->id, 1, 'wallet.deposit', $request))->toBe($result)
        ->and(fn () => $find->handle($other['user']->id, 1, 'wallet.deposit', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $find->handle($fixture['user']->id, 1, 'campaign.cancel', $request))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $find->handle($fixture['user']->id, 1, 'wallet.deposit', (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $find->handle($fixture['user']->id, 0, 'wallet.deposit', $request))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT')
        ->and(WalletDepositIntent::query()->count())->toBe(1);
});

it('applies no synthetic policy where the synthetic provider is not allowed', function (): void {
    $fixture = InvestorWalletFixture::ready();
    config(['isolation.live_money_enabled' => true]);
    expect(InvestorWalletFixture::deposit($fixture)['code'])->toBe('POLICY_INPUT_REQUIRED')
        ->and(WalletDepositIntent::query()->count())->toBe(0);
});
