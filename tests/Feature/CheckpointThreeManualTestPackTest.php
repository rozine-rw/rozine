<?php

declare(strict_types=1);

use App\Models\AuditPartnerCredit;
use App\Models\PrimaryHolding;
use App\Models\User;
use Database\Seeders\CheckpointThreeSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\ConsentFixture;

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
