<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;

/**
 * @phpstan-import-type ObligationInput from CashFlowEvidence
 *
 * @phpstan-type Commitment array{id: string, principal: string}
 */
final class AcceptedExposure
{
    /**
     * Count an economic commitment once, including when another evidence source reports it.
     * Accepted but unissued principal has no repayment calendar yet.
     *
     * @param  list<ObligationInput>  $obligations
     * @param  list<Commitment>  $commitments
     */
    public function additional(array $obligations, array $commitments): BigRational
    {
        $seen = [];
        foreach ($obligations as $obligation) {
            $seen[$obligation['id']] = $obligation['principal'];
        }
        $additional = BigRational::zero();
        foreach ($commitments as $commitment) {
            $principal = ExactFinancialValue::amount($commitment['principal']);
            if ($commitment['id'] === '' || (isset($seen[$commitment['id']]) && $seen[$commitment['id']] !== $commitment['principal'])) {
                throw new UnderwritingViolation('OBLIGATION_EVIDENCE_CONFLICT');
            }
            if (! isset($seen[$commitment['id']])) {
                $additional = $additional->plus($principal);
                $seen[$commitment['id']] = $commitment['principal'];
            }
        }

        return $additional;
    }
}
