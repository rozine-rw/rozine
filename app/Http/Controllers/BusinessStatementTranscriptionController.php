<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessHome;
use App\Application\Evidence\FindStatementOperation;
use App\Application\Evidence\GetStatementAuditReadiness;
use App\Application\Evidence\GetStatementEvidence;
use App\Application\Evidence\GetStatementTranscription;
use App\Application\Evidence\GetStatementTranscriptionDraft;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\SaveStatementTranscriptionRequest;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Resources\BusinessHomeResource;
use App\Http\Resources\BusinessStatementsResource;
use App\Http\Resources\OperationResource;
use App\Http\Resources\StatementTranscriptionDraftResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A Business representative's review of their own statements (contract initial-statement-audit-1 §5-6):
 * the draft to review against, the saved transcriptions, the reviewed save, its receipt and the raw
 * readiness for the first independent review. Saving verifies nothing and unlocks no eligibility.
 * The web page is the same review as a form: it posts the same save and is drawn again with the
 * saved figures, or shows a refusal in the words below.
 */
class BusinessStatementTranscriptionController extends Controller
{
    /** Refusals in the words the page shows; the codes are the reconciliation and review preconditions. */
    private const MESSAGES = [
        'STATEMENT_INVENTORY_REQUIRED' => 'Add at least one account and choose the months your statements cover (36 at most).',
        'STATEMENT_MONTH_GAP' => 'The months must follow on from each other with no gap.',
        'STATEMENT_MONTH_INVALID' => 'One of the months is not a valid month.',
        'STATEMENT_RAIL_CONFLICT' => 'Two accounts have the same name, or an account closes before it opens.',
        'STATEMENT_COVERAGE_CONFLICT' => 'A month was entered for an account that was not open then, or entered twice.',
        'STATEMENT_RAIL_MONTH_REQUIRED' => 'Every account needs figures for every month it was open.',
        'STATEMENT_RAIL_BOUNDARY_BALANCE_REQUIRED' => 'An account must start at 0 in the month it opened and end at 0 in the month it closed.',
        'STATEMENT_BALANCE_CONTINUITY_REQUIRED' => 'Each month must start with the balance the month before ended with.',
        'STATEMENT_REFERENCE_INVALID' => 'Account names and references may use letters, numbers, - and _ only.',
        'STATEMENT_TRANSACTION_DATE_INVALID' => 'Each transaction date must fall in the month it is entered under.',
        'STATEMENT_DUPLICATE_CONFLICT' => 'Two transactions in the same month share a reference. Give each its own.',
        'STATEMENT_CLASSIFICATION_REQUIRED' => 'A transaction type does not match its direction. Check money in and money out.',
        'STATEMENT_AMOUNT_INVALID' => 'Amounts must be whole Rwandan francs.',
        'STATEMENT_TRANSFER_COUNTERPART_REQUIRED' => 'Each transfer between your accounts must be matched with the same amount on the other account.',
        'OWNER_RETURN_REFERENCE_REQUIRED' => 'Money an owner put back must be linked to the owner withdrawal it repays.',
        'OWNER_RETURN_EXCEEDS_DRAW' => 'An owner put back more than they withdrew.',
        'DRAW_EXCEPTION_REVIEW_REQUIRED' => 'An owner withdrawal needs review by our team.',
        'STATEMENT_RECONCILIATION_DIFFERENCE' => 'A month does not add up: opening balance plus transactions must equal the closing balance.',
        'STATEMENT_ORIGINAL_REQUIRED' => 'Pick the uploaded statement each month comes from.',
        'STATEMENT_REVIEW_REQUIRED' => 'Confirm you checked these figures against your original statements.',
        'STATEMENT_SOURCE_CHANGED' => 'Your statements changed while you were entering figures. Reload the page and check again.',
        'STATEMENT_TRANSCRIPTION_STALE' => 'These figures were saved again in the meantime. Reload the page and check again.',
        'VERSION_CONFLICT' => 'Your statements changed while you were entering figures. Reload the page and check again.',
        'BUSINESS_STALE' => 'Your business details changed in the meantime. Reload the page and check again.',
        'MANDATE_STALE' => 'Your business details changed in the meantime. Reload the page and check again.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    /**
     * The form a representative fills from their uploaded statements, drawn over the business's Home
     * with the statements to pick sources from and what was saved before.
     */
    public function page(ShowBusinessHomeRequest $request, GetStatementTranscriptionDraft $drafts, GetStatementEvidence $statements, GetBusinessHome $home): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) ($request->validated('identity_context_revision') ?? $this->context($userId));
        $business = (string) $request->route('business');
        $draft = (new StatementTranscriptionDraftResource($drafts->handle($userId, $revision, $business)))->resolve($request);
        $homeProps = (new BusinessHomeResource($home->handle($userId, $revision, $business)))->toArray($request);

