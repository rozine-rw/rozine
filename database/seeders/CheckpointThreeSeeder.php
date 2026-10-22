<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use JsonException;
use LogicException;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditSealingFixture;
use Tests\Support\ConsentFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\PrimaryReservationFixture;
use Throwable;

/**
 * Opt-in development data for the screens the Checkpoint 2 pack cannot reach: a raise listed to
 * Investors with one purchase in it, a raise funded and paid out (an issued note, its Holdings, its
 * rating and the Audit Partner's platform fee share) and a monthly report awaiting its co-signature
 * inside the 24-hour review window. Every Investor has a verified email. Reuses the acceptance
 * scenarios and their real application actions, under the same local and staging guard as the
 * Checkpoint 2 pack; requires Composer's development autoloader.
 *
 * @phpstan-type Account array{id: int, label: string, email: string, mfa: bool}
 * @phpstan-type Scenario array{business: string, campaign: string|null, report: string|null, business_path: string}
 * @phpstan-type Pack array{version: int, created_at: string, accounts: list<Account>, scenarios: array<string, Scenario>}
 */
class CheckpointThreeSeeder extends Seeder
{
    public const string PASSWORD = CheckpointTwoSeeder::PASSWORD;

    public const string DIRECTORY = 'manual-tests/checkpoint-three';

    public const string MANIFEST = self::DIRECTORY.'/manifest.json';

    public const string PENDING = self::DIRECTORY.'/pending.json';

    private const string DOMAIN = '@c3.rozine.invalid';

    public function __construct(private CheckpointTwoSeeder $guard) {}

    public function run(): void
    {
        $this->prepare();
    }

    public function otp(string $alias): string
    {
        $this->guard->assertLocal();
        foreach ($this->readManifest()['accounts'] as $account) {
            if ($account['label'] === $alias && $account['mfa']) {
                return (new Google2FA)->getCurrentOtp(decrypt(User::query()->findOrFail($account['id'])->two_factor_secret));
            }
        }

        throw new LogicException('C3_MFA_ACCOUNT_REQUIRED: use an Auditor alias from this pack.');
    }

    /** @return Pack */
    public function prepare(): array
    {
        $this->guard->assertLocal();

        DB::select('SELECT pg_advisory_lock(962026, 3)');
        try {
            return $this->stage();
        } finally {
            DB::select('SELECT pg_advisory_unlock(962026, 3)');
        }
    }

    /**
     * Stages the pack in two transactions around the payout. A funded raise is paid out after the
     * transaction that opens its disbursement commits, so its closing cannot be observed inside one
     * transaction with the rest. A pending checkpoint names every committed scenario, so retries
     * resume that campaign without creating purchases or paying an issued closing again.
     *
     * @return Pack
     */
    private function stage(): array
    {
        $disk = Storage::disk('local');
        if ($disk->exists(self::PENDING) && ! User::query()->where('email', 'like', '%'.self::DOMAIN)->exists()) {
            // The checkpoint was written before an initial transaction that rolled back.
            if (! $disk->delete(self::PENDING)) {
                throw new LogicException('C3_PENDING_DELETE_FAILED: no C3 accounts exist; remove the pending file and retry.');
            }
        }
        if ($disk->exists(self::PENDING)) {
            $pack = $this->readManifest(self::PENDING);
        } elseif ($disk->exists(self::MANIFEST)) {
            $pack = $this->readManifest();
            $this->writeGuide($pack);

            return $pack;
        } else {
            $pack = $this->stageScenarios();
        }

        $campaign = $pack['scenarios']['funded']['campaign'];
        try {
            PrimaryIssuanceFixture::finishFunded((string) $campaign, $pack['created_at']);
        } catch (Throwable $exception) {
            throw new LogicException('C3_PAYOUT_FAILED: progress is saved; rerun to resume the same campaign. '.$exception->getMessage(), 0, $exception);
        }
        $this->writeGuide($pack);
        $disk->delete(self::PENDING);

        return $pack;
    }

