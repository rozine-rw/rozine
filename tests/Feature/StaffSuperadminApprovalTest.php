<?php

declare(strict_types=1);

use App\Application\Identity\ApproveStaffAccess;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\GrantStaffAccess;
use App\Application\Identity\SetStaffAccess;
use App\Application\Identity\StaffAccessRefused;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

function approvalManager(string $name = 'Manager'): User
{
    $user = User::factory()->withTwoFactor()->create(['name' => $name]);
    StaffFixture::provision($user, ['superadmin'], 'Synthetic existing administrator.');

    return $user;
}

/** @return array<string, mixed> */
function requestSuperadmin(User $requester, User $recipient, ?string $requestId = null): array
{
    return app(GrantStaffAccess::class)->handle($requester->id, $recipient->email, 'superadmin', 'New administrator.', $requestId ?? (string) Str::uuid());
}

test('superadmin requests preserve all recipient state and require a distinct current manager', function (): void {
    $requester = approvalManager('Requester');
    $approver = approvalManager('Approver');
    $recipient = User::factory()->withTwoFactor()->for(Party::factory())->create(['name' => 'Recipient']);
    $before = $recipient->refresh()->getAttributes();
    $key = (string) Str::uuid();
    $proposal = requestSuperadmin($requester, $recipient, $key);
    expect($proposal['code'])->toBe('STAFF_SUPERADMIN_APPROVAL_REQUIRED')
        ->and(requestSuperadmin($requester, $recipient, $key))->toBe($proposal)
        ->and($recipient->refresh()->getAttributes())->toBe($before)
        ->and(StaffAccount::query()->find($recipient->id))->toBeNull()
        ->and(app(GetStaffAccess::class)->handle($recipient->id)['can_open_admin'])->toBeFalse();
    expect(fn () => app(ApproveStaffAccess::class)->handle($requester->id, $proposal['proposal_id'], 'I approve.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
    expect(fn () => app(GrantStaffAccess::class)->handle($requester->id, $recipient->email, 'analyst', 'New administrator.', $key))
        ->toThrow(StaffAccessRefused::class, 'IDEMPOTENCY_KEY_REUSED');
    expect(fn () => app(SetStaffAccess::class)->handle($requester->id, $approver->id, false, 'New administrator.', $key))
        ->toThrow(StaffAccessRefused::class, 'IDEMPOTENCY_KEY_REUSED');
    expect(StaffAccount::query()->findOrFail($approver->id)->enabled)->toBeTrue();
    $approvalKey = (string) Str::uuid();
    $approve = fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Independent approval.', $approvalKey);
    $result = $approve();
    expect($approve())->toBe($result)->and($result['code'])->toBe('STAFF_ACCESS_ENABLED')
        ->and(StaffAccount::query()->findOrFail($recipient->id))->enabled->toBeTrue()->roles->toBe(['superadmin'])
        ->and($recipient->refresh()->party_id)->toBeNull()
        ->and(app(GetStaffAccess::class)->handle($recipient->id)['allowed_actions'])->toContain('staff.manage');
    expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Changed details.', $approvalKey))
        ->toThrow(StaffAccessRefused::class, 'IDEMPOTENCY_KEY_REUSED');
    expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Again.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, 'STAFF_APPROVAL_COMPLETED');
    $audit = IdentityAuditEvent::query()->where('after->proposal_id', $proposal['proposal_id'])->sole();
    expect($audit->actor_user_id)->toBe($approver->id)->and($audit->after['requested_by'])->toBe($requester->id)
        ->and($audit->after['request_reason'])->toBe('New administrator.')
        ->and($audit->after['approval_policy_version'])->toBe('staff-superadmin-approval-v1')
        ->and(IdentityAuditEvent::query()->whereKey($proposal['proposal_id'])->sole()->after['approval_policy_version'])->toBe('staff-superadmin-approval-v1');
    expect(fn () => DB::transaction(fn () => IdentityAuditEvent::query()->whereKey($proposal['proposal_id'])->update(['reason' => 'Changed payload'])))
        ->toThrow(QueryException::class, 'Identity audit events are immutable');
});

test('approval rechecks both managers current authority and effective MFA', function (string $who, string $failure): void {
    $requester = approvalManager();
    $approver = approvalManager();
    $recipient = User::factory()->withTwoFactor()->create();
    $proposal = requestSuperadmin($requester, $recipient);
    $manager = $who === 'requester' ? $requester : $approver;
    match ($failure) {
        'disabled' => app(ConfigureStaffAccess::class)->handle($manager->id, false, 'Withdraw authority.', (string) Str::uuid(), ['superadmin']),
        'role' => app(ConfigureStaffAccess::class)->handle($manager->id, true, 'Withdraw role.', (string) Str::uuid(), ['analyst']),
        'email' => $manager->forceFill(['email_verified_at' => now()->addDay()])->save(),
        'mfa' => $manager->forceFill(['two_factor_confirmed_at' => null])->save(),
        default => throw new LogicException('Unknown authority failure.'),
    };
    expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Review.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, $failure === 'role' ? 'STAFF_PERMISSION_REQUIRED' : 'STAFF_ACCESS_REQUIRED');
    expect(StaffAccount::query()->find($recipient->id))->toBeNull();
})->with(['requester', 'approver'])->with(['disabled', 'role', 'email', 'mfa']);

