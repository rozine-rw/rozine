<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessAcademy;
use App\Application\Business\SubmitAcademyTest;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Requests\Business\SubmitAcademyTestRequest;
use App\Http\Resources\BusinessAcademyModuleResource;
use App\Http\Resources\BusinessAcademyResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Business Academy (BRS v3.0 RDY-R07), opened from the Readiness tab: the stage's core modules,
 * each module's flashcards and test, scored on the server. A refusal comes back as form errors on
 * the module page. Web only: the bearer transport has no such read or command yet.
 */
class BusinessAcademyController extends Controller
{
    /** Refusals that are not about one answer, in the words the page shows. */
    private const array MESSAGES = [
        'ACADEMY_ALREADY_PASSED' => 'You have already passed this module.',
        'ACADEMY_RETAKE_WAITING' => 'You can retake this test 24 hours after your last attempt.',
        'ACADEMY_ATTEMPT_STALE' => 'This test was already answered. Reload the page for your next attempt.',
        'IDEMPOTENCY_CONFLICT' => 'These answers were already sent with different values. Reload the page.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function index(ShowBusinessHomeRequest $request, GetBusinessAcademy $academy): Response
    {
        $course = $academy->course($this->userId($request), $this->revision($request), (string) $request->route('business'));

        return Inertia::render('business/academy', (new BusinessAcademyResource($course))->resolve($request));
    }

    public function show(ShowBusinessHomeRequest $request, GetBusinessAcademy $academy): Response
    {
        $lesson = $academy->lesson($this->userId($request), $this->revision($request), (string) $request->route('business'), (string) $request->route('module'));

        return Inertia::render('business/academy-module', (new BusinessAcademyModuleResource($lesson))->resolve($request));
    }

    public function store(SubmitAcademyTestRequest $request, SubmitAcademyTest $action): RedirectResponse
    {
        $business = (string) $request->route('business');
        $module = (string) $request->route('module');
        /** @var array<array-key, mixed> $answers */
        $answers = $request->validated('answers');
        $result = $action->handle($this->userId($request), (int) $request->validated('identity_context_revision'), $business, $module,
            (string) $request->validated('request_id'), ['attempt' => (int) $request->validated('attempt'), 'answers' => $answers]);
        abort_if(($result['code'] ?? null) === 'ACADEMY_MODULE_NOT_FOUND', 404);
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['form' => [self::MESSAGES[$result['code']] ?? 'We could not send your answers. Try again.']]
                : $fields);
        }

        return to_route('business.academy.module', ['business' => $business, 'module' => $module]);
    }

    private function userId(ShowBusinessHomeRequest|SubmitAcademyTestRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(ShowBusinessHomeRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision') ?? $this->identity->context($this->userId($request), 'business')['context_revision']);
    }
}
