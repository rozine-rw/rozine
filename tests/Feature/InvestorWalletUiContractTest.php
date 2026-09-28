<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\TestCase;

/**
 * The live Resource against the C3 wallet fixtures the web tests render: the same keys at every
 * level, compared recursively. A null link in the live output is the narrowed C3 shell contract.
 *
 * @param  array<string, mixed>  $live
 * @param  array<string, mixed>  $fixture
 */
function walletUiSameShape(array $live, array $fixture, string $path): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (! is_array($value) || ! is_array($other)) {
            continue;
        }
        if (! array_is_list($value) && ! array_is_list($other)) {
            walletUiSameShape($value, $other, "{$path}.{$key}");
        } elseif ($value !== [] && $other !== [] && is_array($value[0]) && is_array($other[0])) {
            walletUiSameShape($value[0], $other[0], "{$path}.{$key}.0");
        }
    }
}

/**
 * The page props without the app-wide shared props, exactly as the Resource shapes them, plus the
 * same Resource over the API: both transports carry one shape.
 *
 * @param  array<string, mixed>  $query
 * @return array<string, mixed>
 */
function walletUiProps(TestCase $test, User $user, array $query = []): array
{
    Sanctum::actingAs($user, ['investor:read', 'investor:command']);
    $api = $test->getJson(route('api.v1.investor.wallet', $query))->assertOk()->json('data');
    $web = $test->actingAs($user, 'web')->get(route('investor.wallet', $query))->assertOk()->viewData('page')['props'];
    $web = array_intersect_key($web, $api);
    walletUiSameShape($web, $api, 'web');

    return $web;
}

/** @return array<string, mixed> */
function walletUiFixture(string $name): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    expect($fixture['component'])->toBe('investor/wallet');

    return $fixture['props'];
}

it('matches the live-minimal wallet shape for an empty wallet', function (): void {
    $investor = InvestorWalletFixture::investor();
    $props = walletUiProps($this, $investor['user']);

    walletUiSameShape($props, walletUiFixture('investor-wallet-live-minimal'), 'props');
    expect($props['funding'])->toBe(walletUiFixture('investor-wallet-live-minimal')['funding'])
        ->and($props['history']['filters'])->toBe(walletUiFixture('investor-wallet-live-minimal')['history']['filters']);
});

it('matches the deposit, pending and receipt fixtures once deposits are recorded and credited', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $credited = InvestorWalletFixture::deposit($fixture, '100000');
    InvestorWalletFixture::settle($credited['data']['intent_id']);
    InvestorWalletFixture::deposit($fixture, '200000');
    $pending = walletUiProps($this, $fixture['user'], ['kind' => 'deposit', 'amount' => '100000']);
    $receipt = walletUiProps($this, $fixture['user'], ['receipt' => $pending['deposits'][1]['credit_receipt']['receipt_id']]);

    walletUiSameShape($pending, walletUiFixture('investor-wallet-deposit'), 'deposit');
    walletUiSameShape($pending['deposits'][1], walletUiFixture('investor-wallet-deposit-pending')['deposits'][1], 'deposits.1');
    walletUiSameShape($pending['history'], walletUiFixture('investor-wallet-deposit-pending')['history'], 'history');
    walletUiSameShape($receipt['receipt'], walletUiFixture('investor-wallet-receipt')['receipt'], 'receipt');
    expect(json_encode([$pending, $receipt], JSON_THROW_ON_ERROR))->not->toContain('/preview/')
        ->and(array_column($pending['deposits'], 'state'))->toBe(['pending', 'succeeded']);
});