test('the recipient cannot approve and direct stores enforce the same policy', function (): void {
    $requester = approvalManager();
    $recipient = User::factory()->withTwoFactor()->create();
    $store = app(IdentityAccessStore::class);
    $proposal = $store->grantStaff($requester->id, $recipient->email, 'superadmin', 'Request.', (string) Str::uuid());
    expect(StaffAccount::query()->find($recipient->id))->toBeNull();
    StaffAccount::factory()->create(['user_id' => $recipient->id, 'roles' => ['superadmin']]);
    expect(fn () => $store->approveStaff($recipient->id, $proposal['proposal_id'], 'Self approve.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
    expect(fn () => $store->grantStaff($requester->id, $requester->email, 'superadmin', 'Self grant.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_SELF_CHANGE_FORBIDDEN');
    expect(fn () => $store->approveStaff($requester->id, (string) Str::ulid(), 'Unknown.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_APPROVAL_NOT_FOUND');
});

test('approval refuses stale recipient access and rechecks eligibility', function (string $failure): void {
    $requester = approvalManager();
    $approver = approvalManager();
    $party = Party::factory()->create();
    $recipient = User::factory()->withTwoFactor()->for($party)->create();
    $proposal = requestSuperadmin($requester, $recipient);
    match ($failure) {
        'staff' => app(ConfigureStaffAccess::class)->handle($recipient->id, true, 'Ordinary staff now.', (string) Str::uuid(), ['analyst']),
        'context' => $recipient->forceFill(['context_revision' => $recipient->context_revision + 1])->save(),
        'mfa' => $recipient->forceFill(['two_factor_secret' => null])->save(),
        'participant' => $party->forceFill(['verified_at' => now()])->save(),
        default => throw new LogicException('Unknown recipient failure.'),
    };
    expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Review.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, match ($failure) {
            'mfa' => 'STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED', 'participant' => 'DEDICATED_STAFF_ACCOUNT_REQUIRED', default => 'STAFF_APPROVAL_STALE',
        });
    expect(app(GetStaffAccess::class)->handle($recipient->id)['allowed_actions'])->not->toContain('staff.manage');
})->with(['staff', 'context', 'mfa', 'participant']);

test('reactivating a superadmin preserves roles and remains disabled until independent approval', function (): void {
    $requester = approvalManager();
    $approver = approvalManager();
    $recipient = approvalManager();
    $set = app(SetStaffAccess::class);
    $set->handle($requester->id, $recipient->id, false, 'Suspend.', (string) Str::uuid());
    $before = StaffAccount::query()->findOrFail($recipient->id)->getAttributes();
    $key = (string) Str::uuid();
    $proposal = $set->handle($requester->id, $recipient->id, true, 'Return.', $key);
    expect($set->handle($requester->id, $recipient->id, true, 'Return.', $key))->toBe($proposal)
        ->and(StaffAccount::query()->findOrFail($recipient->id)->getAttributes())->toBe($before);
    $older = $set->handle($requester->id, $recipient->id, true, 'Another request.', (string) Str::uuid());
    app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Independent return.', (string) Str::uuid());
    expect(StaffAccount::query()->findOrFail($recipient->id))->enabled->toBeTrue()->roles->toBe(['superadmin']);
    $set->handle($requester->id, $recipient->id, false, 'Suspend again.', (string) Str::uuid());
    expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $older['proposal_id'], 'Old request.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, 'STAFF_APPROVAL_STALE');
    expect(StaffAccount::query()->findOrFail($recipient->id)->enabled)->toBeFalse();
});

test('console cannot introduce or activate superadmin through single or mixed roles', function (bool $enabled, bool $mixed): void {
    $roles = $mixed ? ['analyst', 'superadmin'] : ['superadmin'];
    $recipient = User::factory()->withTwoFactor()->create();
    expect(fn () => app(ConfigureStaffAccess::class)->handle($recipient->id, $enabled, 'Console grant.', (string) Str::uuid(), $roles))
        ->toThrow(IdentityViolation::class, $enabled ? 'STAFF_SUPERADMIN_APPROVAL_REQUIRED' : 'STAFF_ACCOUNT_NOT_FOUND');
    app(ConfigureStaffAccess::class)->handle($recipient->id, true, 'Ordinary staff.', (string) Str::uuid(), ['analyst']);
    expect(fn () => app(ConfigureStaffAccess::class)->handle($recipient->id, $enabled, 'Console escalation.', (string) Str::uuid(), $roles))
        ->toThrow(IdentityViolation::class, 'STAFF_SUPERADMIN_APPROVAL_REQUIRED');
    expect(StaffAccount::query()->findOrFail($recipient->id)->roles)->toBe(['analyst']);
})->with([true, false])->with([false, true]);

test('console preserves current admins and revocation while refusing superadmin restoration', function (): void {
    $recipient = approvalManager();
    $configure = app(ConfigureStaffAccess::class);
    $configure->handle($recipient->id, true, 'Current admin unchanged.', (string) Str::uuid(), ['superadmin']);
    expect(StaffAccount::query()->findOrFail($recipient->id)->enabled)->toBeTrue();
    $recipient->forceFill(['two_factor_secret' => null])->save();
    $configure->handle($recipient->id, false, 'Withdraw without MFA.', (string) Str::uuid(), ['superadmin']);
    $recipient->forceFill(['two_factor_secret' => 'synthetic'])->save();
    expect(Artisan::call('identity:staff', ['user' => $recipient->id, '--role' => ['superadmin'], '--reason' => 'Shortcut.']))->toBe(1);
    expect(StaffAccount::query()->findOrFail($recipient->id)->enabled)->toBeFalse();
    $configure->handle($recipient->id, true, 'Return without privileged role.', (string) Str::uuid(), ['analyst']);
    expect(StaffAccount::query()->findOrFail($recipient->id))->enabled->toBeTrue()->roles->toBe(['analyst']);
});

test('audit failure rolls back the approved grant and leaves the immutable request pending', function (): void {
    $requester = approvalManager();
    $approver = approvalManager();
    $recipient = User::factory()->withTwoFactor()->create();
    $proposal = requestSuperadmin($requester, $recipient);
    $event = 'eloquent.creating: '.IdentityAuditEvent::class;
    Event::listen($event, function (IdentityAuditEvent $audit): void {
        if ($audit->action === 'staff.configure') {
            throw new RuntimeException('Audit unavailable.');
        }
    });
    try {
        expect(fn () => app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Review.', (string) Str::uuid()))
            ->toThrow(RuntimeException::class, 'Audit unavailable.');
    } finally {
        Event::forget($event);
    }
    expect(StaffAccount::query()->find($recipient->id))->toBeNull()
        ->and(IdentityAuditEvent::query()->where('after->proposal_id', $proposal['proposal_id'])->count())->toBe(0);
    app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Retry.', (string) Str::uuid());
    expect(StaffAccount::query()->findOrFail($recipient->id)->enabled)->toBeTrue();
});

test('web pending requests are reviewable and API projections keep approval links read only', function (): void {
    $requester = approvalManager('Requester');
    $approver = approvalManager('Approver');
    $recipient = User::factory()->withTwoFactor()->create(['name' => 'Recipient']);
    $payload = ['email' => $recipient->email, 'role' => 'superadmin', 'request_id' => (string) Str::uuid(), 'reason' => 'New admin.'];
    $this->actingAs($requester)->post(route('staff.staff.grant'), $payload)->assertRedirect(route('staff.staff.index'));
    $proposal = IdentityAuditEvent::query()->where('action', 'staff.superadmin.request')->sole();
    $this->get(route('staff.staff.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('pending_approvals.0.recipient', 'Recipient')->where('pending_approvals.0.approve', null));
    $this->actingAs($approver)->get(route('staff.staff.index'))->assertInertia(fn (Assert $page) => $page
        ->where('pending_approvals.0.approve.url', route('staff.staff.approve', $proposal->id, false)));
    $this->getJson(route('api.v1.staff.staff.index'))->assertOk()->assertJsonPath('data.pending_approvals.0.approve', null);
    $this->actingAs($requester)->post(route('staff.staff.approve', $proposal->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Self.'])
        ->assertSessionHasErrors('reason');
    $this->actingAs($approver)->post(route('staff.staff.approve', $proposal->id), ['request_id' => (string) Str::uuid(), 'reason' => 'Independent review.'])
        ->assertRedirect(route('staff.staff.index', ['operator' => $recipient->id]));
    $this->get(route('staff.staff.index'))->assertInertia(fn (Assert $page) => $page->where('pending_approvals', []));
    $ordinary = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($ordinary, ['analyst'], 'Ordinary staff.');
    $this->actingAs($ordinary)->get(route('staff.staff.index'))->assertInertia(fn (Assert $page) => $page->where('pending_approvals', []));
});
