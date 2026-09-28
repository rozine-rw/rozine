<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;
use Tests\Support\InvestorWalletFixture;

/**
 * Opt-in synthetic Investor accounts for the `local:wallet` hook. Local and testing only, on a
 * local PostgreSQL database; requires Composer's development autoloader, like the C2 pack.
 */
class SyntheticWalletSeeder extends Seeder
{
    public const string PASSWORD = 'Rozine-S3B-local-only-42!';

    public function run(): void
    {
        $this->assertLocal();
    }

    public function assertLocal(): void
    {
        app(SyntheticWalletGuard::class)->assertAllowed();
        $isolation = app(EnvironmentIsolation::class);
        $isolation->assertSeedingAllowed();
        $config = config()->array('database.connections.'.config()->string('database.default'));
        if (($config['driver'] ?? null) !== 'pgsql' || ! in_array($config['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || ! in_array($config['database'] ?? null, ['rozine', 'rozine_test', 'rozine_manual'], true)
            || ($isolation->profile() === 'testing' && $config['database'] !== 'rozine_test')
            || ! empty($config['url']) || isset($config['read']) || isset($config['write'])) {
            throw new LogicException('WALLET_LOCAL_DATABASE_REQUIRED: use a local PostgreSQL database named rozine, rozine_test or rozine_manual.');
        }
    }

    /** The synthetic Investor for this email, created with the investor role active if absent. */
    public function investor(string $email): User
    {
        $this->assertLocal();
        $user = User::query()->where('email', $email)->first();

        return $user === null ? InvestorWalletFixture::investor($email, self::PASSWORD)['user'] : $this->withParty($user);
    }

    public function existing(string $email): User
    {
        $this->assertLocal();

        return $this->withParty(User::query()->where('email', $email)->first()
            ?? throw new LogicException('WALLET_SYNTHETIC_ACCOUNT_NOT_FOUND: seed it first with --seed.'));
    }

    private function withParty(User $user): User
    {
        return $user->party_id === null ? throw new LogicException('WALLET_SYNTHETIC_ACCOUNT_NOT_INVESTOR: that account has no Party.') : $user;
    }
}
