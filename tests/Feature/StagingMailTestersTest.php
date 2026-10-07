<?php

declare(strict_types=1);

use App\Application\Environment\ManageStagingMailTesters;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\StaffPermission;
use App\Models\CommandOperation;
use App\Models\Party;
use App\Models\StagingMailTester;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    // Outside `testing` the framework checks CSRF tokens again; these tests post as the browser would.
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn (): string => 'staging');
    config(['isolation.staging_mail.recipients' => ['@rozine.rw']]);
    $this->superadmin = testerStaff(['superadmin'], 'Grace Mukamana');
});

afterEach(function (): void {
    app()->detectEnvironment(fn (): string => 'testing');
});

/** @param  list<string>  $roles */
function testerStaff(array $roles, string $name = 'Staff Member'): User
{
    $user = User::factory()->withTwoFactor()->create(['name' => $name]);
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Staging mail tester assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/**
 * @param  array<string, string>  $fields
 * @return array<string, string>
 */
function testerChange(array $fields = []): array
{
    return ['request_id' => (string) Str::uuid(), 'reason' => 'Joining the staging test round.', ...$fields];
}

test('only superadmin holds the staging mail tester permission', function () {
    expect(StaffPermission::forRoles(['superadmin']))->toContain('staging.mail.testers.manage');

    foreach (['analyst', 'approver', 'treasury', 'compliance'] as $role) {
        expect(StaffPermission::forRoles([$role]))->not->toContain('staging.mail.testers.manage');
    }
});

test('a superadmin adds and removes a named tester with a reason the journal keeps', function () {
    $this->actingAs($this->superadmin)->get(route('staff.staging-mail-testers.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/staging-mail-testers')
            ->where('contract_version', 'staff-staging-mail-testers-v1')
            ->where('server_recipients', ['@rozine.rw'])->where('testers', [])
            ->where('viewer.role', 'superadmin')->where('nav.mail_testers.url', route('staff.staging-mail-testers.index', [], false))
            ->where('nav.investors.url', route('staff.investor-verifications.index', [], false))
            ->where('add', ['url' => route('staff.staging-mail-testers.store', [], false), 'method' => 'post']));

    $this->post(route('staff.staging-mail-testers.store'), testerChange(['email' => ' Tester@Example.org ']))
        ->assertRedirect(route('staff.staging-mail-testers.index'));

    $tester = StagingMailTester::query()->sole();
    expect($tester->email)->toBe('tester@example.org')
        ->and($tester->added_by_user_id)->toBe($this->superadmin->id);

    $added = CommandOperation::query()->where('command', 'staging.mail.tester.add')->sole();
    expect($added->actor_user_id)->toBe($this->superadmin->id)
        ->and($added->target_id)->toBe('tester@example.org')
        ->and($added->result['code'])->toBe('STAGING_MAIL_TESTER_ADDED')
        ->and($added->result['data']['reason'])->toBe('Joining the staging test round.');

    $this->get(route('staff.staging-mail-testers.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('testers', 1)
            ->where('testers.0.email', 'tester@example.org')->where('testers.0.added_by', 'Grace Mukamana')
            ->where('testers.0.remove', ['url' => route('staff.staging-mail-testers.remove', ['tester' => $tester->id], false), 'method' => 'post']));

    $this->post(route('staff.staging-mail-testers.remove', ['tester' => $tester->id]), testerChange(['reason' => 'Test round finished.']))
        ->assertRedirect(route('staff.staging-mail-testers.index'));

    $removed = CommandOperation::query()->where('command', 'staging.mail.tester.remove')->sole();
    expect(StagingMailTester::query()->count())->toBe(0)
        ->and($removed->result['data'])->toMatchArray(['email' => 'tester@example.org', 'reason' => 'Test round finished.']);
});

test('the page search narrows the named testers by address', function () {
    StagingMailTester::factory()->create(['email' => 'aline@example.org', 'added_by_user_id' => $this->superadmin->id]);
    StagingMailTester::factory()->create(['email' => 'robert@example.net', 'added_by_user_id' => $this->superadmin->id]);

    $this->actingAs($this->superadmin)->get(route('staff.staging-mail-testers.index', ['q' => ' ROBERT ']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'ROBERT')->has('testers', 1)->where('testers.0.email', 'robert@example.net'));
});

test('a repeated request is replayed rather than applied twice', function () {
    $change = testerChange(['email' => 'tester@example.org']);

    $this->actingAs($this->superadmin)->post(route('staff.staging-mail-testers.store'), $change)->assertRedirect();
    $this->post(route('staff.staging-mail-testers.store'), $change)->assertRedirect(route('staff.staging-mail-testers.index'));

    expect(StagingMailTester::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'staging.mail.tester.add')->count())->toBe(1);
});

test('refused changes come back as form errors and change nothing', function (Closure $arrange, Closure $change, string $field, string $message) {
    $tester = StagingMailTester::factory()->create(['email' => 'tester@example.org', 'added_by_user_id' => $this->superadmin->id]);
    $arrange($tester);

    [$route, $parameters, $fields] = $change($tester);
    $this->actingAs($this->superadmin)->from(route('staff.staging-mail-testers.index'))->post(route($route, $parameters), $fields)
        ->assertRedirect(route('staff.staging-mail-testers.index'))->assertSessionHasErrors([$field => $message]);
})->with([
    'an address already approved' => [fn () => null, fn () => ['staff.staging-mail-testers.store', [], testerChange(['email' => 'TESTER@example.org'])],
        'form', 'This address is already an approved tester.'],
    'a tester already removed' => [fn (StagingMailTester $tester) => $tester->delete(), fn (StagingMailTester $tester) => ['staff.staging-mail-testers.remove', ['tester' => $tester->id], testerChange()],
        'form', 'This tester was already removed.'],
    'not an address' => [fn () => null, fn () => ['staff.staging-mail-testers.store', [], testerChange(['email' => 'tester'])],
        'email', 'Enter one email address.'],
    'no reason' => [fn () => null, fn () => ['staff.staging-mail-testers.store', [], testerChange(['email' => 'new@example.org', 'reason' => ''])],
        'reason', 'Give the reason for this change.'],
    'a request reused for other details' => [function (StagingMailTester $tester): void {
        app(ManageStagingMailTesters::class)->add($tester->added_by_user_id, 'first@example.org', 'Joining the staging test round.', '6f1c4b1e-7d9a-4c51-9b1e-2f6a8d3c4b5a');
    }, fn () => ['staff.staging-mail-testers.store', [], testerChange(['email' => 'second@example.org', 'request_id' => '6f1c4b1e-7d9a-4c51-9b1e-2f6a8d3c4b5a'])],
        'form', 'That request was already used for different details. Try again.'],
]);

test('the store itself refuses a blank or overlong reason and a malformed address', function (string $email, string $reason, string $code, string $field) {
    $result = app(ManageStagingMailTesters::class)->add($this->superadmin->id, $email, $reason, (string) Str::uuid());

    expect($result['status'])->toBe('rejected')
        ->and($result['code'])->toBe($code)
        ->and($result['field_errors'])->toHaveKey($field)
        ->and(StagingMailTester::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'staging.mail.tester.add')->count())->toBe(0);
})->with([
    'a blank reason' => ['tester@example.org', '   ', 'CHANGE_REASON_REQUIRED', 'reason'],
    'an overlong reason' => ['tester@example.org', str_repeat('r', 1001), 'CHANGE_REASON_REQUIRED', 'reason'],
    'not an address' => ['tester', 'Joining the test round.', 'TESTER_EMAIL_INVALID', 'email'],
]);

