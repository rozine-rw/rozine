<?php

namespace App\Application\Pulse\Contracts;

interface PulseSignupRepository
{
    /**
     * @return array{pledged: int, investors: int, businesses: int, average_loan: int|null, average_yield: float|null, average_rating: float|null, average_term: int|null}
     */
    public function traction(): array;

    /**
     * @return list<array{id: int, name: string, district: string, listed: bool, term_months: int, flat_rate: float, rating_band: string, rating_score: float}>
     */
    public function preQualifiedListings(int $limit): array;

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array{pledge_amount: int, projected_return: int, blended_yield: float}  $investment
     * @return array{queue_number: string}
     */
    public function createInvestor(array $signup, array $investment): array;

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array<string, mixed>  $sizing
     * @return array{queue_number: string, loan_number: string}
     */
    public function createBusiness(array $signup, array $sizing, bool $listed): array;
}
