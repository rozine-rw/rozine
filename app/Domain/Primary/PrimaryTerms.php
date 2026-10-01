<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/** Server-sourced disclosed terms. Tier eligibility and fee policy are separate inputs. */
final readonly class PrimaryTerms
{
    /** Approved rates only; selecting a tier from active capital is a separate policy input. */
    private const array FEE_BPS = ['standard' => 1000, 'bronze' => 800, 'silver' => 650, 'gold' => 550, 'platinum' => 500, 'diamond' => 400];

    public string $disclosureSha256;

    /** @param array{tier: string, rate_bps: int, basis: string, policy_version: string} $earningsFee */
    private function __construct(public string $ratePercent, public int $termMonths, public string $policyVersion,
        public string $disclosureVersion, public array $earningsFee, public string $payoutFee, UnitRights $rights)
    {
        $this->disclosureSha256 = $this->digestFor($rights);
    }

    public static function disclosed(string $ratePercent, int $termMonths, string $policyVersion,
        string $disclosureVersion, mixed $earningsFee, string $payoutFee, UnitRights $rights): self
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
            || trim($disclosureVersion) === '' || ! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $payoutFee)) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }
        foreach ([$policyVersion, $disclosureVersion, $feeVersion] as $version) {
            if (! mb_check_encoding($version, 'UTF-8') || preg_match('/[\x{2028}\x{2029}]/u', $version)) {
                throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
            }
        }

        return new self($ratePercent, $termMonths, $policyVersion, $disclosureVersion,
            ['tier' => $earningsFee['tier'], 'rate_bps' => $earningsFee['rate_bps'], 'basis' => $earningsFee['basis'], 'policy_version' => $feeVersion], $payoutFee, $rights);
    }

    public function requireRights(UnitRights $rights): void
    {
        $expectedReturn = $rights->campaignPrincipal->multipliedBy(str_replace('.', '', $this->ratePercent))->dividedBy(1000, RoundingMode::HalfUp);
        $total = $rights->campaignPrincipal->plus($expectedReturn);
        $regular = $total->dividedBy($this->termMonths, RoundingMode::HalfUp);
        $payments = array_fill(0, $this->termMonths - 1, (string) $regular);
        $payments[] = (string) $total->minus($regular->multipliedBy($this->termMonths - 1));
        if ($this->termMonths !== count($rights->instalments) || ! $rights->campaignReturn->isEqualTo($expectedReturn)
            || $rights->campaignPayments !== $payments || ! BigInteger::of($this->payoutFee)->isEqualTo($this->scheduledPayoutFee($rights))
            || ! hash_equals($this->disclosureSha256, $this->digestFor($rights))) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }
    }

    /** The scheduled quote: half-up per return payout, never a charge on principal. */
    public function scheduledPayoutFee(UnitRights $rights): BigInteger
    {
        $fee = BigInteger::zero();
        foreach ($this->payoutFees($rights) as $instalment) {
            $fee = $fee->plus($instalment['amount']);
        }

        return $fee;
    }

    /** @return list<array{index: int, amount: string}> */
    private function payoutFees(UnitRights $rights): array
    {
        return array_map(fn (array $instalment): array => ['index' => $instalment['index'],
            'amount' => (string) BigInteger::of($instalment['return'])->multipliedBy($this->earningsFee['rate_bps'])->dividedBy(10000, RoundingMode::HalfUp)], $rights->instalments);
    }

    /** JCS binds every acknowledgement to exact terms and rights, independent of quote history. */
    private function digestFor(UnitRights $rights): string
    {
        $payload = ['contract' => 'primary-disclosure-1', 'terms' => $this->toArray(),
            'campaign' => ['principal' => (string) $rights->campaignPrincipal, 'return' => (string) $rights->campaignReturn, 'payments' => $rights->campaignPayments],
            'units' => ['total' => (string) $rights->ordinals->totalUnits, 'ranges' => $rights->ordinals->ranges],
            'rights' => $rights->toArray(), 'payout_fees' => $this->payoutFees($rights)];

        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize((object) $payload, false));
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
