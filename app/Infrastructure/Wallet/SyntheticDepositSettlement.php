<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticSettlement;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\BusinessDepositDispatch;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessProviderEvent;
use App\Models\InvestorFundingMethod;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Sleep;
use LogicException;
use Throwable;

/**
 * The UAT settlement of synthetic deposits. Each deposit's event is derived from the deposit alone
 * (its id, reference, amount and recording time), so every attempt signs the same message: the
 * outcome stores apply it once and replay it after that. A deposit paid from the Investor's
 * declining test number fails; every other one succeeds.
 */
final class SyntheticDepositSettlement implements SyntheticSettlement
{
    public const int DELAY_SECONDS = 3;

    /** Whether a settlement is already waiting for this application's termination. */
    private bool $scheduled = false;

    /** Settlement attempts left in a run a worker bounded; null in an unbounded one, such as a web request. */
    private ?int $allowance = null;

    public function __construct(private Application $app, private EnvironmentIsolation $isolation, private SyntheticWalletGuard $guard) {}

    public function enabled(): bool
    {
        return $this->guard->allowed() && $this->isolation->profile() === 'uat';
    }

    /**
     * One wait per application lifetime, however many deposits were sent: the callback is registered
     * once, and it waits only if something is still unsettled when the application terminates and
     * the run's allowance is not spent (the worker settles its batch itself, so it finishes without
     * waiting).
     */
    public function scheduleAfterResponse(): void
    {
        if (! $this->enabled() || $this->scheduled) {
            return;
        }
        $this->scheduled = true;
        $this->app->terminating(function (): void {
            if (! $this->scheduled) {
                return;
            }
            $this->scheduled = false;
            if ($this->allowance !== 0 && ($this->unsettledInvestorIntents()->exists() || $this->unsettledBusinessIntents()->exists())) {
                Sleep::for(self::DELAY_SECONDS)->seconds();
                $this->settlePending();
            }
        });
    }

    public function limitRun(int $limit): void
    {
        $this->allowance = max(0, $limit);
    }

    public function settlePending(int $limit = 50): array
    {
        $result = ['settled' => 0, 'failed' => 0, 'remaining' => 0];
        if (! $this->enabled()) {
            return $result;
        }
        $limit = min($limit, $this->allowance ?? $limit);
        $oldest = [...$this->unsettledInvestorDeposits($limit), ...$this->unsettledBusinessDeposits($limit)];
        usort($oldest, fn (array $first, array $second): int => strcmp($first['intent']->id, $second['intent']->id));
        $batch = array_slice($oldest, 0, $limit);
        if ($this->allowance !== null) {
            $this->allowance -= count($batch);
        }
        foreach ($batch as $deposit) {
            try {
                $deposit['investor']
                    ? $this->app->make(ApplyProviderOutcome::class)->handle($this->message($deposit['intent'], $this->declines($deposit['intent'])))
                    : $this->app->make(ApplyBusinessProviderOutcome::class)->handle($this->message($deposit['intent'], false));
                $result['settled']++;
            } catch (Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }
        $result['remaining'] = $this->unsettledInvestorIntents()->count() + $this->unsettledBusinessIntents()->count();

        return $result;
    }

    /** @return list<array{intent: WalletDepositIntent, investor: true}> */
    private function unsettledInvestorDeposits(int $limit): array
    {
        return array_values($this->unsettledInvestorIntents()->orderBy('id')->limit($limit)->get()
            ->map(fn (WalletDepositIntent $intent): array => ['intent' => $intent, 'investor' => true])->all());
    }

    /** @return list<array{intent: BusinessDepositIntent, investor: false}> */
    private function unsettledBusinessDeposits(int $limit): array
    {
        return array_values($this->unsettledBusinessIntents()->orderBy('id')->limit($limit)->get()
            ->map(fn (BusinessDepositIntent $intent): array => ['intent' => $intent, 'investor' => false])->all());
    }

    /** @return Builder<WalletDepositIntent> */
    private function unsettledInvestorIntents(): Builder
    {
        $acknowledged = WalletDepositDispatch::query()->where('phase', 'acknowledged')->select('intent_id');
        $final = WalletProviderEvent::query()->where('disposition', 'applied')->whereIn('state', ['succeeded', 'failed'])->select('intent_id');

        return WalletDepositIntent::query()->where('provider', 'synthetic')->whereIn('id', $acknowledged)->whereNotIn('id', $final);
    }

    /** @return Builder<BusinessDepositIntent> */
    private function unsettledBusinessIntents(): Builder
    {
        $acknowledged = BusinessDepositDispatch::query()->where('phase', 'acknowledged')->select('intent_id');
        $final = BusinessProviderEvent::query()->where('disposition', 'applied')->whereIn('state', ['succeeded', 'failed'])->select('intent_id');

        return BusinessDepositIntent::query()->where('provider', 'synthetic')->whereIn('id', $acknowledged)->whereNotIn('id', $final);
    }

    private function declines(WalletDepositIntent $intent): bool
    {
        return InvestorFundingMethod::query()->whereKey($intent->method_id)->value('label') === SyntheticWalletFixtures::DECLINING_LABEL;
    }

    /** @return array<string, string> */
    private function message(WalletDepositIntent|BusinessDepositIntent $intent, bool $declined): array
    {
        return $this->app->make(SyntheticEventSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'synthetic-settlement-'.$intent->id,
            'reference' => $intent->provider_reference, 'state' => $declined ? 'failed' : 'succeeded', 'amount' => $intent->amount,
            'currency' => $intent->currency, 'environment' => $this->isolation->profile(),
            'observed_at' => $intent->created_at?->copy()->utc()->startOfSecond()->format(DATE_ATOM)
                ?? throw new LogicException('WALLET_SYNTHETIC_INTENT_UNDATED: a recorded deposit has no recording time.')]);
    }
}
