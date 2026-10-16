<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessContacts;
use App\Domain\Operations\CommandRejection;

/**
 * `business.profile.update`: saves the Business's contact details and registered address as its next
 * version, under the actor's current mandate permission, once per request id. The registered name
 * and the RDB facts stay as verified; only contact and address are the Business's to keep current.
 * A refusal before the save is recorded (no current permission, a reused request id) comes back as
 * a rejected result, as a recorded refusal does, so the form can show it beside its fields.
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
        try {
            return $this->contacts->save($userId, $contextRevision, $businessId, $requestId, $input);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason, 'field_errors' => $rejection->fieldErrors];
        }
    }
}