test('only a superadmin may open or change the tester list', function (Closure $actor, string $code) {
    $tester = StagingMailTester::factory()->create(['added_by_user_id' => $this->superadmin->id]);
    $this->actingAs($actor());

    $this->get(route('staff.staging-mail-testers.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', $code));
    $this->post(route('staff.staging-mail-testers.store'), testerChange(['email' => 'new@example.org']))->assertForbidden();
    $this->post(route('staff.staging-mail-testers.remove', ['tester' => $tester->id]), testerChange())->assertForbidden();

    expect(StagingMailTester::query()->pluck('id')->all())->toBe([$tester->id]);
})->with([
    'compliance' => [fn () => testerStaff(['compliance']), 'STAFF_PERMISSION_REQUIRED'],
    'approver' => [fn () => testerStaff(['approver']), 'STAFF_PERMISSION_REQUIRED'],
    'a participant' => [fn () => User::factory()->create(['party_id' => Party::factory()]), 'STAFF_ACCESS_REQUIRED'],
]);

test('a malformed change is refused by the staging and permission boundary before it is validated', function (string $environment, string $role, int $status) {
    app()->detectEnvironment(fn (): string => $environment);
    $tester = StagingMailTester::factory()->create(['added_by_user_id' => $this->superadmin->id]);
    $this->actingAs(testerStaff([$role]));

    $this->post(route('staff.staging-mail-testers.store'), ['email' => 'not-an-address'])
        ->assertStatus($status)->assertSessionHasNoErrors();
    $this->post(route('staff.staging-mail-testers.remove', ['tester' => $tester->id]), [])
        ->assertStatus($status)->assertSessionHasNoErrors();
})->with([
    'compliance on staging' => ['staging', 'compliance', 403],
    'superadmin outside staging' => ['testing', 'superadmin', 404],
]);

