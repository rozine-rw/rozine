<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Evidence\FindStatementVerificationOperation;
use App\Application\Evidence\ReadAuditStatement;
use App\Application\Evidence\RecordStatementVerification;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\ShowAuditorOperationRequest;
use App\Http\Requests\Auditor\VerifyStatementsRequest;
use App\Http\Resources\AuditorJobsResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The Audit Partner's side of a statement review (#396): each statement on file, opened from the
 * job it was accepted for, and the factual verification that completes the review. A verification
 * is looked up, never resent, when its answer is lost.
 */
class AuditorStatementVerificationController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

    public function original(Request $request, ReadAuditStatement $statements): HttpResponse
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $document = $statements->handle($userId, $this->revision($userId), (string) $request->route('assignment'), (string) $request->route('document'));
        $response = new HttpResponse($document['content'], 200, [
            'Content-Type' => $document['media_type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Repr-Digest' => 'sha-256=:'.base64_encode((string) hex2bin($document['sha256'])).':',
        ]);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', $document['filename']));

        return $response;
    }

    public function store(VerifyStatementsRequest $request, RecordStatementVerification $action): OperationResource
    {
        /** @var array<string, mixed> $review */
        $review = $request->validated('review');

        return $this->present($action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('assignment'), (int) $request->validated('expected_revision'), (int) $request->validated('expected_evidence_revision'),
            (int) $request->validated('expected_verification_revision'), (string) $request->validated('transcription_id'),
            (string) $request->validated('transcription_sha256'), $review, (string) $request->validated('request_id')));
    }

    public function operation(ShowAuditorOperationRequest $request, FindStatementVerificationOperation $action): OperationResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();

        return $this->present($action->handle($userId, $this->revision($userId), (string) $request->route('request_id')));
    }

    private function revision(int $userId): int
    {
        return (int) $this->identity->context($userId, 'auditor')['context_revision'];
    }

    /**
     * A verified review completes the engagement, so the partner goes back to their Jobs.
     *
     * @param  array<string, mixed>  $result
     */
    private function present(array $result): OperationResource
    {
        if ($result['status'] === 'completed') {
            $result['data'] = [...(array) $result['data'], 'next' => AuditorJobsResource::link('auditor.jobs.index')];
        }

        return new OperationResource($result);
    }
}
