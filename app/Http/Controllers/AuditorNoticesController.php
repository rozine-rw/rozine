<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\ReadPartyNotices;
use App\Http\Requests\Auditor\AuditorNoticesRequest;
use App\Http\Resources\AuditorNoticesResource;
use Illuminate\Http\JsonResponse;

/** The Audit Partner's notices behind the Auditor bell (`auditor.notices`), for the web session and API v1 alike. */
class AuditorNoticesController extends Controller
{
    public function __construct(private ReadPartyNotices $notices) {}

    public function index(AuditorNoticesRequest $request): AuditorNoticesResource
    {
        return new AuditorNoticesResource($this->notices->inbox($this->user($request)));
    }

    public function read(AuditorNoticesRequest $request, string $notice): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->user($request), strtolower($notice))]]);
    }

    public function readAll(AuditorNoticesRequest $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->user($request), null)]]);
    }

    private function user(AuditorNoticesRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
