<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use RuntimeException;

/** A live publication's progress, shared by the Business campaign page and the Investor Deals. */
final class CampaignProgress
{
    public function __construct(private CampaignReservationSummary $reservations, private CampaignFundingEvidence $fundings) {}

    /**
     * Retained raise or funding-lock progress; this never dispatches or certifies payment.
     * Returned and overdue claims remain unavailable until ordinal recycling exists.
     *
     * @param  array<string, mixed>  $payload
     * @return array{progress: array<string, mixed>, has_unreturned_holds: bool}
     */
    public function project(string $campaignId, array $payload): array
    {
        $at = now('UTC');
        $summary = $this->reservations->read($campaignId, $at->toDateTimeImmutable());
        $remaining = BigInteger::of($payload['principal'])->minus($summary['committed_principal'])->minus($summary['held_principal']);
        $available = BigInteger::of($payload['quote']['units'])->minus($summary['occupied_units']);
        $unavailable = BigInteger::of($summary['occupied_units'])->minus($summary['committed_units'])->minus($summary['held_units']);
        if ($remaining->isNegative() || $available->isNegative() || $unavailable->isNegative()) {
            throw new RuntimeException('RESERVATION_SUMMARY_INTEGRITY_FAILED');
        }

        $hasUnreturnedHolds = $summary['held_principal'] !== '0' || $summary['expired_hold_principal'] !== '0';
        $funding = $this->fundings->find($campaignId);
        if ($funding !== null) {
            return ['has_unreturned_holds' => $hasUnreturnedHolds, 'progress' => ['phase' => 'funded', 'lifecycle' => 'funded_pending_disbursement', 'restriction' => null,
                'committed' => ['currency' => 'RWF', 'amount' => $funding['principal']],
                'investors' => count(array_unique(array_column($funding['commitments'], 'party_id'))),
                'funded_at' => $funding['recorded_at'], 'closing' => ['stage' => 'awaiting_disbursement']]];
        }
        $lifecycle = match (true) {
            $at->gte($payload['expires_at']) => 'closing_pending_settlement',
            BigInteger::of($summary['committed_principal'])->isEqualTo($payload['principal']) => 'sold_out_pending_settlement',
            $available->isZero() && BigInteger::of($summary['held_units'])->isPositive() => 'fully_reserved',
            $available->isZero() => 'inventory_unavailable',
            default => 'live',
        };

        return ['has_unreturned_holds' => $hasUnreturnedHolds, 'progress' => ['phase' => 'raising', 'lifecycle' => $lifecycle, 'restriction' => null,
            'committed' => ['currency' => 'RWF', 'amount' => $summary['committed_principal']],
            'reserved' => ['currency' => 'RWF', 'amount' => $summary['held_principal']],
            'remaining' => ['currency' => 'RWF', 'amount' => (string) $remaining],
            'units' => ['total' => $payload['quote']['units'], 'available' => (string) $available,
                'reserved' => $summary['held_units'], 'committed' => $summary['committed_units'], 'unavailable' => (string) $unavailable],
            'investors' => $summary['investors'],
            'funded_pct' => (string) BigDecimal::of($summary['committed_principal'])->multipliedBy(100)
                ->dividedBy($payload['principal'], 1, RoundingMode::Down),
            'clock' => ['starts_at' => $payload['recorded_at'], 'expires_at' => $payload['expires_at']]]];
    }
}
