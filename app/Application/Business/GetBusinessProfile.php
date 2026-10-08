<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessCampaignStore;

/**
 * The Business Profile tab for a current mandate holder. Every Business read already runs under
 * verified-party authority, so the profile and mandate returned are the verified ones, exactly as
 * held; the rating is the latest published one Home shows. No contact details, postal address or
 * RDB certificate is held anywhere yet, so none is returned rather than invented.
 *
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 */
final class GetBusinessProfile
{
    public function __construct(private WithBusinessAuthority $authority, private BusinessCampaignStore $campaigns) {}

    /**
     * @return array{identity_context_revision: int, business: array{id: string, entity_kind: string, profile: Profile, mandate: Terms}, rating: array{band: string, score: string}|null}
     */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $business = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (array $business): array => ['id' => $business['id'], 'entity_kind' => $business['entity_kind'], 'profile' => $business['profile'], 'mandate' => $business['mandate']]);

        return ['identity_context_revision' => $contextRevision, 'business' => $business, 'rating' => $this->campaigns->home($userId, $contextRevision, $businessId)['rating']];
    }
}
