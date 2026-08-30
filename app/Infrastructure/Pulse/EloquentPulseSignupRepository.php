<?php

namespace App\Infrastructure\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

final class EloquentPulseSignupRepository implements PulseSignupRepository
{
    /**
     * @return array{pledged: int, investors: int, businesses: int, average_loan: int|null, average_yield: float|null, average_rating: float|null, average_term: int|null}
     */
    public function traction(): array
    {
        $investors = PulseSignup::query()->investors();
        $businesses = PulseSignup::query()->businesses();
        $preQualified = PulseSignup::query()->preQualified();

        $averageLoan = $this->average($preQualified, 'qualified_amount');
        $averageTerm = $this->average($preQualified, 'term_months');

        return [
            'pledged' => (int) $investors->clone()->sum('pledge_amount'),
            'investors' => $investors->clone()->count(),
            'businesses' => $businesses->clone()->count(),
            'average_loan' => $averageLoan === null ? null : (int) $averageLoan,
            'average_yield' => $this->average($preQualified, 'flat_rate', 1),
            'average_rating' => $this->average($preQualified, 'rating_score', 1),
            'average_term' => $averageTerm === null ? null : (int) $averageTerm,
        ];
    }

    /**
     * @return list<array{id: int, name: string, district: string, listed: bool, term_months: int, flat_rate: float, rating_band: string, rating_score: float}>
     */
    public function preQualifiedListings(int $limit): array
    {
        $listings = PulseSignup::query()
            ->preQualified()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (PulseSignup $signup): array => [
                'id' => (int) $signup->id,
                'name' => (string) $signup->name,
                'district' => (string) $signup->district,
                'listed' => (bool) $signup->listed,
                'term_months' => (int) $signup->term_months,
                'flat_rate' => (float) $signup->flat_rate,
                'rating_band' => (string) $signup->rating_band,
                'rating_score' => (float) $signup->rating_score,
            ])
            ->all();

        return array_values($listings);
    }

    /**
     * @param  array{name: string, contact_method: string, contact: string, province?: string, district?: string, country?: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array{pledge_amount: int, projected_return: int, blended_yield: float}  $investment
     * @return array{queue_number: string}
     */
    public function createInvestor(array $signup, array $investment): array
    {
        $record = $this->newSignup($signup, PulseSignupType::Investor);
        $record->fill($investment)->save();

        return ['queue_number' => (string) $record->queue_number];
    }

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array<string, mixed>  $sizing
     * @return array{queue_number: string, loan_number: string}
     */
    public function createBusiness(array $signup, array $sizing, bool $listed): array
    {
        $record = $this->newSignup($signup, PulseSignupType::Business);
        $record->fill([
            'annual_revenue' => $sizing['annual_revenue'],
            'annual_costs' => $sizing['annual_costs'],
            'sector' => $sizing['sector'],
            'registered_year' => $sizing['registered_year'],
            'score' => $sizing['score'],
            'qualified_amount' => $sizing['qualified_amount'],
            'term_months' => $sizing['term_months'],
            'flat_rate' => $sizing['flat_rate'],
            'rating_band' => $sizing['rating_band'],
            'rating_score' => $sizing['rating_score'],
            'listed' => $listed,
            'loan_number' => '#'.Number::format(PulseSignup::query()->businesses()->count() + 1),
        ])->save();

        return [
            'queue_number' => (string) $record->queue_number,
            'loan_number' => (string) $record->loan_number,
        ];
    }

    /**
     * @param  array{name: string, contact_method: string, contact: string, province: string, district: string, ip_address: string|null, user_agent: string|null}  $signup
     * @param  array{annual_revenue: int, annual_costs: int, term_months: int}  $figures
     * @return array{queue_number: string}
     */
    public function createBusinessLead(array $signup, array $figures): array
    {
        $record = $this->newSignup($signup, PulseSignupType::Business);
        $record->fill($figures)->save();

        return ['queue_number' => (string) $record->queue_number];
    }

    /**
     * @param  Builder<PulseSignup>  $signups
     */
    private function average(Builder $signups, string $column, int $precision = 0): ?float
    {
        $average = $signups->clone()->avg($column);

        return $average === null ? null : round((float) $average, $precision);
    }

    /**
     * @param  array{name: string, contact_method: string, contact: string, province?: string, district?: string, country?: string, ip_address: string|null, user_agent: string|null}  $signup
     */
    private function newSignup(array $signup, PulseSignupType $type): PulseSignup
    {
        return new PulseSignup([
            ...$signup,
            'type' => $type,
            'queue_number' => '#'.str_pad(
                (string) (PulseSignup::query()->where('type', $type)->count() + 1),
                4,
                '0',
                STR_PAD_LEFT,
            ),
        ]);
    }
}
