<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\BusinessMandatesInForce;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\VerifiedDestination;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\BusinessFundingMethod;
use Carbon\CarbonImmutable;
use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * The Business's verified payout destination, produced from the account it linked itself
 * (`business.wallet.link_method`). BRS v3 KYC-R03: the Business links a bank or MoMo account in its own
 * registered name "for disbursements and withdrawals"; SVC-R01 pays the raise "to the business's
 * verified account". No fixture is ever read.
 *
 * A destination exists only while all of these hold when it is asked:
 * - the environment asked for is this installation's profile;
 * - the Business's current mandate is active and in force;
 * - the Business has a linked account that is verified and not unlinked, whose verification source is
 *   accepted here (`synthetic` only where the synthetic wallet guard allows, so production never pays
 *   to a synthetic verification).
 *
 * With more than one such account, the one verified first is paid, so a later link never redirects
 * money until the earlier account is unlinked. The BRS sets no re-verification period for a linked
 * account, so the destination expires with the mandate that authorizes it, or never while that mandate
 * is open-ended. When anything is missing the answer is null and disbursement waits.
 */
final class EloquentBusinessPayoutDestinations implements PayoutDestinations
{
    /** The expiry of a destination whose mandate has no end: the BRS sets no re-verification period. */
    public const string OPEN_ENDED = '9999-12-31T23:59:59+00:00';

    public function __construct(private EnvironmentIsolation $isolation, private SyntheticWalletGuard $synthetic, private BusinessMandatesInForce $mandates) {}

    public function verified(string $businessId, string $environment): ?VerifiedDestination
    {
        $mandate = $environment === $this->isolation->profile() ? $this->mandates->current($businessId) : null;
        $account = $mandate === null ? null : BusinessFundingMethod::query()->where('business_id', $businessId)
            ->whereNotNull('verified_at')->whereNull('revoked_at')->whereIn('verification_source', $this->acceptedSources())
            ->orderBy('verified_at')->orderBy('id')->first();
        if ($mandate === null || $account?->verified_at === null) {
            return null;
        }
        $verifiedAt = $account->verified_at->utc()->toIso8601String();
        $evidence = ['scope' => 'business-payout-destination-v1', 'business_id' => $businessId, 'method_id' => $account->id, 'kind' => $account->kind,
            'verification_source' => $account->verification_source, 'verified_at' => $verifiedAt, 'mandate_id' => $mandate['id'],
            'mandate_version' => $mandate['version']];

        return new VerifiedDestination($account->id, 1, $businessId, $mandate['id'], $account->kind,
            hash('sha256', 'business-payout-account|'.$account->kind.'|'.$account->reference), $environment,
            hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize((object) $evidence, false)), $verifiedAt,
            $mandate['expires_at'] === null ? self::OPEN_ENDED : CarbonImmutable::parse($mandate['expires_at'])->utc()->toIso8601String(),
            $account->label.' '.$account->masked);
    }

    /**
     * The verification sources this installation pays to. Only the synthetic verifier exists, and its
     * accounts are accepted only where synthetic money is allowed.
     *
     * @return list<string>
     */
    private function acceptedSources(): array
    {
        return $this->synthetic->allowed() ? ['synthetic'] : [];
    }
}
