<?php

declare(strict_types=1);

use App\Application\Identity\RegisterIdentity;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\SandboxNotice;
use App\Models\SandboxNoticeAcknowledgement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function (): void {
    $this->skipUnlessFortifyHas(Features::registration());
});

/**
 * @param  array<string, string|null>  $overrides
 * @return array<string, string|null>
 */
function sandboxNoticeSignUp(array $overrides = []): array
{
    return [...['name' => 'Aline Uwase', 'email' => 'aline@example.test', 'password' => 'password', 'password_confirmation' => 'password',
        'sandbox_notice_version' => SandboxNotice::VERSION, 'sandbox_notice_sha256' => SandboxNotice::sha256()], ...$overrides];
}

it('gives the sign-up page the CMA sandbox notice word for word, with its version and digest (BRS KYC-R01)', function (): void {
    $this->get(route('register'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/register')
        ->where('sandboxNotice', [
            'version' => 'brs-2.0-kyc-r01',
            'text' => "Rozine is testing this service in the Capital Market Authority's Fintech Regulatory Sandbox, under limits set by the CMA. Admission to the sandbox is not a licence or an endorsement.",
            'sha256' => SandboxNotice::sha256(),
        ]));

    expect(SandboxNotice::sha256())->toMatch('/^[0-9a-f]{64}$/');
});

it('stores the acknowledgement with the notice version, the time and a digest when the person signs up', function (): void {
    $this->freezeTime();
    $this->post(route('register.store'), sandboxNoticeSignUp())->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'aline@example.test')->sole();
    $acknowledgement = SandboxNoticeAcknowledgement::query()->sole();
    $at = now('UTC')->toDateTimeImmutable();

    expect($acknowledgement->party_id)->toBe($user->party_id)
        ->and($acknowledgement->version)->toBe(SandboxNotice::VERSION)
        ->and($acknowledgement->notice_sha256)->toBe(SandboxNotice::sha256())
        ->and($acknowledgement->acknowledged_at->equalTo($at))->toBeTrue()
        ->and($acknowledgement->sha256)->toBe(hash('sha256', implode("\n", ['sandbox-notice-acknowledgement-1', $acknowledgement->id,
            $user->party_id, SandboxNotice::VERSION, SandboxNotice::sha256(), $at->format('Y-m-d\TH:i:s.u\Z')])));
});

it('refuses a sign-up that did not acknowledge the current notice, and creates nothing', function (array $notice): void {
    $this->post(route('register.store'), sandboxNoticeSignUp($notice))
        ->assertSessionHasErrors(array_keys($notice));

    expect(session('errors')->first())->toBe('Read and acknowledge the sandbox notice before you sign up.');
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('parties', 0);
    $this->assertDatabaseCount('sandbox_notice_acknowledgements', 0);
    $this->assertGuest();
})->with([
    'no version' => [['sandbox_notice_version' => null]],
    'an older version' => [['sandbox_notice_version' => 'brs-1.0-kyc-r01']],
    'no digest' => [['sandbox_notice_sha256' => '']],
    'another wording' => [['sandbox_notice_sha256' => str_repeat('a', 64)]],
]);

it('refuses to register anyone who did not acknowledge exactly the current notice', function (string $version, string $digest): void {
    expect(fn () => app(RegisterIdentity::class)->handle('Aline Uwase', 'aline@example.test', 'password', $version, $digest))
        ->toThrow(IdentityViolation::class, 'SANDBOX_NOTICE_REQUIRED');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('parties', 0);
})->with([
    'an older version' => ['brs-1.0-kyc-r01', SandboxNotice::sha256()],
    'another wording' => [SandboxNotice::VERSION, str_repeat('a', 64)],
]);

it('keeps every acknowledgement as it was recorded', function (): void {
    $this->post(route('register.store'), sandboxNoticeSignUp())->assertSessionHasNoErrors();

    // Each attempt runs in its own savepoint, so the refused write leaves the test's transaction usable.
    expect(fn () => DB::transaction(fn () => DB::table('sandbox_notice_acknowledgements')->update(['version' => 'changed'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('sandbox_notice_acknowledgements')->delete()))->toThrow(QueryException::class);
    expect(SandboxNoticeAcknowledgement::query()->sole()->version)->toBe(SandboxNotice::VERSION);
});

it('rolls the acknowledgement table back and forward', function (): void {
    $migration = require database_path('migrations/2026_10_25_120000_create_sandbox_notice_acknowledgements_table.php');

    $migration->down();
    expect(Schema::hasTable('sandbox_notice_acknowledgements'))->toBeFalse()
        ->and(DB::scalar("SELECT to_regprocedure('reject_sandbox_notice_acknowledgement_mutation()') IS NULL"))->toBeTrue();

    $migration->up();
    expect(Schema::hasTable('sandbox_notice_acknowledgements'))->toBeTrue()
        ->and(DB::scalar("SELECT to_regprocedure('reject_sandbox_notice_acknowledgement_mutation()') IS NULL"))->toBeFalse();
});
