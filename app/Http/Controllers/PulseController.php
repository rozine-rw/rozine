<?php

namespace App\Http\Controllers;

use App\Enums\PulseSignupType;
use App\Http\Requests\Pulse\StoreBusinessSignupRequest;
use App\Http\Requests\Pulse\StoreInvestorPledgeRequest;
use App\Http\Resources\PulseListingResource;
use App\Models\PulseSignup;
use App\Support\PulseUnderwriting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PulseController extends Controller
{
    /**
     * Show the Pulse waitlist.
     */
    public function index(): Response
    {
        return Inertia::render('pulse', [
            'traction' => $this->traction(),
            'listings' => PulseListingResource::collection(
                PulseSignup::query()
                    ->preQualified()
                    ->latest()
                    ->limit((int) config('pulse.listing_limit'))
                    ->get()
            )->resolve(),
            'districts' => config('rwanda.districts'),
        ]);
    }

    /**
     * Store an investor's pledge intent.
     */
    public function storeInvestor(StoreInvestorPledgeRequest $request): JsonResponse
    {
        $pledgeAmount = (int) $request->validated('pledge_amount');

        $signup = $this->signupFor($request, PulseSignupType::Investor);

        $signup->fill([
            'pledge_amount' => $pledgeAmount,
            'projected_return' => PulseUnderwriting::projectedReturn($pledgeAmount),
            'blended_yield' => PulseUnderwriting::BLENDED_YIELD,
        ])->save();

        return response()->json([
            'queue_number' => $signup->queue_number,
            'traction' => $this->traction(),
        ]);
    }

    /**
     * Store a business' pre-qualification, sized here rather than taken from
     * the browser so the figures recorded are the ones this application worked
     * out from what the business reported.
     */
    public function storeBusiness(StoreBusinessSignupRequest $request): JsonResponse
    {
        $signup = $this->signupFor($request, PulseSignupType::Business);

        $signup->fill([
            ...PulseUnderwriting::size(
                (int) $request->validated('annual_revenue'),
                (int) $request->validated('annual_costs'),
                $request->sector(),
                (int) $request->validated('registered_year'),
                (int) $request->validated('term_months'),
            ),
            'listed' => $request->boolean('listed'),
            'loan_number' => '#'.Number::format(PulseSignup::query()->businesses()->count() + 1),
        ])->save();

        return response()->json([
            'queue_number' => $signup->queue_number,
            'loan_number' => $signup->loan_number,
            'traction' => $this->traction(),
        ]);
    }

    /**
     * Get the traction Pulse reports, which is only ever what the waitlist has
     * actually taken.
     *
     * @return array{pledged: int, investors: int, businesses: int, average_loan: int|null, average_yield: float|null, average_rating: float|null, average_term: int|null}
     */
    private function traction(): array
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
     * Average a column across the businesses that pre-qualified. Nothing sized
     * means there is no average to report, rather than an average of nothing.
     *
     * @param  Builder<PulseSignup>  $signups
     */
    private function average(Builder $signups, string $column, int $precision = 0): ?float
    {
        $average = $signups->clone()->avg($column);

        return $average === null ? null : round((float) $average, $precision);
    }

    /**
     * Start the signup a submission belongs to, holding the next spot in the
     * queue for its side of the waitlist.
     */
    private function signupFor(FormRequest $request, PulseSignupType $type): PulseSignup
    {
        /** @var array<string, string> $validated */
        $validated = $request->validated();

        return new PulseSignup([
            'type' => $type,
            'name' => $validated['name'],
            'contact_method' => $validated['contact_method'],
            'contact' => $validated['contact'],
            'province' => $validated['province'],
            'district' => $validated['district'],
            'queue_number' => '#'.Str::padLeft(
                (string) (PulseSignup::query()->where('type', $type)->count() + 1),
                4,
                '0'
            ),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
