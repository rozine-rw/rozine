<?php

declare(strict_types=1);

use App\Application\Disbursement\RecordPayoutEvent;
use App\Models\AuditPartnerCredit;
use App\Models\DepositPolicy;
use App\Models\DisbursementIntent;
use App\Models\PrimaryHolding;
use App\Models\User;
use Database\Seeders\CheckpointThreeSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ConsentFixture;
use Tests\Support\InvestorWalletFixture;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();
});

it('stages a listed raise, a paid-out note and a monthly report in its window, and preserves them on repeat', function (): void {
    $existing = User::factory()->create();
    $pack = app(CheckpointThreeSeeder::class)->prepare();

    expect(array_column($pack['accounts'], 'label'))->toBe(['business-live', 'auditor-live', 'investor-live', 'business-funded', 'auditor-funded',
        'business-monthly', 'auditor-monthly', 'investor-funded-1', 'investor-funded-2']);
    $this->assertDatabaseHas('users', ['id' => $existing->id, 'email' => $existing->email]);
    foreach ($pack['accounts'] as $account) {
        $user = User::query()->findOrFail($account['id']);
        expect(Hash::check(CheckpointThreeSeeder::PASSWORD, $user->password))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->two_factor_secret !== null)->toBe($account['mfa']);
    }
    expect(DB::table('primary_commitments')->join('primary_reservations', 'primary_reservations.id', '=', 'primary_commitments.primary_reservation_id')
        ->where('primary_reservations.business_campaign_id', $pack['scenarios']['live']['campaign'])->count())->toBe(1)
        ->and(PrimaryHolding::query()->count())->toBe(2)
        ->and(AuditPartnerCredit::query()->sole()->auditor_party_id)
        ->toBe(User::query()->where('email', 'auditor-funded@c3.rozine.invalid')->value('party_id'));
    $this->assertDatabaseHas('audit_report_publications', ['audit_report_id' => $pack['scenarios']['monthly']['report'], 'status' => 'pending']);

    $owner = User::query()->where('email', 'business-funded@c3.rozine.invalid')->sole();
    $this->actingAs($owner)->get($pack['scenarios']['funded']['business_path'])->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/home')->whereNot('rating', null));
    $monthly = User::query()->where('email', 'business-monthly@c3.rozine.invalid')->sole();
    $this->actingAs($monthly)->get($pack['scenarios']['monthly']['business_path'])->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/audit-cosign')->where('allowed_actions', ['report.cosign', 'report.dispute']));

    $holder = User::query()->where('email', 'investor-funded-1@c3.rozine.invalid')->sole();
    $this->actingAs($holder)->get(route('investor.portfolio'))->assertOk();
    $this->actingAs(User::query()->where('email', 'investor-live@c3.rozine.invalid')->sole())
        ->get(route('investor.deals.show', ['campaign' => $pack['scenarios']['live']['campaign']]))->assertOk();

    $operations = DB::table('command_operations')->count();
    expect(app(CheckpointThreeSeeder::class)->prepare())->toBe($pack)
        ->and(DB::table('command_operations')->count())->toBe($operations);
    Storage::disk('local')->assertExists(CheckpointThreeSeeder::DIRECTORY.'/START-HERE.md');
    expect(Artisan::call('local:checkpoint-three'))->toBe(0)
        ->and(Artisan::call('local:checkpoint-three', ['--otp' => 'auditor-funded']))->toBe(0)
        ->and(trim(Artisan::output()))->toMatch('/^[0-9]{6}$/')
        ->and(Artisan::call('local:checkpoint-three', ['--otp' => 'investor-live']))->toBe(1);
});

it('refuses a reserved login collision without changing any accounts', function (): void {
    $existing = User::factory()->create(['email' => 'business-live@c3.rozine.invalid']);
    expect(Artisan::call('local:checkpoint-three'))->toBe(1)
        ->and(User::query()->count())->toBe(1)->and($existing->refresh()->name)->not->toStartWith('C3 Synthetic');
    Storage::disk('local')->assertMissing(CheckpointThreeSeeder::MANIFEST);
});

it('builds on a consent release already recorded, as on staging after the Checkpoint 2 pack', function (): void {
    ConsentFixture::record(ConsentFixture::staff());
    app(CheckpointThreeSeeder::class)->run();

    $this->assertDatabaseCount('consent_releases', 1);
    Storage::disk('local')->assertExists(CheckpointThreeSeeder::MANIFEST);
});

it('refuses a stale manifest instead of reseeding over existing progress', function (): void {
    Storage::disk('local')->put(CheckpointThreeSeeder::MANIFEST, json_encode(['version' => 1, 'accounts' => [], 'scenarios' => []], JSON_THROW_ON_ERROR));
    expect(Artisan::call('local:checkpoint-three'))->toBe(1)
        ->and(Artisan::output())->toContain('C3_MANIFEST_INVALID');
    $this->assertDatabaseCount('users', 0);
});

it('stages the paid-out note on a local machine, where synthetic provider events carry the local profile', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $pack = app(CheckpointThreeSeeder::class)->prepare();

    expect(PrimaryHolding::query()->count())->toBe(2)
        ->and(DB::table('primary_commitments')->join('primary_reservations', 'primary_reservations.id', '=', 'primary_commitments.primary_reservation_id')
            ->where('primary_reservations.business_campaign_id', $pack['scenarios']['live']['campaign'])->count())->toBe(1);
});

