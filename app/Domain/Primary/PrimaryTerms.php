<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Brick\Math\BigInteger;

/** Server-sourced disclosed terms. Tier eligibility and fee policy are separate inputs. */
final readonly class PrimaryTerms
{
    /** @param array{tier: string, rate_bps: int, basis: string, policy_version: string} $earningsFee */
    private function __construct(public string $ratePercent, public int $termMonths, public string $policyVersion,
        public string $disclosureVersion, public string $disclosureSha256, public array $earningsFee, public string $payoutFee) {}

    /** @param array{tier: string, rate_bps: int, basis: string, policy_version: string}|null $earningsFee */
    public static function disclosed(string $ratePercent, int $termMonths, string $policyVersion,
        string $disclosureVersion, string $disclosureSha256, ?array $earningsFee, string $payoutFee): self
    {
        if ($earningsFee === null || trim($policyVersion) === '' || trim($earningsFee['policy_version']) === '') {
            throw new PrimaryViolation('POLICY_INPUT_REQUIRED');
        }
        if (! preg_match('/^(1[0-4]\.[0-9]|15\.0)$/D', $ratePercent) || ! in_array($termMonths, [3, 4, 5, 6], true)
            || trim($disclosureVersion) === '' || ! preg_match('/^[a-f0-9]{64}$/D', $disclosureSha256)
            || ! in_array($earningsFee['tier'], ['standard', 'bronze', 'silver', 'gold', 'platinum', 'diamond'], true)
            || $earningsFee['basis'] !== 'return_only' || $earningsFee['rate_bps'] < 0 || $earningsFee['rate_bps'] > 10000
            || ! preg_match('/^(0|[1-9][0-9]{0,63})$/D', $payoutFee)) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
        }

        return new self($ratePercent, $termMonths, $policyVersion, $disclosureVersion, $disclosureSha256,
            ['tier' => $earningsFee['tier'], 'rate_bps' => $earningsFee['rate_bps'], 'basis' => $earningsFee['basis'], 'policy_version' => $earningsFee['policy_version']], $payoutFee);
    }

    public function requireRights(UnitRights $rights): void
    {
        if ($this->termMonths !== count($rights->instalments) || BigInteger::of($this->payoutFee)->isGreaterThan($rights->contractualReturn)) {
            throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
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
