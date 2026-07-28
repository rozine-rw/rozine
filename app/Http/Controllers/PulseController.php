<?php

namespace App\Http\Controllers;

use App\Enums\PulseSignupType;
use App\Http\Requests\Pulse\StoreBusinessSignupRequest;
use App\Http\Requests\Pulse\StoreInvestorPledgeRequest;
use App\Http\Requests\Pulse\StoreStatementRequest;
use App\Http\Resources\PulseListingResource;
use App\Models\PulseSignup;
use App\Support\PulseUnderwriting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                    ->businesses()
                    ->whereNotNull('qualified_amount')
                    ->latest()
                    ->limit((int) config('pulse.listing_limit'))
                    ->get()
            )->resolve(),
            'districts' => config('rwanda.districts'),
        ]);
    }

    /**
     * Store a statement and report the cash flow read off it.
     */
    public function storeStatement(StoreStatementRequest $request): JsonResponse
    {
        $path = Storage::disk('local')->putFile('pulse-statements', $request->file('statement'));

        if ($path === false) {
            throw ValidationException::withMessages([
                'statement' => 'We could not save that statement. Please try again.',
            ]);
        }

        $annualInflow = PulseUnderwriting::estimateAnnualInflow();

        $this->rememberParsedStatement($request, $path, $annualInflow);

        return response()->json([
            'statement_path' => $path,
            'annual_inflow' => $annualInflow,
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
     * Store a business' pre-qualification.
     */
    public function storeBusiness(StoreBusinessSignupRequest $request): JsonResponse
    {
        $signup = $this->signupFor($request, PulseSignupType::Business);

        $signup->fill([
            ...PulseUnderwriting::size($request->annualInflow(), (int) $request->validated('term_months')),
            'statement_path' => $request->validated('statement_path'),
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
     * @return array{pledged: int, investors: int, businesses: int, average_loan: int|null, average_yield: float|null, average_rating: float|null}
     */
    private function traction(): array
    {
        $investors = PulseSignup::query()->investors();
        $businesses = PulseSignup::query()->businesses();

        $averageLoan = $this->average($businesses, 'qualified_amount');
        $averageYield = $this->average($businesses, 'flat_rate', 1);
        $averageRating = $this->average($businesses, 'rating_score', 1);

        return [
            'pledged' => (int) $investors->clone()->sum('pledge_amount'),
            'investors' => $investors->clone()->count(),
            'businesses' => $businesses->clone()->count(),
            'average_loan' => $averageLoan === null ? null : (int) $averageLoan,
            'average_yield' => $averageYield,
            'average_rating' => $averageRating,
        ];
    }

    /**
     * Average a column across the businesses sized so far. Nothing sized means
     * there is no average to report, rather than an average of nothing.
     *
     * @param  Builder<PulseSignup>  $businesses
     */
    private function average(Builder $businesses, string $column, int $precision = 0): ?float
    {
        $average = $businesses->clone()->avg($column);

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

    /**
     * Remember the inflow read off a statement so the pre-qualification that
     * follows is sized from a figure this application produced.
     */
    private function rememberParsedStatement(Request $request, string $path, int $annualInflow): void
    {
        /** @var array<string, int> $statements */
        $statements = $request->session()->get('pulse.statements', []);

        $statements[$path] = $annualInflow;

        $request->session()->put('pulse.statements', $statements);
    }
}
