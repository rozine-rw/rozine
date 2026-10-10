<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Environment\Contracts\StagingMailTesterStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\GetIdentityContext;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\User;
use Illuminate\Console\Command;
use LogicException;

/**
 * Lets a staging tester fund a wallet without a real provider: gives the tester's own verified
 * Investor account a verified synthetic MTN method under the explicit synthetic policy (fee 0,
 * RWF 1,000 to 5,000,000), and with `--declining` a second test number whose deposits fail. On UAT
 * only an owner-approved tester qualifies (the staging mail list or a superadmin's named testers).
 * It never creates an account or a password, and it refuses wherever synthetic deposits are not
 * allowed: demo, production, or live money on.
 */
class PrepareStagingWallet extends Command
{
    protected $hidden = true;

    protected $signature = 'staging:wallet
        {email : The tester\'s existing, verified Investor account}
        {--declining : Also add a test number whose deposits fail}';

    protected $description = 'Give a staging tester a synthetic MoMo method under the synthetic deposit policy (local, testing and UAT only)';

    public function handle(SyntheticWalletGuard $guard, EnvironmentIsolation $isolation, StagingMailTesterStore $testers,
        GetIdentityContext $identity, SyntheticWalletFixtures $fixtures): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        try {
            $guard->assertAllowed();
            if ($isolation->profile() === 'uat' && ! $isolation->stagingMailServerApproves($email) && ! $testers->includes($email)) {
                throw new LogicException('WALLET_STAGING_TESTER_REQUIRED: only an owner-approved staging tester can hold a synthetic method.');
            }
            $user = User::query()->where('email', $email)->first();
            if ($user === null || $user->party_id === null || ! in_array('investor', $identity->handle($user->id)['available_roles'], true)) {
                throw new LogicException('WALLET_STAGING_INVESTOR_REQUIRED: that account is not a verified Investor yet.');
            }
            $prepared = $fixtures->prepareInvestor((string) $user->party_id);
            $this->components->info('Synthetic deposits ready. On UAT each one settles by itself a few seconds after it is sent.');
            $this->table(['Email', 'Method', 'Policy'], [[$user->email, $prepared['method_id'], $prepared['policy_version']]]);
            if ($this->option('declining')) {
                $this->line('Declining test number: '.$fixtures->addDecliningMethod((string) $user->party_id));
            }
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
