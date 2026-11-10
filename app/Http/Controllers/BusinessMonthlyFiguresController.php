<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetMonthlyFigures;
use App\Application\Business\SubmitMonthlyFigures;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Requests\Business\SubmitMonthlyFiguresRequest;
use App\Http\Resources\BusinessMonthlyFiguresResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The monthly submission (BRS v3.0 VIS-R04), opened from the audit-prep sheet while the monthly
 * audit's window is open. A refusal comes back as form errors on the same page. Web only: the
 * bearer transport has no such command yet.
 */
class BusinessMonthlyFiguresController extends Controller
{
    /** Refusals that are not about one field, in the words the page shows. */
    private const array MESSAGES = [
        'MONTHLY_SUBMISSION_CLOSED' => 'The window for this month has closed, so the figures can no longer be sent.',
        'MONTHLY_SUBMISSION_ALREADY_SENT' => 'This month\'s figures were already sent.',
        'IDEMPOTENCY_CONFLICT' => 'These figures were already sent with different values. Reload the page.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(ShowBusinessHomeRequest $request, GetMonthlyFigures $figures): Response|RedirectResponse
    {
        $business = (string) $request->route('business');
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) ($request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision']);
        $page = $figures->handle($userId, $revision, $business);
        if ($page['window'] === null) {
            return to_route('business.show', ['business' => $business]);
        }

        return Inertia::render('business/monthly-figures', (new BusinessMonthlyFiguresResource($page))->resolve($request));
    }

    public function store(SubmitMonthlyFiguresRequest $request, SubmitMonthlyFigures $action): RedirectResponse
    {
        $business = (string) $request->route('business');
        /** @var array<string, mixed> $input */
        $input = $request->safe()->only(['money_in', 'costs', 'paid_to_owners', 'forecast', 'order', 'changes']);
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'), $business,
            (string) $request->validated('request_id'), $input);
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['form' => [self::MESSAGES[$result['code']] ?? 'We could not send your figures. Try again.']]
                : $fields);
        }

        return to_route('business.monthly-figures.show', ['business' => $business]);
    }
}
