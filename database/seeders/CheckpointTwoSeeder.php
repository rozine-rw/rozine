<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Environment\EnvironmentIsolation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use JsonException;
use LogicException;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\AuditSealingFixture;
use Tests\Support\AuditSourceFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;

/**
 * Opt-in development data. Reuses the acceptance scenarios and their real
 * application actions; requires Composer's development autoloader.
 *
 * @phpstan-type Account array{id: int, label: string, email: string, mfa: bool}
 * @phpstan-type Scenario array{business: string, application: string, assignment: string, report: string|null, business_path: string, auditor_path: string, operations_path: string, report_path: string|null}
 * @phpstan-type Pack array{version: int, created_at: string, accounts: list<Account>, scenarios: array<string, Scenario>}
 */
class CheckpointTwoSeeder extends Seeder
{
    public const string PASSWORD = 'Rozine-C2-local-only-42!';

    public const string DIRECTORY = 'manual-tests/checkpoint-two';

    public const string MANIFEST = self::DIRECTORY.'/manifest.json';

    public function run(): void
    {
        $this->prepare();
    }

    public function assertLocal(): void
    {
        $isolation = app(EnvironmentIsolation::class);
        $isolation->assertSeedingAllowed();
        $config = config()->array('database.connections.'.config()->string('database.default'));
        if (! in_array($isolation->profile(), ['local', 'testing'], true)
            || ($config['driver'] ?? null) !== 'pgsql'
            || ! in_array($config['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || ! in_array($config['database'] ?? null, ['rozine', 'rozine_test'], true)
            || ($isolation->profile() === 'testing' && $config['database'] !== 'rozine_test')
            || ! empty($config['url']) || isset($config['read']) || isset($config['write'])
            || config('filesystems.disks.local.driver') !== 'local') {
            throw new LogicException('C2_LOCAL_DATABASE_REQUIRED');
        }
    }

    public function otp(string $alias): string
    {
        $this->assertLocal();
        $pack = $this->readManifest();
        foreach ($pack['accounts'] as $account) {
            if ($account['label'] === $alias && $account['mfa']) {
                $user = User::query()->findOrFail($account['id']);

                return (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
            }
        }

        throw new LogicException('C2_MFA_ACCOUNT_REQUIRED: use an Auditor or Operations alias from this pack.');
    }

    /** @return Pack */
    public function prepare(): array
    {
        $this->assertLocal();

        return DB::transaction(function (): array {
            DB::select('SELECT pg_advisory_xact_lock(962026, 2)');
            $disk = Storage::disk('local');
            if ($disk->exists(self::MANIFEST)) {
                $pack = $this->readManifest();
                $this->writeFiles($pack);

                return $pack;
            }
            if (User::query()->where('email', 'like', '%@c2.rozine.invalid')->exists()) {
                throw new LogicException('C2_RESERVED_ACCOUNT_COLLISION: restore the original manifest; existing accounts were not changed.');
            }
            if (DB::table('consent_releases')->exists() || DB::table('audit_engagement_releases')->exists()) {
                throw new LogicException('C2_EXISTING_RELEASES: use a separate empty local database; existing consent or engagement releases were not changed.');
            }

            $draft = BusinessQuoteFixture::make();
            $this->requireCode(ConsentFixture::record($draft['audit']['staff']), 'CONSENT_RELEASE_RECORDED');
            $review = BusinessQuoteFixture::ready(2, recordConsent: false);
            $this->requireCode(BusinessQuoteFixture::submit($review, BusinessQuoteFixture::acceptance($review)), 'APPLICATION_SIGNATURE_RECORDED');
            $seal = AuditSealingFixture::ready(recordConsent: false);
            $cosign = AuditSealingFixture::ready(recordConsent: false);
            $published = AuditSealingFixture::ready(recordConsent: false);

            $accounts = $scenarios = [];
            foreach (['draft' => $draft, 'review' => $review, 'seal' => $seal, 'cosign' => $cosign, 'published' => $published] as $name => $fixture) {
                $audit = $fixture['audit'];
                foreach ($audit['authority']['users'] as $index => $user) {
                    $alias = $index === 0 ? 'business-'.$name : 'signatory-'.$name;
                    $accounts[] = $this->account($user, $alias, false);
                }
                $accounts[] = $this->account($audit['partners'][0]['user'], 'auditor-'.$name, true);
                $staff = $this->account($audit['staff'], $name === 'draft' ? 'operations' : 'operations-'.$name, true);
                $accounts[] = $staff;
                $accounts[] = $this->account($audit['partners'][0]['staff'], 'compliance-'.$name, true);
                $business = $audit['business'];
                $application = $fixture['application']->id;
                $assignment = $fixture['assignment']->id;
                $report = isset($fixture['report']) ? $fixture['report']->id : null;
                $scenarios[$name] = ['business' => $business, 'application' => $application, 'assignment' => $assignment, 'report' => $report,
                    'business_path' => "/business/{$business}/applications/{$application}",
                    'auditor_path' => $report === null ? "/auditor/jobs/{$assignment}" : "/auditor/reports/{$report}",
                    'operations_path' => "/admin/audit-assignments/{$assignment}",
                    'report_path' => $report === null ? null : "/business/{$business}/audit-reports/{$report}"];
            }
            foreach ([$draft, $review] as $fixture) {
                $facts = AuditSourceFactsFixture::facts();
                $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
                $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];
                AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
            }
            foreach ([$cosign, $published] as $fixture) {
                $fixture['code'] = (new Google2FA)->getCurrentOtp(decrypt($fixture['user']->refresh()->two_factor_secret));
                $this->requireCode(AuditSealingFixture::seal($fixture), 'AUDIT_SEALED');
            }
            $this->requireCode(AuditSealingFixture::cosign($published), 'REPORT_PUBLISHED');
            $pack = ['version' => 1, 'created_at' => now('UTC')->toIso8601String(), 'accounts' => $accounts, 'scenarios' => $scenarios];
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
            $this->writeFiles($pack);

            return $pack;
        });
    }

    /** @param array<string, mixed> $receipt */
    private function requireCode(array $receipt, string $expected): void
    {
        if (($receipt['code'] ?? null) !== $expected) {
            throw new LogicException('C2_SCENARIO_FAILED: '.($receipt['code'] ?? 'missing receipt'));
        }
    }

    /** @return Account */
    private function account(User $user, string $alias, bool $mfa): array
    {
        $email = $alias.'@c2.rozine.invalid';
        $user->forceFill(['name' => 'C2 Synthetic '.$alias, 'email' => $email, 'password' => self::PASSWORD,
            'two_factor_secret' => $mfa ? encrypt((new Google2FA)->generateSecretKey()) : null,
            'two_factor_recovery_codes' => $mfa ? encrypt(json_encode(['c2-local-recovery-'.$alias], JSON_THROW_ON_ERROR)) : null,
            'two_factor_confirmed_at' => $mfa ? now()->subMinute() : null])->save();

        return ['id' => $user->id, 'label' => $alias, 'email' => $email, 'mfa' => $mfa];
    }

    /** @return Pack */
    private function readManifest(): array
    {
        try {
            $decoded = json_decode(Storage::disk('local')->get(self::MANIFEST) ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new LogicException('C2_MANIFEST_INVALID');
        }
        if (! is_array($decoded) || Validator::make($decoded, [
            'version' => ['required', 'integer', 'in:1'], 'created_at' => ['required', 'string'],
            'accounts' => ['required', 'array', 'size:21'], 'accounts.*.id' => ['required', 'integer'],
            'accounts.*.label' => ['required', 'string'], 'accounts.*.email' => ['required', 'string', 'ends_with:@c2.rozine.invalid'],
            'accounts.*.mfa' => ['required', 'boolean'], 'scenarios' => ['required', 'array', 'size:5'],
            'scenarios.*.business' => ['required', 'ulid'], 'scenarios.*.application' => ['required', 'ulid'],
            'scenarios.*.assignment' => ['required', 'ulid'], 'scenarios.*.report' => ['present', 'nullable', 'ulid'],
            'scenarios.*.business_path' => ['required', 'string'], 'scenarios.*.auditor_path' => ['required', 'string'],
            'scenarios.*.operations_path' => ['required', 'string'], 'scenarios.*.report_path' => ['present', 'nullable', 'string'],
        ])->fails()) {
            throw new LogicException('C2_MANIFEST_INVALID');
        }
        /** @var Pack $pack */
        $pack = $decoded;
        foreach ($pack['accounts'] as $account) {
            if (! User::query()->whereKey($account['id'])->where('email', $account['email'])->exists()) {
                throw new LogicException('C2_MANIFEST_DATABASE_MISMATCH: no accounts were reset.');
            }
        }
        foreach ($pack['scenarios'] as $name => $scenario) {
            if (! DB::table('business_applications')->where('id', $scenario['application'])->where('business_id', $scenario['business'])->exists()
                || ! DB::table('audit_assignments')->where('id', $scenario['assignment'])->exists()
                || ($scenario['report'] !== null && ! DB::table('audit_reports')->where('id', $scenario['report'])->exists())) {
                throw new LogicException('C2_MANIFEST_DATABASE_MISMATCH: no progress was reset.');
            }
            if ($scenario['report'] === null) {
                $report = DB::table('audit_reports')->where('application_id', $scenario['application'])->whereNull('amends_id')->value('id');
                if (is_string($report)) {
                    $pack['scenarios'][$name]['report'] = $report;
                    $pack['scenarios'][$name]['auditor_path'] = '/auditor/reports/'.$report;
                    $pack['scenarios'][$name]['report_path'] = '/business/'.$scenario['business'].'/audit-reports/'.$report;
                }
            }
        }

        return $pack;
    }

    /** @param Pack $pack */
    private function writeFiles(array $pack): void
    {
        $disk = Storage::disk('local');
        $csv = "date,reference,amount\n";
        $first = CarbonImmutable::parse($pack['created_at'])->setTimezone('Africa/Kigali')->startOfMonth()->subMonths(36);
        for ($index = 0; $index < 36; $index++) {
            $day = $first->addMonths($index)->format('Y-m-d');
            $csv .= "{$day},sales,4000000\n{$day},costs,-1000000\n";
        }
        $guide = "# Local checkpoint 2 test pack\n\nSynthetic data only. Created ".$pack['created_at'].". Reruns preserve progress.\n\n";
        $guide .= '[Log in]('.url('/login').') — password: `'.self::PASSWORD."`\n\n";
        $guide .= "Auditor and staff MFA: `php artisan local:checkpoint-two --otp=ACCOUNT-ALIAS`. Use a fresh code for sealing after login; a code can only be used once.\n\n";
        foreach ($pack['scenarios'] as $name => $scenario) {
            $guide .= '## '.ucfirst($name)."\n\nBusiness: `business-{$name}@c2.rozine.invalid`; Auditor: `auditor-{$name}@c2.rozine.invalid`.\n\n";
            $guide .= '[Application]('.url($scenario['business_path']).') · [Auditor job/report]('.url($scenario['auditor_path']).') · [Operations case JSON]('.url($scenario['operations_path']).")\n\n";
            if ($scenario['report_path'] !== null) {
                $guide .= '[Business report and co-sign]('.url($scenario['report_path']).') · [Verify seal]('.url('/audit-seals/'.$scenario['report']).")\n\n";
            }
        }
        $guide .= "Review needs the second account `signatory-review@c2.rozine.invalid`. Operations: `operations@c2.rozine.invalid`.\n\n";
        $guide .= "See `docs/Checkpoint_Two_Manual_Testing.md` for the ordered walkthrough. Sample statement: `synthetic-statement.csv` in this directory.\n";
        if (! $disk->put(self::DIRECTORY.'/synthetic-statement.csv', $csv)
            || ! $disk->put(self::DIRECTORY.'/START-HERE.md', $guide)
            || ! $disk->put(self::MANIFEST, json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n")) {
            throw new LogicException('C2_FILES_WRITE_FAILED: database changes rolled back.');
        }
    }
}
