<?php

namespace App\Http\Controllers;

use App\Application\Pulse\GetPulsePage;
use App\Application\Pulse\PreviewPulseBusiness;
use App\Application\Pulse\PreviewPulseInvestor;
use App\Application\Pulse\RegisterPulseBusiness;
use App\Application\Pulse\RegisterPulseInvestor;
use App\Http\Requests\Pulse\PreviewBusinessRequest;
use App\Http\Requests\Pulse\PreviewInvestorRequest;
use App\Http\Requests\Pulse\StoreBusinessSignupRequest;
use App\Http\Requests\Pulse\StoreInvestorPledgeRequest;
use App\Http\Resources\PulseBusinessPreviewResource;
use App\Http\Resources\PulseBusinessSignupReceiptResource;
use App\Http\Resources\PulseInvestorPreviewResource;
use App\Http\Resources\PulseInvestorSignupReceiptResource;
use App\Http\Resources\PulsePageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PulseController extends Controller
{
    public function index(Request $request, GetPulsePage $action): Response
    {
        /** @var array<string, list<string>> $districts */
        $districts = config('rwanda.districts');

        $page = $action->handle(
            $districts,
            (int) config('pulse.listing_limit'),
            (int) now()->year,
        );

        return Inertia::render('pulse', (new PulsePageResource($page))->resolve($request));
    }

    public function previewInvestor(
        PreviewInvestorRequest $request,
        PreviewPulseInvestor $action,
    ): JsonResponse {
        $preview = $action->handle(
            (int) $request->validated('pledge_amount'),
            (int) config('pulse.listing_limit'),
        );

        return response()->json((new PulseInvestorPreviewResource($preview))->resolve($request));
    }

    public function previewBusiness(
        PreviewBusinessRequest $request,
        PreviewPulseBusiness $action,
    ): JsonResponse {
        $preview = $action->handle(
            (int) $request->validated('annual_revenue'),
            (int) $request->validated('annual_costs'),
            $request->sector(),
            (int) $request->validated('registered_year'),
            (int) $request->validated('term_months'),
            (int) now()->year,
        );

        return response()->json((new PulseBusinessPreviewResource($preview))->resolve($request));
    }

    public function storeInvestor(
        StoreInvestorPledgeRequest $request,
        RegisterPulseInvestor $action,
    ): JsonResponse {
        $receipt = $action->handle(
            [
                'name' => (string) $request->validated('name'),
                'contact_method' => (string) $request->validated('contact_method'),
                'contact' => (string) $request->validated('contact'),
                'province' => (string) $request->validated('province'),
                'district' => (string) $request->validated('district'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            (int) $request->validated('pledge_amount'),
            (int) config('pulse.listing_limit'),
        );

        return response()->json((new PulseInvestorSignupReceiptResource($receipt))->resolve($request));
    }

    public function storeBusiness(
        StoreBusinessSignupRequest $request,
        RegisterPulseBusiness $action,
    ): JsonResponse {
        $receipt = $action->handle(
            [
                'name' => (string) $request->validated('name'),
                'contact_method' => (string) $request->validated('contact_method'),
                'contact' => (string) $request->validated('contact'),
                'province' => (string) $request->validated('province'),
                'district' => (string) $request->validated('district'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            (int) $request->validated('annual_revenue'),
            (int) $request->validated('annual_costs'),
            $request->sector(),
            (int) $request->validated('registered_year'),
            (int) $request->validated('term_months'),
            $request->boolean('listed'),
            (int) now()->year,
        );

        return response()->json((new PulseBusinessSignupReceiptResource($receipt))->resolve($request));
    }
}
