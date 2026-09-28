<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/** Server-sourced disclosed terms. Tier eligibility and fee policy are separate inputs. */
final readonly class PrimaryTerms
{
    /** Approved rates only; selecting a tier from active capital is a separate policy input. */
    private const array FEE_BPS = ['standard' => 1000, 'bronze' => 800, 'silver' => 650, 'gold' => 550, 'platinum' => 500, 'diamond' => 400];

    /** @param array{tier: string, rate_bps: int, basis: string, policy_version: string} $earningsFee */
    private function __construct(public string $ratePercent, public int $termMonths, public string $policyVersion,
        public string $disclosureVersion, public string $disclosureSha256, public array $earningsFee, public string $payoutFee) {}

    public static function disclosed(string $ratePercent, int $termMonths, string $policyVersion,
        string $disclosureVersion, string $disclosureSha256, mixed $earningsFee, string $payoutFee): self
    {
        if ($earningsFee === null || trim($policyVersion) === '') {
            throw new PrimaryViolation('POLICY_INPUT_REQUIRED');
        }
        if (! is_array($earningsFee)) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }
        $feeVersion = $earningsFee['policy_version'] ?? null;
        if ($feeVersion === null || (is_string($feeVersion) && trim($feeVersion) === '')) {
            throw new PrimaryViolation('POLICY_INPUT_REQUIRED');
        }
        if (! is_string($feeVersion) || ! is_string($earningsFee['tier'] ?? null) || ! is_int($earningsFee['rate_bps'] ?? null)
            || ($earningsFee['basis'] ?? null) !== 'return_only'
            || ! isset(self::FEE_BPS[$earningsFee['tier']]) || self::FEE_BPS[$earningsFee['tier']] !== $earningsFee['rate_bps']
            || ! preg_match('/^(1[0-4]\.[0-9]|15\.0)$/D', $ratePercent) || ! in_array($termMonths, [3, 4, 5, 6], true)
            || trim($disclosureVersion) === '' || ! preg_match('/^[a-f0-9]{64}$/D', $disclosureSha256)
            || ! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $payoutFee)) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }

        return new self($ratePercent, $termMonths, $policyVersion, $disclosureVersion, $disclosureSha256,
            ['tier' => $earningsFee['tier'], 'rate_bps' => $earningsFee['rate_bps'], 'basis' => $earningsFee['basis'], 'policy_version' => $earningsFee['policy_version']], $payoutFee);
    }

    public function requireRights(UnitRights $rights): void
    {
        $expectedReturn = BigRational::of($this->ratePercent)->dividedBy(100)->multipliedBy($rights->campaignPrincipal)
            ->toScale(0, RoundingMode::HalfUp)->toBigInteger();
        if ($this->termMonths !== count($rights->instalments) || ! $rights->campaignReturn->isEqualTo($expectedReturn)
            || ! BigInteger::of($this->payoutFee)->isEqualTo($this->scheduledPayoutFee($rights))) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }
    }

    /** The scheduled quote: half-up per return payout, never a charge on principal. */
    public function scheduledPayoutFee(UnitRights $rights): BigInteger
    {
        $fee = BigInteger::zero();
        foreach ($rights->instalments as $instalment) {
            $fee = $fee->plus(BigInteger::of($instalment['return'])->multipliedBy($this->earningsFee['rate_bps'])->dividedBy(10000, RoundingMode::HalfUp));
        }

        return $fee;
    }

    public function requireFreshDisclosure(self $previous): void
    {
        if (($this->policyVersion !== $previous->policyVersion || $this->earningsFee !== $previous->earningsFee || $this->payoutFee !== $previous->payoutFee)
            && $this->disclosureSha256 === $previous->disclosureSha256) {
            throw new PrimaryViolation('DISCLOSURE_STALE');
        }
    }

    public function requireAcknowledged(self $current, string $acknowledgedVersion, string $acknowledgedSha256): void
    {
        if ($this->disclosureVersion !== $acknowledgedVersion || ! hash_equals($this->disclosureSha256, $acknowledgedSha256)
            || $this->disclosureSha256 !== $current->disclosureSha256 || $this->toArray() !== $current->toArray()) {
            throw new PrimaryViolation('DISCLOSURE_STALE');
        }
    }

    /** @return array{rate_pct: string, term_months: int, payout_fee: array{currency: string, amount: string}, earnings_fee: array{tier: string, rate_bps: int, basis: string, policy_version: string}, policy_version: string, disclosure_version: string} */
    public function toArray(): array
    {
        return ['rate_pct' => $this->ratePercent, 'term_months' => $this->termMonths,
            'payout_fee' => ['currency' => 'RWF', 'amount' => $this->payoutFee], 'earnings_fee' => $this->earningsFee,
            'policy_version' => $this->policyVersion, 'disclosure_version' => $this->disclosureVersion];
    }
}
