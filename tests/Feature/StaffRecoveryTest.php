<?php

declare(strict_types=1);

use App\Application\Identity\ApproveStaffRecovery;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\RecordStaffPerson;
use App\Application\Identity\RequestStaffRecovery;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\PendingCommand;
use Tests\Support\StaffFixture;
use Tests\Support\StaffRecoveryFixture as Recovery;

/** @return array<string, mixed> */
function recoveryRequest(User $operator, User $first, User $second, string $kind = 'genesis', ?string $requestId = null): array
{
    return app(RequestStaffRecovery::class)->handle($operator->id, 'password', Recovery::code($operator), $kind,
        [$first->id, $second->id], 'Independently reviewed setup.', $requestId ?? (string) Str::uuid());
}

/** @param array<string, mixed> $proposal
 * @return array<string, mixed>
 */
function recoveryApprove(User $operator, array $proposal, ?string $requestId = null): array
{
    return app(ApproveStaffRecovery::class)->handle($operator->id, 'password', Recovery::code($operator),
        $proposal['proposal_id'], $proposal['payload_sha256'], 'Independent second approval.', $requestId ?? (string) Str::uuid(), $proposal['kind'], $proposal['recipients']);
}

test('fresh setup authenticates two independent operators and atomically establishes the manager pair', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    $before = [$a->getAttributes(), $b->getAttributes()];
    $proposal = recoveryRequest($first, $a, $b);
    expect($proposal['code'])->toBe('STAFF_RECOVERY_APPROVAL_REQUIRED')
        ->and(app(GetStaffAccess::class)->handle($a->id)['allowed_actions'])->not->toContain('staff.manage')
        ->and([$a->refresh()->getAttributes(), $b->refresh()->getAttributes()])->toBe($before);
    $result = recoveryApprove($second, $proposal);
    expect($result['code'])->toBe('STAFF_RECOVERY_COMPLETED');
    foreach ([$a, $b] as $recipient) {
        expect(app(GetStaffAccess::class)->handle($recipient->id)['allowed_actions'])->toContain('staff.manage')
            ->and(StaffAccount::query()->findOrFail($recipient->id)->roles)->toBe(['analyst', 'superadmin']);
    }
    expect(IdentityAuditEvent::query()->where('after->recovery_proposal_id', $proposal['proposal_id'])->count())->toBe(3)
        ->and(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->sole()->after['approver']['user_id'])->toBe($second->id)
        ->and(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->sole()->after['requester']['user_id'])->toBe($first->id);
    expect(fn () => DB::transaction(fn () => IdentityAuditEvent::query()->whereKey($proposal['proposal_id'])->update(['reason' => 'Changed'])))
        ->toThrow(QueryException::class, 'Identity audit events are immutable');
});