    /** @return Pack */
    private function stageScenarios(): array
    {
        return DB::transaction(function (): array {
            if (User::query()->where('email', 'like', '%'.self::DOMAIN)->exists()) {
                throw new LogicException('C3_RESERVED_ACCOUNT_COLLISION: restore the original manifest; existing accounts were not changed.');
            }
            $makeInvestor = InvestorWalletFixture::packInvestorFactory();
            if (! DB::table('consent_releases')->exists()) {
                $this->requireCode(ConsentFixture::record(ConsentFixture::staff()), 'CONSENT_RELEASE_RECORDED');
            }
            $accounts = [];
            $live = $this->listed('live', $accounts);
            PrimaryHoldingFixture::committed(units: ['540'], fund: false, live: $live, makeInvestor: $makeInvestor);
            $accounts[] = $this->account($this->investors($live)[0], 'investor-live', false);
            $funded = $this->listed('funded', $accounts);

            $monthly = AuditSealingFixture::ready(kind: 'monthly', recordConsent: false);
            $accounts[] = $this->account($monthly['audit']['authority']['users'][0], 'business-monthly', false);
            $accounts[] = $this->account($monthly['user'], 'auditor-monthly', true);
            $monthly['code'] = (new Google2FA)->getCurrentOtp(decrypt($monthly['user']->refresh()->two_factor_secret));
            $this->requireCode(AuditSealingFixture::seal($monthly), 'AUDIT_SEALED');

            PrimaryHoldingFixture::committed(requoted: [0], live: $funded, makeInvestor: $makeInvestor);
            foreach ($this->investors($funded) as $index => $investor) {
                $accounts[] = $this->account($investor, 'investor-funded-'.($index + 1), false);
            }
            $scenarios = [];
            foreach (['live' => $live, 'funded' => $funded] as $name => $campaign) {
                $business = (string) DB::table('business_campaigns')->where('id', $campaign)->value('business_id');
                $scenarios[$name] = ['business' => $business, 'campaign' => $campaign, 'report' => null,
                    'business_path' => route('business.show', ['business' => $business], false)];
            }
            $business = $monthly['audit']['business'];
            $report = $monthly['report']->id;
            $scenarios['monthly'] = ['business' => $business, 'campaign' => null, 'report' => $report,
                'business_path' => route('business.audit-reports.show', ['business' => $business, 'report' => $report], false)];
            $pack = ['version' => 1, 'created_at' => now('UTC')->startOfSecond()->toIso8601String(), 'accounts' => $accounts, 'scenarios' => $scenarios];
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
            $this->writePack(self::PENDING, $pack);

            return $pack;
        });
    }

    /**
     * A raise sealed, co-signed, released and listed by its owner, with the owner's and Audit
     * Partner's logins added to the pack. Returns the campaign's ID.
     *
     * @param  list<Account>  $accounts
     */
    private function listed(string $name, array &$accounts): string
    {
        return PrimaryReservationFixture::campaign(false, function (array $fixture) use ($name, &$accounts): string {
            $accounts[] = $this->account($fixture['audit']['authority']['users'][0], 'business-'.$name, false);
            $accounts[] = $this->account($fixture['user'], 'auditor-'.$name, true);

            return (new Google2FA)->getCurrentOtp(decrypt($fixture['user']->refresh()->two_factor_secret));
        })->id;
    }

    /**
     * The Investors who bought into a campaign, in the order they confirmed.
     *
     * @return list<User>
     */
    private function investors(string $campaignId): array
    {
        $ids = DB::table('primary_commitments')->join('primary_reservations', 'primary_reservations.id', '=', 'primary_commitments.primary_reservation_id')
            ->join('users', 'users.party_id', '=', 'primary_reservations.party_id')->where('primary_reservations.business_campaign_id', $campaignId)
            ->orderBy('primary_commitments.id')->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->all();

        return array_values(array_map(fn (int $id): User => User::query()->findOrFail($id), $ids));
    }

    /** @param array<string, mixed> $receipt */
    private function requireCode(array $receipt, string $expected): void
    {
        if (($receipt['code'] ?? null) !== $expected) {
            throw new LogicException('C3_SCENARIO_FAILED: '.($receipt['code'] ?? 'missing receipt'));
        }
    }

