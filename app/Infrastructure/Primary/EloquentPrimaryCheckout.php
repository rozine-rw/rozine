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
