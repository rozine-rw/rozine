<?php

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Domain\Pulse\PulseUnderwriting;

final class PreviewPulseInvestor
{
    public function __construct(private PulseSignupRepository $signups) {}

    /**
     * @return array{pledge_amount: int, projected_return: int, blended_yield: float, listings: list<array<string, mixed>>}
     */
    public function handle(int $pledgeAmount, int $listingLimit): array
    {
        $pledgeAmount = PulseUnderwriting::normalisePledge($pledgeAmount);

        $listings = array_map(
            static fn (array $listing): array => [
                ...$listing,
                'projected_return' => PulseUnderwriting::projectedReturnAtRate(
                    $pledgeAmount,
                    $listing['flat_rate'],
                ),
            ],
            $this->signups->preQualifiedListings($listingLimit),
        );

        return [
            'pledge_amount' => $pledgeAmount,
            'projected_return' => PulseUnderwriting::projectedReturn($pledgeAmount),
            'blended_yield' => PulseUnderwriting::BLENDED_YIELD,
            'listings' => $listings,
        ];
    }
}
