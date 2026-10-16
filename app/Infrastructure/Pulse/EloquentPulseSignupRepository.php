<?php

declare(strict_types=1);

namespace App\Infrastructure\Pulse;

use App\Application\Pulse\Contracts\PulseSignupRepository;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
            'loan_number' => '#'.Number::format(
                $this->claimNext('loan_number', fn (): int => $this->highestIssued('loan_number', null)),
            ),
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
     * Claim the next value in a numbering series.
     *
     * The counter row is locked for the life of the transaction, so a second
     * caller arriving mid-claim waits for the first to commit rather than
     * reading the same value. Counting rows instead - the previous approach -
     * gave concurrent signups identical numbers and reused a number whenever a
     * row was deleted.
     *
     * The claim never undercuts a number already in the table. A signup's
     * number is its place in the queue, so a row that arrived without going
     * through the counter (a seed, an import, a factory) still has to count.
     *
     * @param  callable(): int  $highestIssued
     */
    private function claimNext(string $series, callable $highestIssued): int
    {
        return DB::transaction(function () use ($series, $highestIssued): int {
            DB::table('signup_counters')->insertOrIgnore(['name' => $series, 'value' => 0]);

            $counter = (int) DB::table('signup_counters')
                ->where('name', $series)
                ->lockForUpdate()
                ->value('value');

            $claimed = max($counter, $highestIssued()) + 1;

            DB::table('signup_counters')->where('name', $series)->update(['value' => $claimed]);

            return $claimed;
        });
    }

    /**
     * The highest number handed out in a series so far.
     *
     * Stored numbers are display strings such as `#0007` and `#1,204`, so the
     * value is read back through the digits. The scan is proportional to the
     * waitlist, which is the right trade while it is a waitlist; a numeric
     * column would replace it if these series ever grow into the platform.
     */
    private function highestIssued(string $column, ?PulseSignupType $type): int
    {
        $issued = PulseSignup::query()
            ->whereNotNull($column)
            ->when($type !== null, fn (Builder $signups) => $signups->where('type', $type))
            ->pluck($column);

        $highest = 0;

        foreach ($issued as $value) {
            $highest = max($highest, (int) preg_replace('/\D/', '', (string) $value));
        }

        return $highest;
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
                (string) $this->claimNext(
                    'queue_number:'.$type->value,
                    fn (): int => $this->highestIssued('queue_number', $type),
                ),
                4,
                '0',
                STR_PAD_LEFT,
            ),
        ]);
    }
}
