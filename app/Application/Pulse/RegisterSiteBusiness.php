<?php

namespace App\Application\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;

/**
 * Record a business that asked to borrow from the marketing site. The site
 * collects only the figures the owner can answer from memory, so the signup is
 * stored as a lead and an accountant sizes it later.
 */
final class RegisterSiteBusiness
{
    public function __construct(private PulseSignupRepository $signups) {}

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array{annual_revenue: int, annual_costs: int, term_months: int}  $figures
     * @return array{queue_number: string}
     */
    public function handle(array $signup, array $figures): array
    {
        return $this->signups->createBusinessLead($signup, $figures);
    }
}
