<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Auditor\Contracts\AuditorProfileStore;
use App\Application\Business\OnboardBusiness;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\Contracts\RoleApplicationStore;
use App\Domain\Operations\CommandRejection;

/**
 * Compliance's review of role-switch applications: business KYB on the Businesses page
 * (`businesses.verify`) and Auditor licences on the Auditors page (`audit.partners.verify`), each in one
 * drawer (Erastus, 23 Nov 2026). Approval does everything in one step: a business is onboarded with the
 * applicant as its mandated representative, an Auditor is accredited from the reviewed certificate, and
 * the applicant's Investor role ends, since an account holds one role at a time.
 *
 * @phpstan-import-type Queue from RoleApplicationStore
 * @phpstan-import-type Review from RoleApplicationStore
 * @phpstan-import-type Approval from RoleApplicationStore
 */
final class ReviewRoleApplications
{
    public function __construct(
        private RoleApplicationStore $store,
        private OnboardBusiness $onboarding,
        private IdentityAccessStore $access,
        private AuditorProfileStore $auditors,
    ) {}

    /** @return Queue */
    public function queue(int $actorId, string $role, string $tab, string $search, int $limit = 50): array
    {
        return $this->store->queue($actorId, $role, $tab, $search, $limit);
    }

    /**
     * The review tab of the Businesses or Auditors page: the queue in one status tab and, when one is
     * opened, the application in the drawer. A viewer who may not decide that role sees neither.
     *
     * @return array{applications: Queue|null, application: Review|null}
     */
    public function page(int $actorId, string $role, bool $allowed, string $tab, ?string $selected): array
    {
        if (! $allowed) {
            return ['applications' => null, 'application' => null];
        }
        $application = null;
        if ($selected !== null && $selected !== '') {
            try {
                $application = $this->store->show($actorId, $selected);
            } catch (CommandRejection) {
                $application = null;
            }
        }

        return ['applications' => $this->store->queue($actorId, $role, $tab, '', 50),
            'application' => $application !== null && $application['role'] === $role ? $application : null];
    }

    /**
     * @param  list<string>  $partyIds
     * @return array<string, array{id: string, role: string, status: string}>
     */
    public function forParties(array $partyIds): array
    {
        return $this->store->forParties($partyIds);
    }

    /** @return Review */
    public function show(int $actorId, string $applicationId): array
    {
        return $this->store->show($actorId, $applicationId);
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $applicationId, string $documentId): array
    {
        return $this->store->document($actorId, $applicationId, $documentId);
    }

    /** @return array<string, mixed> */
    public function decide(int $actorId, string $applicationId, int $expectedRevision, string $decision, string $note, string $requestId): array
    {
        return $this->store->decide($actorId, $applicationId, $expectedRevision, $decision, $note, $requestId,
            fn (array $approval): ?string => $approval['state']['role'] === 'business'
                ? $this->business($actorId, $approval) : $this->auditor($actorId, $approval));
    }

    /** @param Approval $approval */
    private function business(int $actorId, array $approval): string
    {
        $company = $approval['state']['answers']['company'] ?? throw new CommandRejection('APPLICATION_INCOMPLETE', 409);
        $reason = 'Business KYB approved: onboarded with the applicant as its representative.';
        $result = $this->onboarding->handle($actorId, ['kind' => 'organization', 'rdb_code' => $company['rdb_code'], 'name' => $company['legal_name'],
            'industry' => $company['industry'], 'district' => $company['district'], 'established_year' => (int) substr($company['incorporation_date'], 0, 4),
            'representative_email' => $approval['email'], 'evidence_reference' => $approval['reference'], 'reason' => $reason,
            'effective_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'), 'request_id' => self::derived($approval['request_id'], 'organization'),
            'mandate_request_id' => self::derived($approval['request_id'], 'mandate')]);
        if ($result['status'] !== 'completed') {
            throw new CommandRejection($result['code'], 409);
        }
        $this->access->switchRole($actorId, 'businesses.verify', $approval['party_id'], 'business', $reason, self::derived($approval['request_id'], 'switch'));

        return $result['business_id'];
    }

    /** @param Approval $approval */
    private function auditor(int $actorId, array $approval): null
    {
        $licence = $approval['state']['answers']['licence'] ?? throw new CommandRejection('APPLICATION_INCOMPLETE', 409);
        $certificate = $approval['certificate'] ?? throw new CommandRejection('APPLICATION_INCOMPLETE', 409);
        $reason = 'Auditor licence approved after checking the ICPAR register.';
        $this->access->switchRole($actorId, 'audit.partners.verify', $approval['party_id'], 'auditor', $reason, self::derived($approval['request_id'], 'switch'));
        $this->auditors->accreditFromIdentityCheck($actorId, $approval['party_id'], $licence['licence_number'], $licence['expires_on'],
            $certificate['filename'], $certificate['content'], $approval['reference'], $reason);

        return null;
    }

    /** A stable request id for each effect of one approval, so a retried approval replays rather than repeats them. */
    private static function derived(string $requestId, string $effect): string
    {
        $hash = hash('sha256', $requestId.'/'.$effect);

        return sprintf('%s-%s-5%s-%s%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 13, 3),
            dechex(8 | (hexdec($hash[16]) & 3)), substr($hash, 17, 3), substr($hash, 20, 12));
    }
}
