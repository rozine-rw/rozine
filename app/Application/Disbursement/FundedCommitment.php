<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Domain\Disbursement\DisbursementViolation;

/**
 * One immutable funded commitment as the funding source retains it: its Party, originating
 * operation, whole units and the exact ordinal ranges, rights and terms bought at confirmation.
 * Issue must use exactly these; nothing here is recomputed.
 */
final readonly class FundedCommitment
{
    public const int UNIT = 5000;

    /**
     * @param  list<array{first: int, last: int}>  $ordinals
     * @param  array<string, mixed>  $rights  `UnitRights::toArray()`
     * @param  array<string, mixed>  $terms  `PrimaryTerms::toArray()`
     */
    public function __construct(
        public string $id,
        public string $partyId,
        public string $originOperationId,
        public int $units,
        public array $ordinals,
        public array $rights,
        public array $terms,
        public string $principal,
    ) {
        $covered = 0;
        foreach ($ordinals as $range) {
            if ($range['first'] < 1 || $range['last'] < $range['first']) {
                throw new DisbursementViolation('FUNDED_COMMITMENT_INVALID');
            }
            $covered += $range['last'] - $range['first'] + 1;
        }
        if ($units < 1 || $covered !== $units || preg_match('/^[1-9][0-9]{0,11}$/D', $principal) !== 1 || $principal !== (string) ($units * self::UNIT)) {
            throw new DisbursementViolation('FUNDED_COMMITMENT_INVALID');
        }
    }

    /** @return array<string, mixed> the canonical facts the commitments digest binds */
    public function toArray(): array
    {
        return ['id' => $this->id, 'party_id' => $this->partyId, 'origin_operation_id' => $this->originOperationId, 'units' => $this->units,
            'ordinals' => $this->ordinals, 'rights' => $this->rights, 'terms' => $this->terms, 'principal' => $this->principal];
    }
}
