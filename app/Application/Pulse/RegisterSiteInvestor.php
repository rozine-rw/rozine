<?php

declare(strict_types=1);

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Domain\Pulse\PulseUnderwriting;

/**
 * Record an investor who signed up from the marketing site. The site asks for a
 * country rather than a Rwandan province and district, so the address columns
 * stay empty for these signups.
 */
final class RegisterSiteInvestor
{
    public function __construct(private PulseSignupRepository $signups) {}

    /**
     * @param  array{name: string, contact_method: string, contact: string, country: string, ip_address: string|null, user_agent: string|null}  $signup
     * @return array{queue_number: string}
     */
    public function handle(array $signup, int $pledgeAmount): array
    {
        $pledgeAmount = PulseUnderwriting::normalisePledge($pledgeAmount);

        return $this->signups->createInvestor($signup, [
            'pledge_amount' => $pledgeAmount,
            'projected_return' => PulseUnderwriting::projectedReturn($pledgeAmount),
            'blended_yield' => PulseUnderwriting::BLENDED_YIELD,
        ]);
    }
}
