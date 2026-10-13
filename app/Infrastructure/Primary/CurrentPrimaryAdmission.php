<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\PrimaryAdmission;
use App\Application\Primary\Contracts\RiskAcknowledgements;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\EarningsFeeBand;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletBalance;
use App\Domain\Wallet\WalletMoney;
use App\Models\InvestorWallet;
use App\Models\PrimaryHolding;
use App\Models\User;
use Brick\Math\BigInteger;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Admission for a new reserve or confirm, run inside `PrimaryCheckout`'s locks after the Investor
 * role (which requires a verified Party), the campaign, its unit capacity, the 50% concentration
 * limit and the declared connections have already been checked:
 *
 * - the Party has acknowledged the current KYC-R05 risk statements, or it is refused
 *   `RISK_ACKNOWLEDGEMENT_REQUIRED`;
 * - the earnings-fee band is chosen from active capital after this checkout (INV-R04): cash
 *   committed to confirmed purchases not yet issued, plus principal of issued Holdings, plus
 *   this line's principal. Wallet cash never counts. Until servicing is live no principal has
 *   been repaid, so an issued Holding's principal is all outstanding.
 *
 * Confirm prices the band afresh; if it moved since the reserve the checkout re-quotes rather
 * than confirming terms the Investor did not see.
 */
final readonly class CurrentPrimaryAdmission implements PrimaryAdmission
{
    /** The campaign publication is immutable, so its only revision is the first. */
    private const int CAMPAIGN_REVISION = 1;

    public const string DISCLOSURE_VERSION = 'primary-disclosure-2026-10-13';

    public function __construct(private RiskAcknowledgements $acknowledgements) {}

    public function forReserve(int $userId, int $expectedCampaignRevision, int $quoteRevision): Closure
    {
        return function (UnitRights $rights, array $campaign) use ($userId, $expectedCampaignRevision): PrimaryTerms {
            if ($expectedCampaignRevision !== self::CAMPAIGN_REVISION) {
                throw new CommandRejection('VERSION_CONFLICT', 409, self::CAMPAIGN_REVISION);
            }

            return $this->terms($userId, $rights, $campaign);
        };
    }

    public function forConfirm(int $userId): Closure
    {
        return fn (UnitRights $rights, array $campaign): PrimaryTerms => $this->terms($userId, $rights, $campaign);
    }

    /** @param array<string, mixed> $campaign */
    private function terms(int $userId, UnitRights $rights, array $campaign): PrimaryTerms
    {
        $partyId = (string) User::query()->whereKey($userId)->value('party_id');
        if ($partyId === '' || ! $this->acknowledgements->current($partyId)) {
            throw new CommandRejection('RISK_ACKNOWLEDGEMENT_REQUIRED', 409);
        }
        $fee = EarningsFeeBand::forActiveCapital($this->outstanding($partyId)->plus($rights->principal));
        if (! is_string($campaign['rate_pct'] ?? null) || ! is_int($campaign['term_months'] ?? null) || ! is_string($campaign['policy_version'] ?? null)) {
            throw new CommandRejection('POLICY_INPUT_REQUIRED');
        }

        return PrimaryTerms::disclosed($campaign['rate_pct'], $campaign['term_months'], $campaign['policy_version'], self::DISCLOSURE_VERSION,
            $fee, PrimaryTerms::payoutFeeFor($rights, $fee['rate_bps']), $rights);
    }

    /** Principal the Party already has working in notes: committed purchase cash plus issued Holdings. */
    private function outstanding(string $partyId): BigInteger
    {
        $walletId = InvestorWallet::query()->where('party_id', $partyId)->value('id');
        $committed = BigInteger::zero();
        if (is_string($walletId)) {
            $sums = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
                ->where('ledger_accounts.wallet_id', $walletId)->where('ledger_accounts.kind', 'investor_committed')->groupBy('ledger_lines.direction')
                ->selectRaw('ledger_lines.direction AS direction, sum(ledger_lines.amount)::text AS total')->pluck('total', 'direction');
            $committed = BigInteger::of(WalletBalance::bucket(WalletMoney::of((string) ($sums['credit'] ?? '0')),
                WalletMoney::of((string) ($sums['debit'] ?? '0')))->amount());
        }
        $issued = (string) PrimaryHolding::query()->where('party_id', $partyId)->selectRaw('coalesce(sum(principal), 0)::text AS total')->value('total');

        return $committed->plus($issued);
    }
}
