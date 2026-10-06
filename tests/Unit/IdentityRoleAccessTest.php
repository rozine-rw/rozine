<?php

declare(strict_types=1);

use App\Domain\Identity\RoleAccess;

it('requires verified identity before making roles available', function (bool $emailVerified, ?string $kind, bool $partyVerified, string $code): void {
    expect((new RoleAccess)->evaluate($emailVerified, $kind, $partyVerified, [
        ['role' => 'investor', 'status' => 'active'],
    ]))->toBe(['code' => $code, 'available_roles' => []]);
})->with([
    'email pending' => [false, 'person', true, 'EMAIL_VERIFICATION_REQUIRED'],
    'legacy user without a Party' => [true, null, false, 'IDENTITY_NOT_LINKED'],
    'unverified Party' => [true, 'person', false, 'IDENTITY_VERIFICATION_REQUIRED'],
    'organization needs its effective mandate' => [true, 'organization', true, 'PARTY_AUTHORITY_REQUIRED'],
    'unknown Party type' => [true, 'unknown', true, 'PARTY_AUTHORITY_REQUIRED'],
]);

it('allows the approved Investor and Business combination in stable order', function (): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, [
        ['role' => 'business', 'status' => 'active'],
        ['role' => 'investor', 'status' => 'active'],
    ]))->toBe(['code' => 'IDENTITY_READY', 'available_roles' => ['investor', 'business']]);
});

it('permits only an active membership', function (string $status): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, [
        ['role' => 'investor', 'status' => $status],
    ]))->toBe(['code' => 'ROLE_MEMBERSHIP_REQUIRED', 'available_roles' => []]);
})->with(['pending', 'suspended', 'revoked']);

it('fails closed when an Auditor has another retained marketplace membership', function (string $role, string $status): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, [
        ['role' => 'auditor', 'status' => 'active'],
        ['role' => $role, 'status' => $status],
    ]))->toBe(['code' => 'ROLE_MEMBERSHIP_CONFLICT', 'available_roles' => []]);
})->with(['investor', 'business'])->with(['pending', 'active', 'suspended']);

it('allows an Auditor alone and does not reactivate a revoked role', function (): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, [
        ['role' => 'investor', 'status' => 'revoked'],
        ['role' => 'auditor', 'status' => 'active'],
    ]))->toBe(['code' => 'IDENTITY_READY', 'available_roles' => ['auditor']]);
});

it('denies unknown or privileged roles and malformed membership states', function (string $role, string $status): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, [
        ['role' => $role, 'status' => $status],
    ]))->toBe(['code' => 'ROLE_MEMBERSHIP_INVALID', 'available_roles' => []]);
})->with([
    ['admin', 'active'],
    ['compliance', 'active'],
    ['investor', 'unknown'],
]);

it('gives no role to a verified person without a membership', function (): void {
    expect((new RoleAccess)->evaluate(true, 'person', true, []))
        ->toBe(['code' => 'ROLE_MEMBERSHIP_REQUIRED', 'available_roles' => []]);
});
