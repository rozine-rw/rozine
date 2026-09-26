<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Application\Auditor\VerifyAuditReportSeal;
use App\Application\Identity\Contracts\ConsentCatalog;
use App\Console\Commands\PrepareCheckpointTwo;
use App\Models\User;
use Database\Seeders\CheckpointTwoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\ConsentFixture;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();
});

it('creates usable staged cases and preserves existing data and manual progress on repeat', function (): void {
    $existing = User::factory()->create();
    $pack = app(CheckpointTwoSeeder::class)->prepare();
    expect($pack['accounts'])->toHaveCount(21);
    $this->assertDatabaseHas('users', ['id' => $existing->id, 'email' => $existing->email]);
    $this->assertDatabaseCount('business_applications', 5);
    $this->assertDatabaseCount('audit_reports', 3);
    $this->assertDatabaseCount('audit_report_publications', 2);
    $this->assertDatabaseCount('audit_report_signatures', 1);
    $this->assertDatabaseHas('audit_report_publications', ['audit_report_id' => $pack['scenarios']['published']['report'], 'status' => 'published']);
    $this->assertDatabaseHas('audit_report_publications', ['audit_report_id' => $pack['scenarios']['cosign']['report'], 'status' => 'pending']);
    $secrets = [];
    foreach ($pack['accounts'] as $account) {
        $user = User::query()->findOrFail($account['id']);
        expect(Hash::check(CheckpointTwoSeeder::PASSWORD, $user->password))->toBeTrue();
        if ($account['mfa']) {
            $secrets[] = decrypt($user->two_factor_secret);
            expect(end($secrets))->toMatch('/^[A-Z2-7]{16,}$/');
            expect(json_decode(decrypt($user->two_factor_recovery_codes), true))->toBeEmpty();
        }
    }
    expect($secrets)->toHaveCount(15)->not->toContain('JBSWY3DPEHPK3PXP')
        ->and(array_unique($secrets))->toHaveCount(15);
    $auditor = User::query()->where('email', 'auditor-seal@c2.rozine.invalid')->firstOrFail();
    expect(app(GetAuditProcedure::class)->handle($auditor->id, 1, $pack['scenarios']['seal']['report'])['can_seal'])->toBeTrue();
    $owner = User::query()->where('email', 'business-cosign@c2.rozine.invalid')->firstOrFail();
    expect(app(GetBusinessAuditReport::class)->handle($owner->id, 1, $pack['scenarios']['cosign']['business'], $pack['scenarios']['cosign']['report'])['can_cosign'])->toBeTrue();
    expect(app(VerifyAuditReportSeal::class)->handle($pack['scenarios']['published']['report'])['seal_status'])->toBe('valid');
    $this->actingAs($owner)->get($pack['scenarios']['cosign']['report_path'])->assertOk();
    $owner->forceFill(['name' => 'Manually changed name'])->save();
    $operations = DB::table('command_operations')->count();
    expect(app(CheckpointTwoSeeder::class)->prepare())->toBe($pack)
        ->and($owner->refresh()->name)->toBe('Manually changed name')
        ->and(DB::table('command_operations')->count())->toBe($operations);
    Storage::disk('local')->assertExists(CheckpointTwoSeeder::DIRECTORY.'/synthetic-statement.csv');
    expect(Artisan::call('local:checkpoint-two'))->toBe(0);
    expect(Artisan::call('local:checkpoint-two', ['--otp' => 'auditor-seal']))->toBe(0)
        ->and(trim(Artisan::output()))->toMatch('/^[0-9]{6}$/');
    expect(Artisan::call('local:checkpoint-two', ['--otp' => 'someone-else']))->toBe(1);
});

it('refuses a reserved login collision without changing any accounts', function (): void {
    $existing = User::factory()->create(['email' => 'operations@c2.rozine.invalid']);
    expect(Artisan::call('local:checkpoint-two'))->toBe(1);
    expect(User::query()->count())->toBe(1)->and($existing->refresh()->name)->not->toStartWith('C2 Synthetic');
    Storage::disk('local')->assertMissing(CheckpointTwoSeeder::MANIFEST);
});

it('rejects nonlocal environments and database targets before writing', function (string $key, mixed $value): void {
    if ($key === 'environment') {
        app()->instance('env', $value);
    } else {
        config([$key => $value]);
    }
    expect(fn () => app(CheckpointTwoSeeder::class)->assertLocal())->toThrow(LogicException::class);
    Storage::disk('local')->assertMissing(CheckpointTwoSeeder::MANIFEST);
})->with([
    ['environment', 'production'],
    ['environment', 'uat'],
    ['database.connections.pgsql.host', 'database.example.com'],
    ['database.connections.pgsql.database', 'rozine_live'],
    ['database.connections.pgsql.database', 'rozine_manual'],
    ['database.connections.pgsql.url', 'postgresql://user:pass@example.com/rozine'],
]);

it('refuses a stale manifest instead of reseeding over existing progress', function (): void {
    Storage::disk('local')->put(CheckpointTwoSeeder::MANIFEST, json_encode(['version' => 1, 'accounts' => [], 'scenarios' => []], JSON_THROW_ON_ERROR));
    expect(Artisan::call('local:checkpoint-two'))->toBe(1);
    $this->assertDatabaseCount('users', 0);
});

it('rolls back the entire pack if a scenario action refuses its transition', function (): void {
    app()->instance(ConsentCatalog::class, new class implements ConsentCatalog
    {
        public function record(int $actorId, int $expectedRevision, string $status, array $documents, array $disclosures, bool $synthetic, string $approvalReference, string $reason, string $requestId): array
        {
            return ['code' => 'SYNTHETIC_CONSENT_DENIED'];
        }

        public function withCurrent(Closure $operation): mixed
        {
            return $operation(null);
        }
    });
    expect(Artisan::call('local:checkpoint-two'))->toBe(1);
    expect(Artisan::output())->toContain('C2_SCENARIO_FAILED: SYNTHETIC_CONSENT_DENIED');
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('business_applications', 0);
    $this->assertDatabaseCount('audit_assignments', 0);
    $this->assertDatabaseCount('command_operations', 0);
    Storage::disk('local')->assertMissing(CheckpointTwoSeeder::MANIFEST);
});

it('refuses existing consent releases without replacing them', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    expect(Artisan::call('local:checkpoint-two'))->toBe(1);
    $this->assertDatabaseCount('consent_releases', 1);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('business_applications', 0);
});

it('refuses existing engagement releases without replacing them', function (): void {
    $release = AuditEngagementFixture::release(ConsentFixture::staff());
    $before = $release->getRawOriginal();
    expect(Artisan::call('local:checkpoint-two'))->toBe(1);
    expect(Artisan::output())->toContain('C2_EXISTING_RELEASES');
    expect($release->refresh()->getRawOriginal())->toBe($before);
    $this->assertDatabaseCount('audit_engagement_releases', 1);
    $this->assertDatabaseCount('consent_releases', 0);
    $this->assertDatabaseCount('business_applications', 0);
    Storage::disk('local')->assertMissing(CheckpointTwoSeeder::MANIFEST);
});

it('permits the dedicated manual database only in local and hides the opt-in command', function (): void {
    app()->instance('env', 'local');
    config(['database.connections.pgsql.database' => 'rozine_manual']);
    app(CheckpointTwoSeeder::class)->assertLocal();
    expect(app(PrepareCheckpointTwo::class)->isHidden())->toBeTrue();
});