        return Inertia::render('business/statement-transcription', ['draft' => $draft, 'identity_context_revision' => $revision,
            'statements' => (new BusinessStatementsResource(['business_id' => $business, 'manifest' => $statements->handle($userId, $revision, $business)]))->toArray($request),
            'home' => $homeProps, 'links' => ['close' => $homeProps['links']['apply'] ?? $homeProps['links']['home']]]);
    }

    public function draft(Request $request, GetStatementTranscriptionDraft $drafts): StatementTranscriptionDraftResource
    {
        $userId = $this->reader($request);

        return new StatementTranscriptionDraftResource($drafts->handle($userId, $this->context($userId), (string) $request->route('business')));
    }

    public function show(Request $request, GetStatementTranscription $transcriptions): JsonResponse
    {
        $userId = $this->reader($request);
        $id = $request->query('transcription');
        abort_if($id !== null && (! is_string($id) || preg_match('/^[0-9a-z]{26}$/D', $id) !== 1), 404);

        return new JsonResponse(['data' => $transcriptions->handle($userId, $this->context($userId), (string) $request->route('business'), $id)]);
    }

    public function save(SaveStatementTranscriptionRequest $request, RecordStatementTranscription $action): OperationResource|RedirectResponse
    {
        $business = (string) $request->route('business');
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'), $business,
            (int) $request->validated('expected_evidence_revision'), $request->rails(), $request->months(), $request->statements(), $request->review(),
            (string) $request->validated('request_id'));
        if (! $request->routeIs('api.*')) {
            return $this->redirect($result);
        }
        if ($result['status'] === 'completed') {
            $result['data'] = [...(array) $result['data'],
                'next' => ['url' => route('api.v1.business.statements.readiness', ['business' => $business], false), 'method' => 'get']];
        }

        return new OperationResource($result);
    }

    public function operation(Request $request, FindStatementOperation $operations): OperationResource
    {
        $userId = $this->reader($request);
        $command = $request->query('command', 'reconcile');

        return new OperationResource($operations->handle($userId, $this->context($userId), (string) $request->route('request_id'),
            is_string($command) ? $command : '', (string) $request->route('business')));
    }

    public function readiness(Request $request, GetStatementAuditReadiness $readiness): JsonResponse
    {
        $userId = $this->reader($request);

        return new JsonResponse(['data' => $readiness->handle($userId, $this->context($userId), (string) $request->route('business'))]);
    }

    /**
     * The web save: the page is drawn again with the saved figures, or the refusal is shown on the form.
     *
     * @param  array<string, mixed>  $result
     */
    private function redirect(array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            $code = is_string($result['code'] ?? null) ? $result['code'] : '';

            throw ValidationException::withMessages(['transcription' => [self::MESSAGES[$code] ?? 'We could not save these figures. Try again.'], ...$fields]);
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Figures saved. An Audit Partner still has to verify them against your statements.')]);

        return redirect()->back();
    }

    private function reader(Request $request): int
    {
        $user = $request->user();
        abort_if($user === null || ! $user->tokenCan('business:read'), 403);

        return (int) $user->getAuthIdentifier();
    }

    private function context(int $userId): int
    {
        return $this->identity->context($userId, 'business')['context_revision'];
    }
}
