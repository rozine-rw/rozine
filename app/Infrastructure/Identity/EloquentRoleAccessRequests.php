<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\Contracts\RoleAccessRequests;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\RoleAccess;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\RoleAccessRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The role access request adapter (#616). A request is journaled under the person's own Party and
 * written once; the Party's row is locked while it is checked, so two tabs never send the same role
 * twice. The permitted input keeps only a digest of the details, which are personal data.
 *
 * @phpstan-import-type StaffRow from RoleAccessRequests
 */
final class EloquentRoleAccessRequests implements RoleAccessRequests
{
    public function __construct(private IdentityRepository $identities, private RoleAccess $roles, private OperationJournal $journal) {}

    public function sent(int $userId): array
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');
        if (! is_string($partyId)) {
            return [];
        }
        $sent = [];
        foreach (RoleAccessRequest::query()->where('party_id', $partyId)->get() as $row) {
            /** @var 'business'|'auditor' $role */
            $role = $row->role;
            $sent[$role] = $row->created_at->toIso8601String();
        }

        return $sent;
    }

    public function request(int $userId, string $requestId, string $role, array $details): array
    {
        try {
            $partyId = $this->requestableParty($userId, $role);

            return $this->journal->execute('party:'.$partyId, $userId, 'identity.access.request', $requestId, 'party', $partyId,
                ['role' => $role, 'details_sha256' => hash('sha256', (string) json_encode($details))],
                function () use ($userId, $role): void {
                    $this->requestableParty($userId, $role);
                },
                function (string $operationId) use ($partyId, $role, $details): OperationResult {
                    DB::table('parties')->where('id', $partyId)->lockForUpdate()->value('id');
                    if (RoleAccessRequest::query()->where('party_id', $partyId)->where('role', $role)->exists()) {
                        throw new CommandRejection('ACCESS_REQUEST_ALREADY_SENT');
                    }
                    $row = new RoleAccessRequest;
                    $row->forceFill(['party_id' => $partyId, 'role' => $role, ...$details,
                        'operation_id' => $operationId, 'created_at' => now('UTC')])->save();

                    return new OperationResult('ACCESS_REQUEST_SENT', ['role' => $role, 'sent_at' => $row->created_at->toIso8601String()], 1);
                });
        } catch (CommandRejection $rejection) {
            // Refused outside the journal's own record (no Party to journal under, or a request id
            // reused with other details), in the journal's shape.
            return ['status' => 'rejected', 'code' => $rejection->reason, 'http_status' => $rejection->status];
        }
    }

    public function forStaff(array $roles): array
    {
        /** @var list<StaffRow> $rows */
        $rows = array_values(RoleAccessRequest::query()->whereIn('role_access_requests.role', $roles)
            ->join('users', 'users.party_id', '=', 'role_access_requests.party_id')
            ->orderByDesc('role_access_requests.created_at')->orderByDesc('role_access_requests.id')
            ->get(['role_access_requests.*', 'users.name as requester_name', 'users.email as requester_email'])
            ->map(fn (RoleAccessRequest $row): array => ['id' => $row->id, 'role' => $row->role, 'name' => (string) $row->getAttribute('requester_name'),
                'email' => (string) $row->getAttribute('requester_email'), 'organization' => $row->organization, 'reference' => $row->reference,
                'phone' => $row->phone, 'note' => $row->note, 'sent_at' => $row->created_at->toIso8601String()])->all());

        return $rows;
    }

    /**
     * The person's own verified Party, when the role is one they may ask for; refused otherwise.
     *
     * @param  'business'|'auditor'  $role
     */
    private function requestableParty(int $userId, string $role): string
    {
        $identity = $this->identities->forUser($userId);
        $party = $identity['party'];
        $access = $this->roles->evaluate($identity['email_verified'], $party['kind'] ?? null, $party['verified'] ?? false, $identity['memberships']);
        if ($party === null || ! in_array($access['code'], ['IDENTITY_READY', 'ROLE_MEMBERSHIP_REQUIRED'], true)
            || ! in_array($role, $this->roles->requestable($identity['memberships']), true)) {
            throw new CommandRejection('ACCESS_REQUEST_UNAVAILABLE', 403);
        }

        return $party['id'];
    }
}
