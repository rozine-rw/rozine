<?php

declare(strict_types=1);

namespace App\Domain\Identity;

final class MembershipTransitions
{
    /**
     * @param  list<array{role: string, status: string, revision: int}>  $memberships
     */
    public function nextRevision(string $role, string $status, int $expectedRevision, array $memberships): int
    {
        if (! in_array($role, ['investor', 'business', 'auditor'], true)
            || ! in_array($status, ['pending', 'active', 'suspended', 'revoked'], true)) {
            throw new IdentityViolation('ROLE_MEMBERSHIP_INVALID', 422);
        }

        $current = null;

        foreach ($memberships as $membership) {
            if ($membership['role'] === $role) {
                $current = $membership;
            }
        }

        if ($expectedRevision !== ($current['revision'] ?? 0)) {
            throw new IdentityViolation('MEMBERSHIP_REVISION_CONFLICT', 409);
        }

        $allowed = match ($current['status'] ?? null) {
            null => ['pending', 'active'],
            'pending' => ['pending', 'active', 'revoked'],
            'active' => ['active', 'suspended', 'revoked'],
            'suspended' => ['suspended', 'active', 'revoked'],
            default => ['revoked'],
        };

        if (! in_array($status, $allowed, true)) {
            throw new IdentityViolation('MEMBERSHIP_TRANSITION_DENIED', 409);
        }

        foreach ($memberships as $membership) {
            if ($status !== 'revoked' && $membership['role'] !== $role && $membership['status'] !== 'revoked'
                && ($role === 'auditor' || $membership['role'] === 'auditor')) {
                throw new IdentityViolation('ROLE_MEMBERSHIP_CONFLICT', 409);
            }
        }

        return ($current['revision'] ?? 0) + 1;
    }
}
