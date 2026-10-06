<?php

declare(strict_types=1);

use App\Domain\Identity\ActiveRolePolicy;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\RoleAccess;

it('does not trust a selected membership belonging to another Party snapshot', function (): void {
    $policy = new ActiveRolePolicy(new RoleAccess);
    $snapshot = [
        'email_verified' => true, 'mfa_confirmed' => true, 'party' => ['id' => 'party-one', 'kind' => 'person', 'verified' => true],
        'memberships' => [['id' => 'member-one', 'role' => 'investor', 'status' => 'active', 'revision' => 1]],
        'active_membership_id' => 'member-two', 'active_membership_revision' => 1, 'context_revision' => 3,
    ];
    expect($policy->activeRole($snapshot))->toBeNull();
    expect(fn () => $policy->authorize($snapshot, 'investor', 'party-one', 3))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REQUIRED');
    expect(fn () => $policy->authorize($snapshot, 'investor', 'party-two', 3))->toThrow(IdentityViolation::class, 'IDENTITY_RECORD_NOT_FOUND');
});
