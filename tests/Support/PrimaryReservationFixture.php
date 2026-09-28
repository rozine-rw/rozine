<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\ReservedCheckout;
use App\Domain\Operations\OperationResult;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\BusinessCampaign;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\User;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Support\Str;

/** Synthetic admission is deliberately explicit; it is not production eligibility or fee policy. */
final class PrimaryReservationFixture
{
    public static function campaign(): BusinessCampaign
    {
        $fixture = AuditSealingFixture::ready();
        AuditSealingFixture::seal($fixture);
        AuditSealingFixture::cosign($fixture);
        $campaigns = app(BusinessCampaignStore::class);
        $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
        $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
            $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());

        return BusinessCampaign::query()->where('business_application_id', $fixture['application']->id)->sole();
    }

    /** @return array{user: User, party: Party, method: InvestorFundingMethod} */
    public static function investor(string $cash = '10000000'): array
    {
        $fixture = InvestorWalletFixture::ready();
        InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, $cash)['data']['intent_id']);

        return $fixture;
    }

    /** @param array<string, mixed> $campaign */
    public static function terms(UnitRights $rights, array $campaign): PrimaryTerms
    {
        $fee = BigInteger::zero();
        foreach ($rights->instalments as $instalment) {
            $fee = $fee->plus(BigInteger::of($instalment['return'])->multipliedBy(1000)->dividedBy(10000, RoundingMode::HalfUp));
        }

        return PrimaryTerms::disclosed($campaign['rate_pct'], $campaign['term_months'], $campaign['policy_version'], 'synthetic-disclosure-1',
            ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-primary-fees-1'], (string) $fee, $rights);
    }

    /**
     * @param  array{user: User, party: Party}  $investor
     * @return array<string, mixed>
     */
    public static function reserve(BusinessCampaign $campaign, array $investor, string $units, ?string $requestId = null): array
    {
        return app(OperationJournal::class)->execute('party:'.$investor['party']->id, $investor['user']->id, 'primary.reserve',
            $requestId ?? (string) Str::uuid(), 'campaign', $campaign->id, ['units' => $units],
            function (): void {},
            function (string $operation) use ($campaign, $investor, $units): OperationResult {
                $result = app(PrimaryReservations::class)->reserve($campaign->id, $investor['party']->id, $operation, $units, self::terms(...));

                return self::outcome($result);
            });
    }

    public static function outcome(ReservedCheckout $result): OperationResult
    {
        return new OperationResult('RESERVATION_HELD', ['reservation_id' => $result->id, 'entry_id' => $result->hold->entryId,
            'origin_operation_id' => $result->originOperationId, 'amount' => $result->hold->amount], 1);
    }
}
