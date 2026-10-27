<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\ResolveVerifiedOrganization;
use App\Domain\Business\MandateAuthority;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Pulse\PulseSector;

/**
 * Operator-assisted onboarding (#375, `businesses.verify`): a company is resolved from its RDB code
 * (`ResolveVerifiedOrganization`), then its profile and first mandate are recorded
 * (`ConfigureBusinessAuthority`) with its official representative holding every role and permission. A
 * sole trader is the representative. The representative must already be a verified person, and is looked
 * up only after the actor's permission is confirmed, so the form cannot be used to probe for accounts. The
 * mandate takes effect at the time the server offered the form, so a delayed retry replays the first result.
 *
 * @phpstan-type Onboarding array{
 *     kind: 'organization'|'person', rdb_code: string|null, name: string, industry: string, district: string, established_year: int|null,
 *     representative_email: string, evidence_reference: string, reason: string, effective_at: string, request_id: string, mandate_request_id: string
 * }
 * @phpstan-type Outcome array{status: 'completed', code: string, business_id: string, representative: string, business_access: bool}|array{status: 'rejected', code: string}
 */
final class OnboardBusiness
{
    /** The official representative holds every role a sole trader or a company's representative may. */
    private const REPRESENTATIVE_ROLES = ['owner', 'controller', 'signatory', 'representative'];

    /**
     * The sectors a business is recorded under: the same list Pulse asks for, so one sector is never
     * spelled three ways in the directory.
     *
     * @return list<string>
     */
    public static function sectors(): array
    {
        return array_map(fn (PulseSector $sector): string => $sector->value, PulseSector::cases());
    }

    public function __construct(
        private GetStaffAccess $access,
        private IdentityRepository $identities,
        private ResolveVerifiedOrganization $organizations,
        private ConfigureBusinessAuthority $authority,
    ) {}

    /**
     * @param  Onboarding  $input
     * @return Outcome
     */
    public function handle(int $actorId, array $input): array
    {
        if (! in_array('businesses.verify', $this->access->handle($actorId)['allowed_actions'], true)) {
            return ['status' => 'rejected', 'code' => 'STAFF_PERMISSION_REQUIRED'];
        }
        $userId = $this->identities->accountForEmail($input['representative_email']);
        if ($userId === null) {
            return ['status' => 'rejected', 'code' => 'REPRESENTATIVE_NOT_VERIFIED'];
        }
        $person = $this->identities->forUser($userId);
        $party = $person['party'];
        if ($party === null || $party['kind'] !== 'person' || ! $party['verified']) {
            return ['status' => 'rejected', 'code' => 'REPRESENTATIVE_NOT_VERIFIED'];
        }
        $partyId = $party['id'];
        $name = $this->identities->accountName($userId);

        try {
            $entity = $input['rdb_code'] === null ? $partyId : (string) $this->organizations->handle($actorId, 'RDB:'.$input['rdb_code'],
                $input['evidence_reference'], $input['reason'], $input['request_id'])['party_id'];
            /** @var array{status: string, code: string, data: array{business: array{id: string}}} $result */
            $result = $this->authority->handle($actorId, $input['kind'], $entity, ['name' => $input['name'], 'company_code' => $input['rdb_code'],
                'industry' => $input['industry'], 'district' => $input['district'], 'established_year' => $input['established_year']],
                ['people' => [['party_id' => $partyId, 'name' => $name, 'roles' => self::REPRESENTATIVE_ROLES, 'permissions' => MandateAuthority::PERMISSIONS]],
                    'required_signatories' => [$partyId], 'effective_at' => $input['effective_at'], 'expires_at' => null,
                    'status' => 'active', 'attested_complete' => true],
                0, $input['evidence_reference'], $input['reason'], $input['mandate_request_id']);
        } catch (CommandRejection|IdentityViolation $refusal) {
            return ['status' => 'rejected', 'code' => $refusal->reason];
        }
        if ($result['status'] !== 'completed') {
            return ['status' => 'rejected', 'code' => $result['code']];
        }
        $memberships = array_filter($person['memberships'], fn (array $membership): bool => $membership['role'] === 'business' && $membership['status'] === 'active');

        return ['status' => 'completed', 'code' => $result['code'], 'business_id' => $result['data']['business']['id'],
            'representative' => $name, 'business_access' => $memberships !== []];
    }
}
