<?php

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Domain\Pulse\PulseUnderwriting;

final class RegisterPulseInvestor
{
    public function __construct(
        private PulseSignupRepository $signups,
        private PreviewPulseInvestor $previewInvestor,
    ) {}

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @return array<string, mixed>
     */
    public function handle(array $signup, int $pledgeAmount, int $listingLimit): array
    {
        $pledgeAmount = PulseUnderwriting::normalisePledge($pledgeAmount);

        $receipt = $this->signups->createInvestor($signup, [
            'pledge_amount' => $pledgeAmount,
            'projected_return' => PulseUnderwriting::projectedReturn($pledgeAmount),
            'blended_yield' => PulseUnderwriting::BLENDED_YIELD,
        ]);

        return [
            ...$receipt,
            'traction' => $this->signups->traction(),
            'investor_preview' => $this->previewInvestor->handle($pledgeAmount, $listingLimit),
        ];
    }
}