    /** @return Account */
    private function account(User $user, string $alias, bool $mfa): array
    {
        $email = $alias.self::DOMAIN;
        $user->forceFill(['name' => 'C3 Synthetic '.$alias, 'email' => $email, 'password' => self::PASSWORD, 'email_verified_at' => now(),
            'two_factor_secret' => $mfa ? encrypt((new Google2FA)->generateSecretKey()) : null,
            'two_factor_recovery_codes' => $mfa ? encrypt('[]') : null,
            'two_factor_confirmed_at' => $mfa ? now()->subMinute() : null])->save();

        return ['id' => $user->id, 'label' => $alias, 'email' => $email, 'mfa' => $mfa];
    }

    /** @return Pack */
    private function readManifest(string $path = self::MANIFEST): array
    {
        try {
            $decoded = json_decode(Storage::disk('local')->get($path) ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new LogicException('C3_MANIFEST_INVALID');
        }
        if (! is_array($decoded) || Validator::make($decoded, [
            'version' => ['required', 'integer', 'in:1'], 'created_at' => ['required', 'string'],
            'accounts' => ['required', 'array', 'size:9'], 'accounts.*.id' => ['required', 'integer', 'distinct'],
            'accounts.*.label' => ['required', 'string', 'distinct'], 'accounts.*.email' => ['required', 'string', 'ends_with:'.self::DOMAIN],
            'accounts.*.mfa' => ['required', 'boolean'], 'scenarios' => ['required', 'array', 'size:3'],
            'scenarios.*.business' => ['required', 'ulid'], 'scenarios.*.campaign' => ['present', 'nullable', 'ulid'],
            'scenarios.*.report' => ['present', 'nullable', 'ulid'], 'scenarios.*.business_path' => ['required', 'string'],
        ])->fails()) {
            throw new LogicException('C3_MANIFEST_INVALID');
        }
        /** @var Pack $pack */
        $pack = $decoded;
        foreach ($pack['accounts'] as $account) {
            if (! User::query()->whereKey($account['id'])->where('email', $account['email'])->exists()) {
                throw new LogicException('C3_MANIFEST_DATABASE_MISMATCH: no accounts were reset.');
            }
        }

        foreach (['live', 'funded'] as $name) {
            $scenario = $pack['scenarios'][$name] ?? null;
            $owner = array_find($pack['accounts'], fn (array $account): bool => $account['label'] === 'business-'.$name);
            if ($scenario === null || $owner === null || ! DB::table('business_campaigns')->where('id', $scenario['campaign'])
                ->where('business_id', $scenario['business'])->where('actor_user_id', $owner['id'])->exists()) {
                throw new LogicException('C3_MANIFEST_DATABASE_MISMATCH: no accounts were reset.');
            }
        }

        return $pack;
    }

    /** @param Pack $pack */
    private function writePack(string $path, array $pack): void
    {
        if (! Storage::disk('local')->put($path, json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n")) {
            throw new LogicException('C3_FILES_WRITE_FAILED: rerun to resume any saved progress.');
        }
    }

    /** @param Pack $pack */
    private function writeGuide(array $pack): void
    {
        $guide = "# Checkpoint 3 test pack\n\nSynthetic data only. Created ".$pack['created_at'].". Reruns preserve progress.\n\n";
        $guide .= '[Log in]('.route('login').') — password: `'.self::PASSWORD."`\n\n";
        $guide .= "Auditor MFA: `php artisan local:checkpoint-three --otp=ACCOUNT-ALIAS`.\n\n";
        $guide .= '- **Live raise:** `business-live'.self::DOMAIN.'` lists a raise; `investor-live'.self::DOMAIN.'` holds one purchase in it. ['
            .'Business home]('.url($pack['scenarios']['live']['business_path']).")\n";
        $guide .= '- **Funded and paid out:** `business-funded'.self::DOMAIN.'` has an issued note; `investor-funded-1` and `investor-funded-2` hold it; `auditor-funded`'
            .' sees the platform fee share on Portfolio and Profile. [Business home]('.url($pack['scenarios']['funded']['business_path']).")\n";
        $guide .= '- **Monthly report:** `business-monthly'.self::DOMAIN.'` co-signs or disputes it inside the 24-hour window. [Report]('
            .url($pack['scenarios']['monthly']['business_path']).")\n";
        if (! Storage::disk('local')->put(self::DIRECTORY.'/START-HERE.md', $guide)) {
            throw new LogicException('C3_FILES_WRITE_FAILED: rerun to resume any saved progress.');
        }
        $this->writePack(self::MANIFEST, $pack);
    }
}