it('preserves the existing deposit policy while funding pack buyers within its fees and limits', function (): void {
    $policy = InvestorWalletFixture::policy(fee: '500', minimum: '1000', maximum: '1000000');
    $original = $policy->fresh()->getAttributes();

    app(CheckpointThreeSeeder::class)->prepare();

    expect(DepositPolicy::query()->count())->toBe(1)->and($policy->fresh()->getAttributes())->toBe($original)
        ->and(DB::table('wallet_deposit_intents')->max('amount'))->toBeLessThanOrEqual(1000000)
        ->and(DB::table('wallet_deposit_intents')->where('fee', '!=', '500')->count())->toBe(0)
        ->and(PrimaryHolding::query()->count())->toBe(2);
});

it('refuses a withdrawn current deposit policy without changing it or creating scenarios', function (): void {
    $policy = DepositPolicy::factory()->withdrawn()->create();
    $original = $policy->fresh()->getAttributes();

    expect(fn () => app(CheckpointThreeSeeder::class)->prepare())->toThrow(LogicException::class, 'C3_DEPOSIT_POLICY_UNAVAILABLE')
        ->and(DepositPolicy::query()->count())->toBe(1)->and($policy->fresh()->getAttributes())->toBe($original)
        ->and(User::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing(CheckpointThreeSeeder::PENDING);
});

/** @return FilesystemAdapter the real fake disk, for restoring after the injected write */
function c3FailWrite(string $path, bool $afterWrite = false): FilesystemAdapter
{
    $disk = Storage::disk('local');
    $proxy = new class($disk->getDriver(), $disk->getAdapter(), $disk->getConfig()) extends FilesystemAdapter
    {
        public string $failedPath;

        public bool $afterWrite;

        public function put($path, $contents, $options = []): bool
        {
            if ($path !== $this->failedPath) {
                return parent::put($path, $contents, $options) !== false;
            }
            if ($this->afterWrite) {
                parent::put($path, $contents, $options);
                throw new RuntimeException('Synthetic transaction interruption after checkpoint write.');
            }

            return false;
        }
    };
    $proxy->failedPath = $path;
    $proxy->afterWrite = $afterWrite;
    Storage::set('local', $proxy);

    return $disk;
}

it('rolls back scenarios when the pending checkpoint cannot be written', function (): void {
    $disk = c3FailWrite(CheckpointThreeSeeder::PENDING);
    expect(fn () => app(CheckpointThreeSeeder::class)->prepare())->toThrow(LogicException::class, 'C3_FILES_WRITE_FAILED')
        ->and(User::query()->count())->toBe(0);
    Storage::set('local', $disk);

    expect(app(CheckpointThreeSeeder::class)->prepare()['accounts'])->toHaveCount(9);
});

it('discards a checkpoint left by a rolled-back initial transaction and safely retries', function (): void {
    $disk = c3FailWrite(CheckpointThreeSeeder::PENDING, afterWrite: true);
    expect(fn () => app(CheckpointThreeSeeder::class)->prepare())->toThrow(RuntimeException::class, 'Synthetic transaction interruption')
        ->and(User::query()->count())->toBe(0);
    Storage::set('local', $disk);
    $disk->assertExists(CheckpointThreeSeeder::PENDING);

    expect(app(CheckpointThreeSeeder::class)->prepare()['accounts'])->toHaveCount(9)
        ->and(PrimaryHolding::query()->count())->toBe(2)->and(DisbursementIntent::query()->count())->toBe(1);
    $disk->assertMissing(CheckpointThreeSeeder::PENDING);
});

it('resumes an interrupted payout using the same purchases and intent', function (): void {
    $real = app(RecordPayoutEvent::class);
    $interrupted = Mockery::mock($real, ['handle' => ['disposition' => 'deferred', 'decision' => 'unknown']]);
    app()->instance(RecordPayoutEvent::class, $interrupted);

    expect(fn () => app(CheckpointThreeSeeder::class)->prepare())->toThrow(LogicException::class, 'C3_PAYOUT_FAILED');
    $intent = DisbursementIntent::query()->sole()->id;
    $purchases = DB::table('primary_commitments')->orderBy('id')->pluck('id')->all();
    $dispatches = DB::table('disbursement_dispatches')->count();
    app()->forgetInstance(RecordPayoutEvent::class);

    $pack = app(CheckpointThreeSeeder::class)->prepare();

    expect($pack['accounts'])->toHaveCount(9)->and(DisbursementIntent::query()->sole()->id)->toBe($intent)
        ->and(DB::table('primary_commitments')->orderBy('id')->pluck('id')->all())->toBe($purchases)
        ->and(DB::table('disbursement_dispatches')->count())->toBe($dispatches)
        ->and(PrimaryHolding::query()->count())->toBe(2);
});

it('finishes the files after an issued payout without creating another intent or provider effect', function (): void {
    $disk = c3FailWrite(CheckpointThreeSeeder::MANIFEST);
    expect(fn () => app(CheckpointThreeSeeder::class)->prepare())->toThrow(LogicException::class, 'C3_FILES_WRITE_FAILED');
    $intent = DisbursementIntent::query()->sole()->id;
    $counts = array_map(fn (string $table): int => DB::table($table)->count(), ['primary_commitments', 'disbursement_provider_calls', 'disbursement_provider_events']);
    Storage::set('local', $disk);

    app(CheckpointThreeSeeder::class)->prepare();

    expect(DisbursementIntent::query()->sole()->id)->toBe($intent)
        ->and(array_map(fn (string $table): int => DB::table($table)->count(), ['primary_commitments', 'disbursement_provider_calls', 'disbursement_provider_events']))->toBe($counts)
        ->and(PrimaryHolding::query()->count())->toBe(2);
    $disk->assertExists(CheckpointThreeSeeder::MANIFEST);
    $disk->assertMissing(CheckpointThreeSeeder::PENDING);
});
