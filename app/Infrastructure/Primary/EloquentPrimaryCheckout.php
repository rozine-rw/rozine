<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\PrimaryReservationRecord;
use Closure;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final readonly class EloquentPrimaryCheckout implements PrimaryCheckout
{
    public function __construct(private PrimaryCampaignSource $campaigns, private AuthorizeActiveRole $authority,
        private OperationJournal $journal, private PrimaryReservations $reservations) {}

    public function reserve(int $userId, int $contextRevision, string $campaignId, string $units, string $requestId, Closure $admit): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId,
            function (array $identity) use ($userId, $contextRevision, $campaignId, $units, $requestId, $admit): array {
                $partyId = (string) $identity['party']['id'];

                return $this->journal->execute('party:'.$partyId, $userId, 'primary.reserve', $requestId, 'campaign', $campaignId,
                    ['identity_context_revision' => $contextRevision, 'units' => $units],
                    function (): void {}, function (string $operationId) use ($campaignId, $partyId, $units, $admit): OperationResult {
                        $result = $this->reservations->reserve($campaignId, $partyId, $operationId, $units, $admit);

                        return new OperationResult('RESERVATION_HELD', ['reservation_id' => $result->id, 'entry_id' => $result->hold->entryId,
                            'origin_operation_id' => $result->originOperationId, 'amount' => $result->hold->amount], 1);
                    });
            });
    }

    public function findReservation(int $userId, int $contextRevision, string $campaignId, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId, function (array $identity) use ($campaignId, $requestId): array {
            return $this->journal->find('party:'.$identity['party']['id'], 'primary.reserve', $requestId,
                function (string $type, string $id) use ($campaignId): void {
                    if ($type !== 'campaign' || $id !== $campaignId) {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                });
        });
    }

    public function confirm(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision,
        string $disclosureVersion, string $disclosureSha256, string $requestId, Closure $admit): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId,
            function (array $identity) use ($userId, $contextRevision, $campaignId, $reservationId, $expectedRevision, $disclosureVersion, $disclosureSha256, $requestId, $admit): array {
                $partyId = (string) $identity['party']['id'];
                $root = $this->reservationTarget($campaignId, $reservationId, $partyId);

                $result = $this->journal->execute('party:'.$partyId, $userId, 'primary.confirm', $requestId, 'primary_reservation', $root->id,
                    ['identity_context_revision' => $contextRevision, 'campaign_id' => $root->business_campaign_id, 'expected_revision' => $expectedRevision,
                        'disclosure_version' => $disclosureVersion, 'disclosure_sha256' => $disclosureSha256],
                    function (): void {}, function (string $operationId) use ($root, $partyId, $expectedRevision, $disclosureVersion, $disclosureSha256, $admit): OperationResult {
                        $result = $this->reservations->confirm($root->business_campaign_id, $root->id, $partyId, $operationId, $expectedRevision, $disclosureVersion, $disclosureSha256, $admit);

                        return new OperationResult($result->commitmentId === null ? 'RESERVATION_REQUOTED' : 'RESERVATION_CONFIRMED',
                            ['reservation_id' => $result->id, 'commitment_id' => $result->commitmentId, 'entry_id' => $result->posting?->entryId,
                                'amount' => (string) $result->reservation->rights->principal, 'terms' => $result->reservation->terms->toArray(),
                                'disclosure_sha256' => $result->reservation->terms->disclosureSha256,
                                'expires_at' => $result->reservation->window->expiresAt->format('Y-m-d\TH:i:s.u\Z')], $result->revision);
                    });
                $this->expireRejected($root, $result);

                return $result;
            });
    }

    public function findConfirmation(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId, function (array $identity) use ($campaignId, $reservationId, $requestId): array {
            $root = $this->reservationTarget($campaignId, $reservationId, (string) $identity['party']['id']);

            return $this->journal->find('party:'.$identity['party']['id'], 'primary.confirm', $requestId,
                function (string $type, string $id) use ($root): void {
                    if ($type !== 'primary_reservation' || $id !== $root->id) {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                });
        });
    }

    public function release(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId,
            function (array $identity) use ($userId, $contextRevision, $campaignId, $reservationId, $expectedRevision, $requestId): array {
                $partyId = (string) $identity['party']['id'];
                $root = $this->reservationTarget($campaignId, $reservationId, $partyId);
                $result = $this->journal->execute('party:'.$partyId, $userId, 'primary.release', $requestId, 'primary_reservation', $root->id,
                    ['identity_context_revision' => $contextRevision, 'campaign_id' => $root->business_campaign_id, 'expected_revision' => $expectedRevision],
                    function (): void {}, function (string $operationId) use ($root, $partyId, $expectedRevision): OperationResult {
                        $released = $this->reservations->release($root->business_campaign_id, $root->id, $partyId, $operationId, $expectedRevision);

                        return new OperationResult('RESERVATION_RELEASED', ['reservation_id' => $released->id, 'entry_id' => $released->posting->entryId,
                            'origin_operation_id' => $root->origin_operation_id, 'amount' => $released->posting->amount], $released->revision);
                    });
                $this->expireRejected($root, $result);

                return $result;
            });
    }

    public function findRelease(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId, function (array $identity) use ($campaignId, $reservationId, $requestId): array {
            $root = $this->reservationTarget($campaignId, $reservationId, (string) $identity['party']['id']);

            return $this->journal->find('party:'.$identity['party']['id'], 'primary.release', $requestId,
                function (string $type, string $id) use ($root): void {
                    if ($type !== 'primary_reservation' || $id !== $root->id) {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                });
        });
    }

    public function refund(int $userId, int $contextRevision, string $campaignId, string $reservationId, int $expectedRevision, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId,
            function (array $identity) use ($userId, $contextRevision, $campaignId, $reservationId, $expectedRevision, $requestId): array {
                $partyId = (string) $identity['party']['id'];
                $root = $this->reservationTarget($campaignId, $reservationId, $partyId);

                return $this->journal->execute('party:'.$partyId, $userId, 'primary.refund', $requestId, 'primary_reservation', $root->id,
                    ['identity_context_revision' => $contextRevision, 'campaign_id' => $root->business_campaign_id, 'expected_revision' => $expectedRevision],
                    function (): void {}, function () use ($root, $partyId, $expectedRevision): OperationResult {
                        $refund = $this->reservations->refund($root->business_campaign_id, $root->id, $partyId, $expectedRevision);

                        return new OperationResult('COMMITMENT_REFUNDED', ['reservation_id' => $refund->id, 'commitment_id' => $refund->commitmentId,
                            'entry_id' => $refund->cash->returnEntryId, 'origin_operation_id' => $root->origin_operation_id,
                            'amount' => $refund->cash->amount, 'currency' => 'RWF', 'fee' => '0'], $refund->revision);
                    });
            });
    }

    public function findRefund(int $userId, int $contextRevision, string $campaignId, string $reservationId, string $requestId): array
    {
        return $this->withInvestor($userId, $contextRevision, $campaignId, function (array $identity) use ($campaignId, $reservationId, $requestId): array {
            $root = $this->reservationTarget($campaignId, $reservationId, (string) $identity['party']['id']);

            return $this->journal->find('party:'.$identity['party']['id'], 'primary.refund', $requestId,
                function (string $type, string $id) use ($root): void {
                    if ($type !== 'primary_reservation' || $id !== $root->id) {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                });
        });
    }

    /**
     * The journal rolls back a rejected operation's savepoint. The enclosing Business and
     * authority transaction therefore records expiry only after the rejected receipt exists;
     * a failure to return the cash rolls back that receipt and all terminal evidence together.
     *
     * @param  array<string, mixed>  $result
     */
    private function expireRejected(PrimaryReservationRecord $root, array $result): void
    {
        if ($result['status'] === 'rejected' && $result['code'] === 'RESERVATION_EXPIRED') {
            $this->reservations->expire($root->business_campaign_id, $root->id, $result['operation_id']);
        }
    }

    private function reservationTarget(string $campaignId, string $reservationId, string $partyId): PrimaryReservationRecord
    {
        return PrimaryReservationRecord::query()->whereKey($reservationId)->where('business_campaign_id', $campaignId)->where('party_id', $partyId)->first()
            ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
    }

    /**
     * @param  Closure(AccessSnapshot): array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function withInvestor(int $userId, int $contextRevision, string $campaignId, Closure $operation): array
    {
        return DB::transaction(function () use ($userId, $contextRevision, $campaignId, $operation): array {
            $this->campaigns->lockBusiness($campaignId);

            return $this->authority->handle($userId, 'investor', null, $contextRevision, $operation);
        }, 3);
    }
}
