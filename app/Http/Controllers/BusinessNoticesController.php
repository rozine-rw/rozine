<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\ReadBusinessNotices;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\BusinessNoticesRequest;
use App\Http\Resources\BusinessNoticesResource;
use Illuminate\Http\JsonResponse;

/** A Business's notices behind its bell (`business.notices`), for the web session and API v1 alike. */
class BusinessNoticesController extends Controller
{
    public function __construct(private ReadBusinessNotices $notices, private AuthorizeActiveRole $identity) {}

    public function index(BusinessNoticesRequest $request, string $business): BusinessNoticesResource
    {
        return new BusinessNoticesResource([...$this->notices->inbox($this->userId($request), $this->revision($request), $business), 'business_id' => $business]);
    }

    public function read(BusinessNoticesRequest $request, string $business, string $notice): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->userId($request), $this->revision($request), $business, strtolower($notice))]]);
    }

    public function readAll(BusinessNoticesRequest $request, string $business): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->notices->markRead($this->userId($request), $this->revision($request), $business, null)]]);
    }

    private function userId(BusinessNoticesRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(BusinessNoticesRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision') ?? $this->identity->context($this->userId($request), 'business')['context_revision']);
    }
}
