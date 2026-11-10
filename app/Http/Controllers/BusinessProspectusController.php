<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessProspectus;
use App\Application\Business\SaveBusinessProspectus;
use App\Application\Business\SignBusinessProspectus;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\SaveBusinessProspectusRequest;
use App\Http\Requests\Business\ShowApplicationRequest;
use App\Http\Requests\Business\SignBusinessProspectusRequest;
use App\Http\Resources\BusinessProspectusResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Listing Builder (BRS v3.0 §19): the Business writes its prospectus, sees every gate with its
 * fix, and the official representative signs. Each save and the signature return to the builder; a
 * refusal comes back as field errors, and refused gates under `declaration`.
 */
class BusinessProspectusController extends Controller
{
    /** @var array<string, string> */
    private const array MESSAGES = [
        'ACTION_FORBIDDEN' => 'Only the official representative can sign the prospectus.',
        'MANDATE_REQUIRED' => 'You do not have permission to change this prospectus.',
        'IDEMPOTENCY_CONFLICT' => 'This save was already sent with different details. Try again.',
        'VERSION_CONFLICT' => 'The prospectus changed since you opened it. Reload to see the latest version.',
        'PROSPECTUS_SIGNED' => 'The prospectus is signed and locked.',
        'PROSPECTUS_DRAFT_REQUIRED' => 'Save the prospectus before signing it.',
        'PROSPECTUS_GATES_FAILED' => 'Fix every check before signing.',
        'REPORT_NOT_CURRENT' => 'Your CPA report is not current, so the prospectus cannot be signed yet.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(ShowApplicationRequest $request, GetBusinessProspectus $prospectus): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) ($request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision']);
        $resource = new BusinessProspectusResource($prospectus->handle($userId, $revision, (string) $request->route('business'), (string) $request->route('application')));

        return Inertia::render('business/prospectus', $resource->resolve($request));
    }

    public function save(SaveBusinessProspectusRequest $request, SaveBusinessProspectus $action): RedirectResponse
    {
        $result = $action->handle($this->user($request), $this->revision($request), (string) $request->route('business'), (string) $request->route('application'),
            (int) $request->validated('expected_version'), $request->safe()->except(['request_id', 'identity_context_revision', 'expected_version']),
            (string) $request->validated('request_id'));

        return $this->back($request, $result, 'purpose');
    }

    public function sign(SignBusinessProspectusRequest $request, SignBusinessProspectus $action): RedirectResponse
    {
        $result = $action->handle($this->user($request), $this->revision($request), (string) $request->route('business'), (string) $request->route('application'),
            (int) $request->validated('expected_version'), (string) $request->validated('request_id'));

        return $this->back($request, $result, 'declaration');
    }

    /** @param array<string, mixed> $result */
    private function back(Request $request, array $result, string $field): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? [$field => [self::MESSAGES[$result['code']] ?? 'We could not save the prospectus. Try again.']]
                : $fields);
        }

        return redirect()->route('business.applications.prospectus.show', ['business' => $request->route('business'), 'application' => $request->route('application')]);
    }

    private function user(Request $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(Request $request): int
    {
        return $request->has('identity_context_revision') ? $request->integer('identity_context_revision')
            : (int) $this->identity->context($this->user($request), 'business')['context_revision'];
    }
}
