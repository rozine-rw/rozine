<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\VerifiedDestination;
use App\Application\Environment\EnvironmentIsolation;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Disbursement\UnavailablePayoutDestinations;
use App\Infrastructure\Wallet\EloquentBusinessPayoutDestinations;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * The verified Business payout destination (BRS v3 KYC-R03, SVC-R01): the raise is paid to the
 * account the Business linked in its own registered name, and to nothing else.
 */

beforeEach(function (): void {
    $this->actor = User::factory()->create();
    $this->business = function (array $terms = []): BusinessProfile {
        $business = BusinessProfile::factory()->create();
        $mandate = BusinessMandate::factory()->create(['business_id' => $business->id, 'actor_user_id' => $this->actor->id]);
        if ($terms !== []) {
            DB::table('business_mandates')->insert(['id' => strtolower((string) str()->ulid()), 'business_id' => $business->id, 'version' => 2,
                'terms' => json_encode([...$mandate->terms, ...$terms]), 'profile' => json_encode($mandate->profile), 'actor_user_id' => $this->actor->id,
                'evidence_reference' => 'fixture:mandate', 'reason' => 'Changed mandate.', 'policy_version' => $mandate->policy_version, 'created_at' => now('UTC')]);
            DB::table('business_profiles')->where('id', $business->id)->update(['mandate_version' => 2]);
        }

        return $business->refresh();
    };
    $this->destinations = fn (): EloquentBusinessPayoutDestinations => app(EloquentBusinessPayoutDestinations::class);
});

it('pays to the account the Business linked in its own name, bound to its mandate and verification', function (): void {
    $business = ($this->business)();
    $verifiedAt = now('UTC')->subDay()->startOfSecond();
    $account = BusinessFundingMethod::factory()->create(['business_id' => $business->id, 'kind' => 'bank', 'label' => 'Bank of Kigali',
        'masked' => 'Account ····6789', 'reference' => 'bk:000123456789', 'verified_at' => $verifiedAt]);
    $mandate = BusinessMandate::query()->where('business_id', $business->id)->sole();

    $destination = ($this->destinations)()->verified($business->id, 'testing');

    expect($destination)->toBeInstanceOf(VerifiedDestination::class)
        ->and($destination->id)->toBe($account->id)
        ->and($destination->revision)->toBe(1)
        ->and($destination->businessId)->toBe($business->id)
        ->and($destination->mandateId)->toBe($mandate->id)
        ->and($destination->rail)->toBe('bank')
        ->and($destination->accountTokenSha256)->toBe(hash('sha256', 'business-payout-account|bank|bk:000123456789'))
        ->and($destination->environment)->toBe('testing')
        ->and($destination->evidenceSha256)->toMatch('/^[0-9a-f]{64}$/')
        ->and($destination->verifiedAt)->toBe($verifiedAt->toIso8601String())
        ->and($destination->expiresAt)->toBe(EloquentBusinessPayoutDestinations::OPEN_ENDED)
        ->and($destination->masked)->toBe('Bank of Kigali Account ····6789')
        ->and($destination->digest())->toBe(($this->destinations)()->verified($business->id, 'testing')?->digest());
});

it('keeps paying the first linked account until it is unlinked, then moves to the next with a new digest', function (): void {
    $business = ($this->business)();
    $first = BusinessFundingMethod::factory()->create(['business_id' => $business->id, 'verified_at' => now('UTC')->subDays(2)]);
    $second = BusinessFundingMethod::factory()->create(['business_id' => $business->id, 'verified_at' => now('UTC')->subDay()]);

    $before = ($this->destinations)()->verified($business->id, 'testing');
    expect($before?->id)->toBe($first->id);

    $first->forceFill(['revoked_at' => now('UTC')])->save();
    $after = ($this->destinations)()->verified($business->id, 'testing');

    expect($after?->id)->toBe($second->id)->and($after?->digest())->not->toBe($before?->digest());
});

it('expires with a mandate that has an end date', function (): void {
    $expiresAt = now('UTC')->addMonths(6)->startOfSecond();
    $business = ($this->business)(['expires_at' => $expiresAt->format('Y-m-d\TH:i:s\Z')]);
    BusinessFundingMethod::factory()->create(['business_id' => $business->id]);

    expect(($this->destinations)()->verified($business->id, 'testing')?->expiresAt)->toBe($expiresAt->toIso8601String());
});

it('has no destination without every current authority', function (string $case): void {
    $business = match ($case) {
        'revoked mandate' => ($this->business)(['status' => 'revoked']),
        'expired mandate' => ($this->business)(['effective_at' => now('UTC')->subYear()->format('Y-m-d\TH:i:s\Z'),
            'expires_at' => now('UTC')->subSecond()->format('Y-m-d\TH:i:s\Z')]),
        'future mandate' => ($this->business)(['effective_at' => now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z')]),
        default => ($this->business)(),
    };
    if ($case === 'missing mandate') {
        DB::table('business_profiles')->where('id', $business->id)->update(['mandate_version' => 9]);
    }
    match ($case) {
        'no account' => null,
        'unverified account' => BusinessFundingMethod::factory()->unverified()->create(['business_id' => $business->id]),
        'unlinked account' => BusinessFundingMethod::factory()->revoked()->create(['business_id' => $business->id]),
        default => BusinessFundingMethod::factory()->create(['business_id' => $business->id]),
    };

    expect(($this->destinations)()->verified($business->id, 'testing'))->toBeNull();
})->with(['revoked mandate', 'expired mandate', 'future mandate', 'missing mandate', 'no account', 'unverified account', 'unlinked account']);

it('has no destination for an unknown Business or another environment', function (): void {
    $business = ($this->business)();
    BusinessFundingMethod::factory()->create(['business_id' => $business->id]);

    expect(($this->destinations)()->verified(strtolower((string) str()->ulid()), 'testing'))->toBeNull()
        ->and(($this->destinations)()->verified($business->id, 'uat'))->toBeNull()
        ->and(($this->destinations)()->verified($business->id, 'production'))->toBeNull();
});

it('never pays to a synthetically verified account where synthetic money is not allowed', function (string $profile, bool $liveMoney): void {
    $business = ($this->business)();
    BusinessFundingMethod::factory()->create(['business_id' => $business->id]);
    app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => $profile]));
    config(['isolation.live_money_enabled' => $liveMoney]);

    expect(($this->destinations)()->verified($business->id, $profile))->toBeNull();
})->with([['production', false], ['demo', false], ['testing', true]]);

it('leaves the disbursement binding unchanged: fixtures on testing, unavailable everywhere else', function (string $profile, string $expected): void {
    app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => $profile]));

    expect(get_class(app()->make(PayoutDestinations::class)))->toBe($expected);
})->with([['testing', SyntheticDisbursementSources::class], ['uat', UnavailablePayoutDestinations::class],
    ['demo', UnavailablePayoutDestinations::class], ['production', UnavailablePayoutDestinations::class]]);
