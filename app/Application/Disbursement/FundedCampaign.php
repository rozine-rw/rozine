<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\IntentDigest;

/**
 * A fully funded campaign locked in the caller's transaction by `FundedCampaigns::lockFunded`. It
 * binds the same campaign, Business and exposure reservation, the funded principal and the exact
 * immutable commitments. Commitment principals add up to the campaign principal, and each is its
 * units times RWF 5,000.
 */
final readonly class FundedCampaign
{
    /** @param list<FundedCommitment> $commitments in stable id order */
    public function __construct(
        public string $campaignId,
        public string $businessId,
        public string $businessName,
        public string $title,
        public string $exposureReservationId,
        public string $principal,
        public string $fundedAt,
        public int $termMonths,
        public array $commitments,
    ) {
        $total = 0;
        $previous = '';
        foreach ($commitments as $commitment) {
            if (strcmp($commitment->id, $previous) <= 0) {
                throw new DisbursementViolation('FUNDED_CAMPAIGN_INVALID');
            }
            $previous = $commitment->id;
            $total += (int) $commitment->principal;
        }
        if ($commitments === [] || preg_match('/^[1-9][0-9]{0,11}$/D', $principal) !== 1 || (string) $total !== $principal
            || $termMonths < 1 || $termMonths > 120) {
            throw new DisbursementViolation('FUNDED_CAMPAIGN_INVALID');
        }
    }

    public function commitmentsDigest(): string
    {
        return IntentDigest::commitments(array_map(fn (FundedCommitment $commitment): array => $commitment->toArray(), $this->commitments));
    }

    /** @return list<string> */
    public function partyIds(): array
    {
        return array_values(array_unique(array_map(fn (FundedCommitment $commitment): string => $commitment->partyId, $this->commitments)));
    }
}
