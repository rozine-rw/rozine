<?php

declare(strict_types=1);

use App\Application\Identity\ApproveStaffRecovery;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\RequestStaffRecovery;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityAuditEvent;
use App\Models\StaffAccount;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\StaffRecoveryFixture as Recovery;

test('recovery retains all actor target and historical manager locks until atomic completion', function (string $who): void {
    [$first, $second, $a, $b] = Recovery::people();
    StaffAccount::query()->findOrFail($a->id)->syncRoles(['superadmin']);
    $proposal = app(RequestStaffRecovery::class)->handle($first->id, 'password', Recovery::code($first),
        'recovery', [$a->id, $b->id], 'Synthetic recovery.', (string) Str::uuid());
    $target = match ($who) {
        'requester' => $first, 'approver' => $second, 'existing-manager' => $a, default => $b
    };
    $default = DB::getDefaultConnection();
    config(['database.connections.recovery_contender' => config('database.connections.pgsql')]);
    DB::connection('recovery_contender')->statement("SET lock_timeout = '100ms'");
    $blocked = false;
    $event = 'eloquent.saving: '.StaffAccount::class;
    Event::listen($event, function () use ($target, $default, &$blocked): void {
        if ($blocked) {
            return;
        }
        DB::setDefaultConnection('recovery_contender');
        try {
            app(ConfigureStaffAccess::class)->handle($target->id, false, 'Concurrent withdrawal.', (string) Str::uuid());
            $this->fail('The recovery must retain account locks until completion.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
            $blocked = true;
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('recovery_contender');
        }
    });
    try {
        app(ApproveStaffRecovery::class)->handle($second->id, 'password', Recovery::code($second), $proposal['proposal_id'],
            $proposal['payload_sha256'], 'Independent review.', (string) Str::uuid(), $proposal['kind'], $proposal['recipients']);
    } finally {
        Event::forget($event);
    }
    expect($blocked)->toBeTrue()->and(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->count())->toBe(1);
})->with(['requester', 'approver', 'existing-manager', 'recipient']);

test('a competing recovery approval blocks and cannot duplicate genesis or completion', function (): void {
    [$first, $second, $a, $b] = Recovery::people();
    $proposal = app(RequestStaffRecovery::class)->handle($first->id, 'password', Recovery::code($first),
        'genesis', [$a->id, $b->id], 'Synthetic setup.', (string) Str::uuid());
    $default = DB::getDefaultConnection();
    config(['database.connections.recovery_contender' => config('database.connections.pgsql')]);
    DB::connection('recovery_contender')->statement("SET lock_timeout = '100ms'");
    $blocked = false;
    $event = 'eloquent.saving: '.StaffAccount::class;
    Event::listen($event, function () use ($second, $proposal, $default, &$blocked): void {
        if ($blocked) {
            return;
        }
        DB::setDefaultConnection('recovery_contender');
        try {
            app(ApproveStaffRecovery::class)->handle($second->id, 'password', Recovery::code($second), $proposal['proposal_id'],
                $proposal['payload_sha256'], 'Concurrent review.', (string) Str::uuid(), $proposal['kind'], $proposal['recipients']);
            $this->fail('Competing completion must block.');
        } catch (QueryException $exception) {
            expect($exception->getCode())->toBe('55P03');
            $blocked = true;
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('recovery_contender');
        }
    });
    try {
        app(ApproveStaffRecovery::class)->handle($second->id, 'password', Recovery::code($second), $proposal['proposal_id'],
            $proposal['payload_sha256'], 'Independent review.', (string) Str::uuid(), $proposal['kind'], $proposal['recipients']);
    } finally {
        Event::forget($event);
    }
    expect($blocked)->toBeTrue()->and(IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->count())->toBe(1);
    $this->travel(1)->minutes();
    expect(fn () => app(ApproveStaffRecovery::class)->handle($second->id, 'password', Recovery::code($second), $proposal['proposal_id'],
        $proposal['payload_sha256'], 'Late replay.', (string) Str::uuid(), $proposal['kind'], $proposal['recipients']))->toThrow(IdentityViolation::class, 'STAFF_RECOVERY_COMPLETED');
});
