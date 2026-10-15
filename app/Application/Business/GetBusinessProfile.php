<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessContacts;
use App\Application\Wallet\GetBusinessWallet;

/**
 * The Business Profile tab for a current mandate holder. Every Business read already runs under
 * verified-party authority, so the profile and mandate returned are the verified ones, exactly as
 * held; the rating is the latest published one Home shows, and the accounts are the Business's own
 * verified ones it deposits from and withdraws to. `contact` is the latest saved contact details and
 * registered address (null until the first save), and `can_update_contact` whether the viewer holds
 * `business.profile.update`. No RDB certificate is held anywhere yet, so none is returned.
 *
 * @phpstan-import-type Contact from \App\Domain\Business\BusinessContact
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 */
final class GetBusinessProfile
{
    /** The design's Profile sections (Business.dc.html L5022–5028), in its menu order. */
    public const array SECTIONS = ['company', 'security', 'permissions', 'linked', 'support', 'terms', 'privacy'];

    public function __construct(private WithBusinessAuthority $authority, private BusinessCampaignStore $campaigns, private GetBusinessWallet $wallet, private BusinessContacts $contacts) {}

    /**
     * @return array{identity_context_revision: int, business: array{id: string, entity_kind: string, profile: Profile, mandate: Terms}, contact: Contact|null, can_update_contact: bool, rating: array{band: string, score: string}|null, methods: list<array{id: string, kind: string, label: string, masked: string}>}
     */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        [$business, $contact, $canUpdate] = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (array $business, array $identity): array => [['id' => $business['id'], 'entity_kind' => $business['entity_kind'], 'profile' => $business['profile'], 'mandate' => $business['mandate']],
                $this->contacts->current($business['id']), $this->holds($business['mandate']['people'], (string) ($identity['party']['id'] ?? ''), 'business.profile.update')]);

        return ['identity_context_revision' => $contextRevision, 'business' => $business, 'contact' => $contact, 'can_update_contact' => $canUpdate, 'rating' => $this->campaigns->home($userId, $contextRevision, $businessId)['rating'],
            'methods' => $this->wallet->methods($userId, $contextRevision, $businessId)];
    }

    /** @param  list<array{party_id: string, permissions: list<string>}>  $people */
    private function holds(array $people, string $partyId, string $permission): bool
    {
        foreach ($people as $person) {
            if ($person['party_id'] === $partyId && in_array($permission, $person['permissions'], true)) {
                return true;
            }
        }

        return false;
    }
}
