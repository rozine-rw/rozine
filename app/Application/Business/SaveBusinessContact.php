<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessContacts;

/**
 * `business.profile.update`: saves the Business's contact details and registered address as its next
 * version, under the actor's current mandate permission, once per request id. The registered name
 * and the RDB facts stay as verified; only contact and address are the Business's to keep current.
 */
final class SaveBusinessContact
{
    public function __construct(private BusinessContacts $contacts) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array
    {
        return $this->contacts->save($userId, $contextRevision, $businessId, $requestId, $input);
    }
}
