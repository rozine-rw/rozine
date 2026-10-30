<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Evidence\FindStatementOperation;
use App\Application\Evidence\GetStatementAuditReadiness;
use App\Application\Evidence\GetStatementTranscription;
use App\Application\Evidence\GetStatementTranscriptionDraft;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\SaveStatementTranscriptionRequest;
use App\Http\Resources\OperationResource;
use App\Http\Resources\StatementTranscriptionDraftResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A Business representative's review of their own statements (contract initial-statement-audit-1 §5-6):
 * the draft to review against, the saved transcriptions, the reviewed save, its receipt and the raw
 * readiness for the first independent review. Saving verifies nothing and unlocks no eligibility.
 */
class BusinessStatementTranscriptionController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

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

    public function save(SaveStatementTranscriptionRequest $request, RecordStatementTranscription $action): OperationResource
    {
        $business = (string) $request->route('business');
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'), $business,
            (int) $request->validated('expected_evidence_revision'), $request->rails(), $request->months(), $request->statements(), $request->review(),
            (string) $request->validated('request_id'));
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
