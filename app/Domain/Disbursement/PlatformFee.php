<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * The platform fee taken from a raise at disbursement (BRS v2.0 SVC-R01, FEE-R01, FEE-R02, ruled on
 * #154 16 Oct 2026): 3% of the principal from the Business, of which 40% is credited to the Audit
 * Partner who verified the note and the rest is Rozine's. Each part is rounded half up to a whole
 * franc and the parts always balance: the Business receives the principal less the fee, and the
 * return is still owed on the full principal.
 */
final readonly class PlatformFee
{
    public const string VERSION = 'brs-2.0-fee-r01';

    public const int FEE_BPS = 300;

    public const int AUDIT_PARTNER_SHARE_BPS = 4000;

    private function __construct(
        public BigInteger $principal,
        public BigInteger $fee,
        public BigInteger $auditPartnerShare,
        public BigInteger $rozineShare,
        public BigInteger $payout,
    ) {}

    public static function of(string $principal): self
    {
        if (preg_match('/^[1-9][0-9]{0,14}$/D', $principal) !== 1) {
            throw new DisbursementViolation('PLATFORM_FEE_PRINCIPAL_INVALID');
        }
        $gross = BigInteger::of($principal);
        $fee = $gross->multipliedBy(self::FEE_BPS)->dividedBy(10000, RoundingMode::HalfUp);
        $auditPartnerShare = $fee->multipliedBy(self::AUDIT_PARTNER_SHARE_BPS)->dividedBy(10000, RoundingMode::HalfUp);

        return new self($gross, $fee, $auditPartnerShare, $fee->minus($auditPartnerShare), $gross->minus($fee));
    }

    /** @return array{principal: string, fee: string, audit_partner_share: string, rozine_share: string, payout: string, version: string} */
    public function toArray(): array
    {
        return ['principal' => (string) $this->principal, 'fee' => (string) $this->fee, 'audit_partner_share' => (string) $this->auditPartnerShare,
            'rozine_share' => (string) $this->rozineShare, 'payout' => (string) $this->payout, 'version' => self::VERSION];
    }
}
