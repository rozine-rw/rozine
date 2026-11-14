<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\Contracts\RoleAccessRequests;
use App\Domain\Identity\RoleAccess;

/**
 * `identity.access.request` (#616): a verified person asks Rozine's staff for Business or Auditor
 * access from the launcher, with what staff need to act on it. Granting the role stays with the
 * staff tools that do so (Onboard business, Auditor accreditation); this only records the ask.
 *
 * @phpstan-import-type Details from RoleAccessRequests
 */
final readonly class RequestRoleAccess
{
    public function __construct(private RoleAccessRequests $requests, private IdentityRepository $identities, private RoleAccess $roles) {}

    /**
     * @param  'business'|'auditor'  $role
     * @param  Details  $details
     * @return array<string, mixed>
     */
    public function handle(int $userId, string $requestId, string $role, array $details): array
    {
        return $this->requests->request($userId, $requestId, $role, $details);
    }

    /**
     * What the launcher shows for each role the person does not hold: the roles they may ask for,
     * when they already asked, and why the others cannot be asked for (#622).
     *
     * @return array{requestable: list<'business'|'auditor'>, sent: array<'business'|'auditor', string>,
     *     unavailable: array<'business'|'auditor', 'ROLE_ON_RECORD'|'AUDITOR_ONLY_ROLE'|'AUDITOR_ACCOUNT'>}
     */
    public function offer(int $userId): array
    {
        $identity = $this->identities->forUser($userId);
        $party = $identity['party'];
        $access = $this->roles->evaluate($identity['email_verified'], $party['kind'] ?? null, $party['verified'] ?? false, $identity['memberships']);
        $sent = $this->requests->sent($userId);
        $ready = in_array($access['code'], ['IDENTITY_READY', 'ROLE_MEMBERSHIP_REQUIRED'], true);
        $requestable = $ready
            ? array_values(array_filter($this->roles->requestable($identity['memberships']), fn (string $role): bool => ! isset($sent[$role])))
            : [];

        return ['requestable' => $requestable, 'sent' => $sent,
            'unavailable' => $ready ? array_diff_key($this->roles->unavailableReasons($identity['memberships']), $sent) : []];
    }
}
