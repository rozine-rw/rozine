<?php

declare(strict_types=1);

use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\MembershipTransitions;

it('permits the explicit membership lifecycle and increments its revision', function (?string $before, string $after): void {
    $memberships = $before === null ? [] : [['role' => 'investor', 'status' => $before, 'revision' => 3]];
    expect((new MembershipTransitions)->nextRevision('investor', $after, $before === null ? 0 : 3, $memberships))
        ->toBe($before === null ? 1 : 4);
})->with([
    [null, 'pending'], [null, 'active'], ['pending', 'active'], ['pending', 'revoked'],
    ['active', 'suspended'], ['suspended', 'active'], ['suspended', 'revoked'], ['revoked', 'revoked'],
]);

it('rejects invalid lifecycle transitions', function (?string $before, string $after): void {
    $memberships = $before === null ? [] : [['role' => 'investor', 'status' => $before, 'revision' => 1]];
    expect(fn () => (new MembershipTransitions)->nextRevision('investor', $after, $before === null ? 0 : 1, $memberships))
        ->toThrow(IdentityViolation::class, 'MEMBERSHIP_TRANSITION_DENIED');
})->with([[null, 'suspended'], [null, 'revoked'], ['pending', 'suspended'], ['active', 'pending'], ['revoked', 'active']]);

it('rejects unknown membership values and stale state', function (string $role, string $status, int $revision, string $code): void {
    expect(fn () => (new MembershipTransitions)->nextRevision($role, $status, $revision, []))->toThrow(IdentityViolation::class, $code);
})->with([
    ['admin', 'active', 0, 'ROLE_MEMBERSHIP_INVALID'], ['investor', 'approved', 0, 'ROLE_MEMBERSHIP_INVALID'],
    ['investor', 'active', 1, 'MEMBERSHIP_REVISION_CONFLICT'],
]);

it('reserves exclusivity for retained memberships and allows only revoked roles to release it', function (string $status): void {
    $memberships = [['role' => 'auditor', 'status' => $status, 'revision' => 1]];
    if ($status === 'revoked') {
        expect((new MembershipTransitions)->nextRevision('business', 'pending', 0, $memberships))->toBe(1);
    } else {
        expect(fn () => (new MembershipTransitions)->nextRevision('business', 'pending', 0, $memberships))
            ->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_CONFLICT');
    }
})->with(['pending', 'active', 'suspended', 'revoked']);
