<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\ReadPartyNotices;
use App\Http\Requests\Investor\NoticesRequest;
use App\Http\Resources\InvestorNoticesResource;
use Illuminate\Http\JsonResponse;

/** The Investor's notices behind the bell (`investor.notices`), for the web session and API v1 alike. */
class InvestorNoticesController extends Controller
{
    public function __construct(private ReadPartyNotices $notices) {}

    public function index(NoticesRequest $request): InvestorNoticesResource
    {
        return new InvestorNoticesResource($this->notices->inbox($this->user($request)));
    }

    public function read(NoticesRequest $request, string $notice): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->user($request), strtolower($notice))]]);
    }

    public function readAll(NoticesRequest $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->user($request), null)]]);
    }

    private function user(NoticesRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