test('an address the server list already approves is not added as a named tester', function (string $email) {
    $this->actingAs($this->superadmin)->from(route('staff.staging-mail-testers.index'))
        ->post(route('staff.staging-mail-testers.store'), testerChange(['email' => $email]))
        ->assertRedirect(route('staff.staging-mail-testers.index'))
        ->assertSessionHasErrors(['email' => 'This address already receives staging mail through the server list, so it is not added here.']);

    expect(StagingMailTester::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'staging.mail.tester.add')->count())->toBe(0);
})->with(['alice@rozine.rw', ' Alice@ROZINE.rw ']);

test('a lookalike or subdomain of a server domain may still be named', function (string $email) {
    $this->actingAs($this->superadmin)->post(route('staff.staging-mail-testers.store'), testerChange(['email' => $email]))
        ->assertRedirect(route('staff.staging-mail-testers.index'))->assertSessionHasNoErrors();

    expect(StagingMailTester::query()->pluck('email')->all())->toBe([$email]);
})->with(['someone@evilrozine.rw', 'someone@mail.rozine.rw']);

test('the tester list does not exist outside staging', function (string $environment) {
    app()->detectEnvironment(fn (): string => $environment);
    $tester = StagingMailTester::factory()->create(['added_by_user_id' => $this->superadmin->id]);
    $this->actingAs($this->superadmin);

    $this->get(route('staff.staging-mail-testers.index'))->assertNotFound();
    $this->post(route('staff.staging-mail-testers.store'), testerChange(['email' => 'new@example.org']))->assertNotFound();
    $this->post(route('staff.staging-mail-testers.remove', ['tester' => $tester->id]), testerChange())->assertNotFound();

    expect(StagingMailTester::query()->count())->toBe(1);
})->with(['testing', 'local']);

test('the staff home links to the tester list only for a superadmin on staging', function (string $environment, string $role, bool $linked) {
    app()->detectEnvironment(fn (): string => $environment);

    $this->actingAs(testerStaff([$role]))->get(route('admin.home'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('identity/staff-home')
            ->where('staging_mail_testers', $linked ? ['url' => route('staff.staging-mail-testers.index', [], false), 'method' => 'get'] : null));
})->with([
    'superadmin on staging' => ['staging', 'superadmin', true],
    'compliance on staging' => ['staging', 'compliance', false],
    'superadmin elsewhere' => ['testing', 'superadmin', false],
]);
