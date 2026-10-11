<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Identity\GetIdentityContext;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\Contracts\SyntheticWalletTesters;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LogicException;

/**
 * Names a staging wallet tester, with a written reason: gives the tester's own verified Investor
 * account a verified synthetic MTN method under the explicit synthetic policy (fee 0, RWF 1,000 to
 * 5,000,000), and with `--declining` a second test number whose deposits fail. Naming a tester is
 * separate from the staging mail list, and on UAT only a named tester can start a deposit, checked
 * again for each one. `--revoke` stops the tester's new deposits; accepted ones still settle. A
 * policy already in force on other terms is never overwritten: the command refuses and changes
 * nothing. It never creates an account or a password, and it refuses wherever synthetic deposits are
 * not allowed: demo, production, or live money on.
 */
class PrepareStagingWallet extends Command
{
    protected $hidden = true;

    protected $signature = 'staging:wallet
        {email : The tester\'s existing, verified Investor account}
        {--reason= : Required audit reason for naming or revoking the tester}
        {--declining : Also add a test number whose deposits fail}
        {--revoke : Stop the tester\'s new synthetic deposits}';

    protected $description = 'Name or revoke a staging wallet tester, with the synthetic deposit policy (local, testing and UAT only)';

    public function handle(SyntheticWalletGuard $guard, GetIdentityContext $identity, SyntheticWalletFixtures $fixtures, SyntheticWalletTesters $testers): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $reason = trim((string) $this->option('reason'));
        try {
            $guard->assertAllowed();
            if ($reason === '' || mb_strlen($reason) > 1000) {
                throw new LogicException('WALLET_STAGING_REASON_REQUIRED: give the reason for this change with --reason, in at most 1,000 characters.');
            }
            $user = User::query()->where('email', $email)->first();
            if ($this->option('revoke')) {
                if ($user === null || $user->party_id === null) {
                    throw new LogicException('WALLET_STAGING_TESTER_NOT_NAMED: that account is not a named wallet tester, so nothing was revoked.');
                }
                $change = $testers->revoke((string) $user->party_id, $user->id, $reason, (string) Str::uuid());
                $this->components->info('Wallet tester revoked: new synthetic deposits are refused; deposits already accepted still settle.');
                $this->line('Change: '.$change);

                return self::SUCCESS;
            }
            if ($user === null || $user->party_id === null || ! in_array('investor', $identity->handle($user->id)['available_roles'], true)) {
                throw new LogicException('WALLET_STAGING_INVESTOR_REQUIRED: that account is not a verified Investor yet.');
            }
            $partyId = (string) $user->party_id;
            $granted = $testers->grant($partyId, $user->id, $reason, (string) Str::uuid(), function () use ($fixtures, $partyId): array {
                $policy = $fixtures->currentPolicy();
                if ($policy !== null && $policy['status'] === 'active'
                    && [$policy['fee'], $policy['minimum'], $policy['maximum']] !== array_values(SyntheticWalletFixtures::POLICY_TERMS)) {
                    throw new LogicException('WALLET_STAGING_POLICY_MISMATCH: the deposit policy in force ('.$policy['version']
                        .') is not the synthetic fee 0, RWF 1,000 to 5,000,000 policy, so nothing was prepared.');
                }

                return [...$fixtures->prepareInvestor($partyId), 'declining' => $this->option('declining') ? $fixtures->addDecliningMethod($partyId) : null];
            });
            $prepared = $granted['prepared'];
            $this->components->info('Synthetic deposits ready. On UAT each one settles by itself a few seconds after it is sent.');
            $this->table(['Email', 'Method', 'Policy', 'Change'], [[$user->email, $prepared['method_id'], $prepared['policy_version'], $granted['change_id'] ?? 'already named']]);
            if ($prepared['declining'] !== null) {
                $this->line('Declining test number: '.$prepared['declining']);
            }
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
