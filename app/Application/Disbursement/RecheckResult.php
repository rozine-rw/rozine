<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Domain\Disbursement\DisbursementViolation;

/**
 * The funding source's current pre-disbursement recheck (MC-02, §11.3): evidence, DSCR,
 * exposure, restrictions, mandate and conditions precedent. `failed` is an evidenced failed
 * condition and takes failed closing at approve or in the worker. `unavailable` is missing policy,
 * evidence or source input: it refuses `POLICY_INPUT_REQUIRED` and never becomes a financial
 * failure.
 */
final readonly class RecheckResult
{
    public const array CAUSES = ['evidence', 'dscr', 'exposure', 'restriction', 'mandate', 'conditions_precedent', 'policy', 'funding_source'];

    /**
     * @param  'passed'|'failed'|'unavailable'  $outcome
     * @param  list<string>  $causes
     */
    private function __construct(public string $outcome, public array $causes, public string $policyVersion) {}

    public static function passed(string $policyVersion): self
    {
        return new self('passed', [], $policyVersion);
    }

    /** @param list<string> $causes */
    public static function failed(array $causes, string $policyVersion): self
    {
        return new self('failed', self::causes($causes), $policyVersion);
    }

    /** @param list<string> $causes */
    public static function unavailable(array $causes, string $policyVersion): self
    {
        return new self('unavailable', self::causes($causes), $policyVersion);
    }

    /**
     * @param  list<string>  $causes
     * @return list<string>
     */
    private static function causes(array $causes): array
    {
        if ($causes === [] || array_diff($causes, self::CAUSES) !== []) {
            throw new DisbursementViolation('RECHECK_CAUSE_INVALID');
        }
        $causes = array_values(array_unique($causes));
        sort($causes);

        return $causes;
    }
}
