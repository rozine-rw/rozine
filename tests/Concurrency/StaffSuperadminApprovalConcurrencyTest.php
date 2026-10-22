<?php

declare(strict_types=1);

use App\Application\Identity\ApproveStaffAccess;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GrantStaffAccess;
use App\Application\Identity\StaffAccessRefused;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\StaffFixture;

function concurrentAdmin(): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, ['superadmin'], 'Synthetic administrator.');

    return $user;
}

it('retains both manager authority locks until the superadmin grant and audit commit', function (string $who): void {
    $requester = concurrentAdmin();
    $approver = concurrentAdmin();
    $recipient = User::factory()->withTwoFactor()->create();
    $proposal = app(GrantStaffAccess::class)->handle($requester->id, $recipient->email, 'superadmin', 'Request.', (string) Str::uuid());
    $manager = $who === 'requester' ? $requester : $approver;
    $default = DB::getDefaultConnection();
    config(['database.connections.staff_approval_contender' => config('database.connections.pgsql')]);
    DB::connection('staff_approval_contender')->statement("SET lock_timeout = '100ms'");
    $blocked = false;
    $event = 'eloquent.creating: '.StaffAccount::class;
    Event::listen($event, function () use ($manager, $default, &$blocked): void {
        DB::setDefaultConnection('staff_approval_contender');
        try {
            app(ConfigureStaffAccess::class)->handle($manager->id, false, 'Concurrent revocation.', (string) Str::uuid());
            $this->fail('Revocation must wait for the grant.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
            $blocked = true;
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('staff_approval_contender');
        }
    });
    try {
        app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'Approve.', (string) Str::uuid());
    } finally {
        Event::forget($event);
    }
    expect($blocked)->toBeTrue()->and(StaffAccount::query()->findOrFail($recipient->id)->enabled)->toBeTrue();
    app(ConfigureStaffAccess::class)->handle($manager->id, false, 'Revocation after commit.', (string) Str::uuid());
    expect(StaffAccount::query()->findOrFail($manager->id)->enabled)->toBeFalse();
})->with(['requester', 'approver']);

it('serializes competing approvals for the same recipient and completes only once', function (): void {
    $requester = concurrentAdmin();
    $approver = concurrentAdmin();
    $other = concurrentAdmin();
    $recipient = User::factory()->withTwoFactor()->create();
    $proposal = app(GrantStaffAccess::class)->handle($requester->id, $recipient->email, 'superadmin', 'Request.', (string) Str::uuid());
    $default = DB::getDefaultConnection();
    config(['database.connections.staff_approval_contender' => config('database.connections.pgsql')]);
    DB::connection('staff_approval_contender')->statement("SET lock_timeout = '100ms'");
    $blocked = false;
    $event = 'eloquent.creating: '.StaffAccount::class;
    Event::listen($event, function () use ($other, $proposal, $default, &$blocked): void {
        DB::setDefaultConnection('staff_approval_contender');
        try {
            app(ApproveStaffAccess::class)->handle($other->id, $proposal['proposal_id'], 'Competing approval.', (string) Str::uuid());
            $this->fail('A competing approval must wait for the recipient lock.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
            $blocked = true;
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('staff_approval_contender');
        }
    });
    try {
        app(ApproveStaffAccess::class)->handle($approver->id, $proposal['proposal_id'], 'First approval.', (string) Str::uuid());
    } finally {
        Event::forget($event);
    }
    expect($blocked)->toBeTrue();
    expect(fn () => app(ApproveStaffAccess::class)->handle($other->id, $proposal['proposal_id'], 'Late approval.', (string) Str::uuid()))
        ->toThrow(StaffAccessRefused::class, 'STAFF_APPROVAL_COMPLETED');
    expect(DB::table('identity_audit_events')->where('after->proposal_id', $proposal['proposal_id'])->count())->toBe(1);
});
