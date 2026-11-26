<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\InstitutionalInvestors;
use App\Domain\Operations\CommandRejection;
use App\Models\InstitutionalInvestor;

/** @phpstan-import-type Institution from InstitutionalInvestors */
final class EloquentInstitutionalInvestors implements InstitutionalInvestors
{
    public function record(int $actorId, string $partyId, string $applicationId, string $legalName, string $rdbCode, string $type, int $monthlyCommitment): void
    {
        if (InstitutionalInvestor::query()->where('party_id', $partyId)->exists()) {
            throw new CommandRejection('INSTITUTION_ALREADY_RECORDED', 409);
        }
        (new InstitutionalInvestor)->forceFill(['party_id' => $partyId, 'role_application_id' => $applicationId, 'legal_name' => $legalName,
            'rdb_code' => $rdbCode, 'institution_type' => $type, 'monthly_commitment' => $monthlyCommitment,
            'approved_by_user_id' => $actorId, 'approved_at' => now()])->save();
    }

    public function forParty(string $partyId): ?array
    {
        $institution = InstitutionalInvestor::query()->where('party_id', $partyId)->first();

        return $institution === null ? null : ['legal_name' => $institution->legal_name, 'rdb_code' => $institution->rdb_code,
            'type' => $institution->institution_type, 'monthly_commitment' => $institution->monthly_commitment,
            'approved_at' => $institution->approved_at->toIso8601String()];
    }
}
