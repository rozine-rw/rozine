<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\DispatchDepositIntents;
use App\Domain\Operations\CommandRejection;
use Database\Seeders\SyntheticWalletSeeder;
use Illuminate\Console\Command;
use LogicException;

/**
 * Synthetic S3-B wallet hooks for independent local checks: seed an Investor with a verified
 * synthetic method under an explicit synthetic policy, withdraw the policy, record a section 11.4
 * hold, and deliver a signed synthetic provider event for a recorded deposit. Local and testing
 * only; synthetic evidence says nothing about a real provider or live money.
 */
class PrepareSyntheticWallet extends Command
{
    protected $hidden = true;

    protected $signature = 'local:wallet
        {--seed= : Create (or reuse) a synthetic Investor with this email, a verified synthetic MTN method and a synthetic policy}
        {--no-policy : Append a withdrawn policy version, so deposits are unavailable}
        {--restrict= : Record a section 11.4 high-risk hold for the Investor with this email}
        {--event= : Deliver a synthetic provider event for the deposit recorded under this request id}
        {--state= : The event state: succeeded, failed, unknown or pending}
        {--event-id= : A provider event id to reuse (for duplicate and collision checks)}
        {--amount= : Report this amount instead of the recorded one (for mismatch checks)}';

    protected $description = 'Prepare synthetic S3-B wallet scenarios and provider events (local and testing only)';

    public function handle(SyntheticWalletSeeder $seeder, SyntheticWalletFixtures $fixtures, DispatchDepositIntents $dispatch, ApplyProviderOutcome $apply): int
    {
        $state = $this->option('state');
        if (! $this->option('seed') && ! $this->option('no-policy') && ! $this->option('restrict') && ! $this->option('event')) {
            $this->error('Choose --seed, --no-policy, --restrict or --event.');

            return self::INVALID;
        }
        if ($this->option('event') && ! in_array($state, ['succeeded', 'failed', 'unknown', 'pending'], true)) {
            $this->error('--event needs --state=succeeded, failed, unknown or pending.');

            return self::INVALID;
        }
        try {
            $seeder->assertLocal();
            if (is_string($this->option('seed'))) {
                $user = $seeder->investor($this->option('seed'));
                $prepared = $fixtures->prepareInvestor((string) $user->party_id);
                $this->components->info('Synthetic Investor ready. Deposits credit only after a synthetic provider event.');
                $this->table(['Email', 'Method', 'Policy'], [[$user->email, $prepared['method_id'], $prepared['policy_version']]]);
                $this->line('Synthetic password (new accounts only): '.SyntheticWalletSeeder::PASSWORD);
                $this->line('Wallet: '.route('investor.wallet', [], false));
            }
            if ($this->option('no-policy')) {
                $this->line('Deposit policy withdrawn: '.$fixtures->withdrawPolicy());
            }
            if (is_string($this->option('restrict'))) {
                $this->line('Section 11.4 hold recorded: '.$fixtures->restrict((string) $seeder->existing($this->option('restrict'))->party_id));
            }
            if (is_string($this->option('event'))) {
                $event = $fixtures->event($this->option('event'), (string) $state, $this->option('event-id') ?: null, $this->option('amount') ?: null);
                $dispatch->handle($event['intent_id']);
                $outcome = $apply->handle($event['message']);
                $this->line('Event '.$event['message']['event_id'].': '.$outcome['disposition'].($outcome['replayed'] ? ' (replayed)' : '')
                    .'; deposit '.$outcome['state'].($outcome['credited'] ? '; credited once' : '; nothing credited'));
            }
        } catch (LogicException|CommandRejection $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