test('recovery is inactive without enrollment and console cannot bypass it', function (): void {
    [$first, , $a, $b] = Recovery::people();
    config(['staff-recovery.operators' => []]);
    expect(fn () => recoveryRequest($first, $a, $b))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_NOT_ENROLLED')
        ->and(fn () => app(ConfigureStaffAccess::class)->handle($a->id, true, 'Direct grant.', (string) Str::uuid(), ['superadmin']))
        ->toThrow(IdentityViolation::class, 'STAFF_SUPERADMIN_APPROVAL_REQUIRED');
    expect(Artisan::call('identity:staff-recovery', ['operation' => 'request', '--operator' => $first->id, '--no-interaction' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('STAFF_RECOVERY_INTERACTIVE_AUTHENTICATION_REQUIRED');
});

test('password and real Fortify authenticator proofs are required and failures are throttled', function (): void {
    [$first, , $a, $b] = Recovery::people();
    $request = app(RequestStaffRecovery::class);
    expect(fn () => $request->handle($first->id, 'wrong', Recovery::code($first), 'genesis', [$a->id, $b->id], 'Review.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_AUTHENTICATION_REQUIRED');
    foreach (range(1, 4) as $attempt) {
        expect(fn () => $request->handle($first->id, 'password', 'invalid', 'genesis', [$a->id, $b->id], 'Review.', (string) Str::uuid()))
            ->toThrow(CommandRejection::class, 'STEP_UP_CODE_INVALID');
    }
    expect(fn () => recoveryRequest($first, $a, $b))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_AUTHENTICATION_THROTTLED');
    expect(IdentityAuditEvent::query()->where('action', 'staff.recovery.request')->exists())->toBeFalse();
});

test('the requester cannot approve their own request', function (): void {
    [$first, , $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    $this->travel(1)->minutes();
    expect(fn () => recoveryApprove($first, $proposal))->toThrow(IdentityViolation::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
});

test('different accounts resolving to the same person cannot form the operator pair', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    app(RecordStaffPerson::class)->handle(IdentityOperator::query()->sole()->user_id, $second->id, 'nid:synthetic-recovery-1',
        'test:alias', 'Same verified person.', (string) Str::uuid());
    config(['staff-recovery.operators.'.$second->id => hash('sha256', 'nid:synthetic-recovery-1')]);
    $proposal = recoveryRequest($first, $a, $b);
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
});

test('changed identity credentials configuration and recipient state invalidate the request', function (string $change): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    match ($change) {
        'password' => $first->forceFill(['password' => 'replacement-password'])->save(),
        'mfa' => $first->forceFill(['two_factor_confirmed_at' => null])->save(),
        'email' => $first->forceFill(['email_verified_at' => null])->save(),
        'recipient' => $a->forceFill(['context_revision' => $a->context_revision + 1])->save(),
        'config' => config(['staff-recovery.operators.'.$first->id => str_repeat('f', 64)]),
        'person' => app(RecordStaffPerson::class)->handle(IdentityOperator::query()->sole()->user_id, $first->id,
            null, 'test:revoked', 'Verification revoked.', (string) Str::uuid()),
        default => throw new LogicException('Unknown stale-state fixture.'),
    };
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class);
    expect(StaffAccount::query()->whereJsonContains('roles', 'superadmin')->count())->toBe(0);
})->with(['password', 'mfa', 'email', 'recipient', 'config', 'person']);

test('approval requires the reviewed digest and an unexpired proposal', function (string $failure): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    if ($failure === 'digest') {
        $proposal['payload_sha256'] = str_repeat('0', 64);
    } else {
        $this->travel(10)->minutes();
    }
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class,
        $failure === 'digest' ? 'STAFF_RECOVERY_PAYLOAD_MISMATCH' : 'STAFF_RECOVERY_EXPIRED');
})->with(['digest', 'expiry']);

test('UUID replay is idempotent while changed payloads and another completion are refused', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    $id = (string) Str::uuid();
    $proposal = recoveryRequest($first, $a, $b, requestId: $id);
    $this->travel(1)->minutes();
    expect(recoveryRequest($first, $a, $b, requestId: $id))->toEqual($proposal);
    $approvalId = (string) Str::uuid();
    $result = recoveryApprove($second, $proposal, $approvalId);
    $this->travel(1)->minutes();
    expect(recoveryApprove($second, $proposal, $approvalId))->toEqual($result);
    $this->travel(1)->minutes();
    expect(fn () => recoveryRequest($first, $a, $b, 'recovery', $id))->toThrow(IdentityViolation::class, 'IDEMPOTENCY_KEY_REUSED');
    $this->travel(1)->minutes();
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_COMPLETED');
    expect(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->count())->toBe(1);
});

test('one-manager recovery retains that manager and existing ordinary roles', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    StaffAccount::query()->whereKey($a->id)->update(['roles' => json_encode(['analyst', 'superadmin'])]);
    $proposal = recoveryRequest($first, $a, $b, 'recovery');
    recoveryApprove($second, $proposal);
    foreach ([$a, $b] as $user) {
        expect(app(GetStaffAccess::class)->handle($user->id)['allowed_actions'])->toContain('staff.manage');
    }
    expect(StaffAccount::query()->findOrFail($a->id)->roles)->toBe(['analyst', 'superadmin']);
});

test('history cannot reset after suspension and two effective managers close recovery', function (string $state): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    recoveryApprove($second, $proposal);
    $this->travel(1)->minutes();
    if ($state === 'disabled') {
        foreach ([$a, $b] as $user) {
            app(ConfigureStaffAccess::class)->handle($user->id, false, 'Suspended.', (string) Str::uuid(), ['superadmin']);
        }
    }
    expect(fn () => recoveryRequest($first, $a, $b, $state === 'disabled' ? 'genesis' : 'recovery'))
        ->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_NOT_REQUIRED');
    if ($state === 'disabled') {
        $this->travel(1)->minutes();
        $replacement = recoveryRequest($first, $a, $b, 'recovery');
        $this->travel(1)->minutes();
        expect(recoveryApprove($second, $replacement)['code'])->toBe('STAFF_RECOVERY_COMPLETED');
    }
})->with(['disabled', 'two']);

test('a second-grant failure rolls back both grants and permits an authenticated retry', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    $event = 'eloquent.saving: '.StaffAccount::class;
    Event::listen($event, function (StaffAccount $staff) use ($b): void {
        if ($staff->user_id === $b->id && in_array('superadmin', $staff->roles, true)) {
            throw new RuntimeException('Synthetic second-grant failure');
        }
    });
    $id = (string) Str::uuid();
    try {
        expect(fn () => recoveryApprove($second, $proposal, $id))->toThrow(RuntimeException::class, 'Synthetic second-grant failure');
    } finally {
        Event::forget($event);
    }
    expect(StaffAccount::query()->whereJsonContains('roles', 'superadmin')->count())->toBe(0)
        ->and(IdentityAuditEvent::query()->where('after->recovery_proposal_id', $proposal['proposal_id'])->count())->toBe(0);
    $this->travel(1)->minutes();
    expect(recoveryApprove($second, $proposal, $id)['code'])->toBe('STAFF_RECOVERY_COMPLETED');
});

