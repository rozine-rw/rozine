<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\PrimaryAdmission;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Domain\Operations\CommandRejection;

/**
 * The Investor purchase commands as the transport sends them (C3 v2 §2c, AC-04): reserve, confirm,
 * release and cancel a commitment, and the lookup of each by its own `request_id`. Reserve and
 * confirm take their admission from `PrimaryAdmission`; release and cancel need none. A commitment
 * is cancelled by refunding its reservation, recorded as `primary.refund`.
 */
final class ManagePrimaryCheckout
{
    /** The client's command names, keyed to what the checkout journals. */
    public const array COMMANDS = ['primary.reserve', 'primary.confirm', 'primary.release', 'primary.cancel'];

    public function __construct(private PrimaryCheckout $checkout, private PrimaryAdmission $admission, private PrimaryPurchaseIndex $index) {}

    /** @return array<string, mixed> */
    public function reserve(int $userId, int $contextRevision, string $campaignId, string $units, int $expectedCampaignRevision,
        int $quoteRevision, string $requestId): array
    {
        return $this->checkout->reserve($userId, $contextRevision, $campaignId, $units, $requestId,
            $this->admission->forReserve($userId, $expectedCampaignRevision, $quoteRevision));
    }

    /** @return array<string, mixed> */
    public function confirm(int $userId, int $contextRevision, string $reservationId, int $expectedRevision, string $disclosureVersion,
        string $disclosureSha256, string $requestId): array
    {
        return $this->checkout->confirm($userId, $contextRevision, $this->index->campaignOfReservation($reservationId), $reservationId,
            $expectedRevision, $disclosureVersion, $disclosureSha256, $requestId, $this->admission->forConfirm($userId));
    }

    /** @return array<string, mixed> */
    public function release(int $userId, int $contextRevision, string $reservationId, int $expectedRevision, string $requestId): array
    {
        return $this->checkout->release($userId, $contextRevision, $this->index->campaignOfReservation($reservationId), $reservationId,
            $expectedRevision, $requestId);
    }

    /** @return array<string, mixed> */
    public function cancel(int $userId, int $contextRevision, string $commitmentId, int $expectedRevision, string $requestId): array
    {
        $target = $this->index->commitment($commitmentId);

        return $this->checkout->refund($userId, $contextRevision, $target['campaign_id'], $target['reservation_id'], $expectedRevision, $requestId);
    }

    /**
     * The recorded result of one command, looked up within the campaign (reserve) or the
     * reservation (the others) it was sent for; anything else is `OPERATION_NOT_FOUND`.
     *
     * @return array<string, mixed>
     */
    public function find(int $userId, int $contextRevision, string $command, string $campaignId, ?string $reservationId, string $requestId): array
    {
        if ($command === 'primary.reserve') {
            return $this->checkout->findReservation($userId, $contextRevision, $campaignId, $requestId);
        }
        if ($reservationId === null || ! in_array($command, self::COMMANDS, true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return match ($command) {
            'primary.confirm' => $this->checkout->findConfirmation($userId, $contextRevision, $campaignId, $reservationId, $requestId),
            'primary.release' => $this->checkout->findRelease($userId, $contextRevision, $campaignId, $reservationId, $requestId),
            default => $this->checkout->findRefund($userId, $contextRevision, $campaignId, $reservationId, $requestId),
        };
    }
}
