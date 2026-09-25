<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\EvaluateBusinessApplication;
use App\Application\Business\FindBusinessOperation;
use App\Application\Business\GetBusinessApplicationPage;
use App\Application\Business\ProjectBusinessApplicationOperation;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Business\SubmitBusinessApplication;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\CreateApplicationRequest;
use App\Http\Requests\Business\EvaluateApplicationRequest;
use App\Http\Requests\Business\SaveApplicationRequest;
use App\Http\Requests\Business\ShowApplicationOperationRequest;
use App\Http\Requests\Business\ShowApplicationRequest;
use App\Http\Requests\Business\SubmitApplicationRequest;
use App\Http\Resources\BusinessApplicationResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessApplicationController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity, private ProjectBusinessApplicationOperation $outcomes) {}

    public function show(ShowApplicationRequest $request, GetBusinessApplicationPage $page): Response|BusinessApplicationResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = $request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision'];
        $resource = new BusinessApplicationResource($page->handle($userId, (int) $revision, (string) $request->route('business'), (string) $request->route('application')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/apply', $resource->resolve($request));
    }

    public function create(CreateApplicationRequest $request, CreateBusinessApplication $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (int) $request->validated('expected_revision'), (string) $request->validated('request_id'));

        return $this->present($request, $result);
    }

    public function save(SaveApplicationRequest $request, SaveBusinessApplication $action): OperationResource
    {
        $term = $request->validated('term_months');
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('application'), (int) $request->validated('expected_revision'),
            ['title' => (string) $request->validated('title'), 'target' => $request->validated('target'), 'term_months' => $term === null ? null : (int) $term,
                'use_of_funds' => $request->validated('use_of_funds'), 'story' => (string) $request->validated('story')],
            $request->validated('step'), (string) $request->validated('request_id'));

        return $this->present($request, $result);
    }

    public function evaluate(EvaluateApplicationRequest $request, EvaluateBusinessApplication $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('application'), (int) $request->validated('expected_revision'),
            $request->validated('accepted_principal'), (string) $request->validated('request_id'),
            ['target' => (string) $request->validated('target'), 'term_months' => (int) $request->validated('term_months'),
                'evidence_version' => (string) $request->validated('evidence_version')]);

        return $this->present($request, $result);
    }

    public function submit(SubmitApplicationRequest $request, SubmitBusinessApplication $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('application'), (int) $request->validated('expected_revision'),
            [...$request->safe()->except(['identity_context_revision', 'expected_revision', 'request_id']),
                'quote_revision' => (int) $request->validated('quote_revision'), 'signature_name' => (string) $request->validated('signature_name'),
                'terms' => $request->boolean('terms'), 'privacy' => $request->boolean('privacy')], (string) $request->validated('request_id'));

        return $this->present($request, $result);
    }

    public function operation(ShowApplicationOperationRequest $request, FindBusinessOperation $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->validated('command'), (string) $request->route('request_id'));

        return $this->present($request, $result);
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        $result = $this->outcomes->handle((int) $request->user()?->getAuthIdentifier(), $request->integer('identity_context_revision'), $result);
        if ($result['status'] === 'completed') {
            $result['data'] = BusinessApplicationResource::commandData($request, $result['data']);
        }
        if ($request->routeIs('api.*') && ! $request->user()?->tokenCan('business:command')) {
            $result['allowed_actions'] = [];
        }

        return new OperationResource($result);
    }
}