test('a valid digest relayed with misleading recipient details or kind is refused', function (string $change): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = recoveryRequest($first, $a, $b);
    if ($change === 'kind') {
        $proposal['kind'] = 'recovery';
    } else {
        $proposal['recipients'] = [$first->id, $second->id];
    }
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_PAYLOAD_MISMATCH');
    expect(StaffAccount::query()->whereJsonContains('roles', 'superadmin')->count())->toBe(0);
})->with(['kind', 'recipients']);

test('verified-person aliases and an operator recipient cannot form a recipient pair', function (string $alias): void {
    [$first, , $a, $b] = Recovery::people();
    if ($alias !== 'operator-account') {
        app(RecordStaffPerson::class)->handle(IdentityOperator::query()->sole()->user_id, $a->id,
            $alias === 'operator-person' ? 'nid:synthetic-recovery-1' : 'nid:synthetic-recovery-4',
            'test:alias', 'Verified alias.', (string) Str::uuid());
    }
    expect(fn () => recoveryRequest($first, $alias === 'operator-account' ? $first : $a, $b))
        ->toThrow(IdentityViolation::class, 'STAFF_SELF_APPROVAL_FORBIDDEN');
})->with(['operator-account', 'operator-person', 'recipient-person']);

test('recipient eligibility is rechecked at both request and approval', function (string $stage, string $failure): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = $stage === 'approve' ? recoveryRequest($first, $a, $b) : null;
    match ($failure) {
        'email' => $a->forceFill(['email_verified_at' => now()->addDay()])->save(),
        'mfa' => $a->forceFill(['two_factor_confirmed_at' => null])->save(),
        'person' => app(RecordStaffPerson::class)->handle(IdentityOperator::query()->sole()->user_id, $a->id,
            null, 'test:revoked', 'Revoked recipient identity.', (string) Str::uuid()),
        default => throw new LogicException('Unknown eligibility fixture.'),
    };
    expect(fn () => $proposal === null ? recoveryRequest($first, $a, $b) : recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class);
    expect(StaffAccount::query()->whereJsonContains('roles', 'superadmin')->count())->toBe(0);
})->with(['request', 'approve'])->with(['email', 'mfa', 'person']);

test('a manager authority change between request and approval makes recovery stale', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    StaffAccount::query()->whereKey($a->id)->update(['roles' => json_encode(['superadmin'])]);
    $proposal = recoveryRequest($first, $a, $b, 'recovery');
    $a->forceFill(['two_factor_confirmed_at' => null])->save();
    expect(fn () => recoveryApprove($second, $proposal))->toThrow(IdentityViolation::class);
    expect(StaffAccount::query()->findOrFail($b->id)->roles)->toBe(['analyst']);
});

test('one-manager recovery must include the existing manager and cannot silently add a third', function (): void {
    [$first, , $a, $b] = Recovery::people();
    $existing = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($existing, ['superadmin'], 'Synthetic prior manager.');
    expect(fn () => recoveryRequest($first, $a, $b, 'recovery'))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_EXISTING_MANAGER_REQUIRED');
});

test('the interactive command uses real password MFA and exact semantic approval inputs', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    $request = $this->artisan('identity:staff-recovery', ['operation' => 'request', '--operator' => (string) $first->id,
        '--kind' => 'genesis', '--recipient' => [(string) $a->id, (string) $b->id], '--reason' => 'Interactive request.']);
    if (! $request instanceof PendingCommand) {
        throw new LogicException('Interactive command testing must be enabled.');
    }
    $request->expectsQuestion('Current account password', 'password')
        ->expectsQuestion('Current authenticator code', Recovery::code($first))->assertSuccessful();
    $request->run();
    $proposal = IdentityAuditEvent::query()->where('action', 'staff.recovery.request')->sole()->result;
    $approve = $this->artisan('identity:staff-recovery', ['operation' => 'approve', '--operator' => (string) $second->id,
        '--proposal' => $proposal['proposal_id'], '--payload-sha256' => $proposal['payload_sha256'],
        '--kind' => 'genesis', '--recipient' => [(string) $a->id, (string) $b->id], '--reason' => 'Interactive independent approval.']);
    if (! $approve instanceof PendingCommand) {
        throw new LogicException('Interactive command testing must be enabled.');
    }
    $approve->expectsQuestion('Current account password', 'password')->expectsQuestion('Current authenticator code', Recovery::code($second))
        ->expectsConfirmation('Approve the exact recipients and payload digest you independently reviewed?', 'yes')->assertSuccessful();
    $approve->run();
    expect(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->count())->toBe(1);
});
