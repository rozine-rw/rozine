<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\FindAuditorOperation;
use App\Application\Auditor\GetAuditEngagementSummary;
use App\Application\Auditor\GetAuditorAccreditation;
use App\Application\Auditor\GetAuditorProfile;
use App\Application\Auditor\ReadAuditorCertificate;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Auditor\WithdrawAuditorAccreditation;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\AuditorCommandRequest;
use App\Http\Requests\Auditor\ShowAuditorOperationRequest;
use App\Http\Requests\Auditor\SubmitAccreditationRequest;
use App\Http\Requests\Auditor\UpdateAvailabilityRequest;
use App\Http\Requests\Auditor\WithdrawAccreditationRequest;
use App\Http\Resources\AuditorProfileResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * The Auditor Profile: accreditation and dispatch availability. Every read and command goes
 * through the protected Auditor actions, which authorize the current Auditor role and its
 * identity context inside their own transaction; this controller only carries the request.
 */
class AuditorProfileController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity, private GetAuditorAccreditation $accreditation, private GetAuditEngagementSummary $engagements) {}

    public function show(Request $request, GetAuditorProfile $profile): Response|AuditorProfileResource
    {
        $this->requireToken($request, 'auditor:read');
        $userId = $this->userId($request);
        $revision = $this->contextRevision($userId);
        $facts = $this->accreditation->handle($userId, $revision);
        $record = $profile->handle($userId, $revision);
        $section = $request->query('section');
        $resource = new AuditorProfileResource([
            'accreditation' => $facts,
            // The certificate on record is linked only when both reads saw the same revision.
            'certificate_id' => $record['revision'] === $facts['accreditation']['revision'] ? $record['state']['certificate_id'] : null,
            'name' => (string) $request->user()?->getAttribute('name'),
            'section' => is_string($section) ? $section : '',
            'engagement' => $this->engagements->handle($userId, $revision),
        ]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/profile', $resource->resolve($request));
    }

    public function submit(SubmitAccreditationRequest $request, SubmitAuditorAccreditation $action): OperationResource
    {
        return $this->submission($request, $action, false);
    }

    public function renew(SubmitAccreditationRequest $request, SubmitAuditorAccreditation $action): OperationResource
    {
        return $this->submission($request, $action, true);
    }

    public function withdraw(WithdrawAccreditationRequest $request, WithdrawAuditorAccreditation $action): OperationResource
    {
        [$userId, $revision] = $this->envelope($request);

        return $this->present($action->handle($userId, $revision, (int) $request->validated('expected_revision'),
            (string) $request->validated('submission_id'), (string) $request->validated('request_id')), $userId, $revision, 'accreditation');
    }

    public function availability(UpdateAvailabilityRequest $request, SetAuditorAvailability $action): OperationResource
    {
        [$userId, $revision] = $this->envelope($request);

        return $this->present($action->handle($userId, $revision, (int) $request->validated('expected_revision'),
            $request->boolean('accepting'), (string) $request->validated('request_id')), $userId, $revision, 'availability');
    }

    /** Replays the recorded outcome of a command, under the current identity context. */
    public function operation(ShowAuditorOperationRequest $request, FindAuditorOperation $action): OperationResource
    {
        $userId = $this->userId($request);
        $revision = $this->contextRevision($userId);
        $command = (string) $request->validated('command');

        return $this->present($action->handle($userId, $revision, $command, (string) $request->route('request_id')),
            $userId, $revision, $command === 'availability.update' ? 'availability' : 'accreditation');
    }

    /** The Auditor's own certificate, private and never cached, under a neutral filename. */
    public function certificate(Request $request, ReadAuditorCertificate $action): HttpResponse
    {
        $this->requireToken($request, 'auditor:read');
        $userId = $this->userId($request);
        $context = $this->identity->context($userId, 'auditor');
        /** @var array{id: string} $party */
        $party = $context['party'];
        $certificateId = (string) $request->route('certificate');
        $download = $action->handle($userId, (int) $context['context_revision'], $party['id'], $certificateId);
        $extension = match ($download['media_type']) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            default => 'jpg',
        };
        $response = new HttpResponse($download['content'], 200, [
            'Content-Type' => $download['media_type'],
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Repr-Digest' => 'sha-256=:'.base64_encode((string) hex2bin($download['sha256'])).':',
        ]);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', "licence-certificate-{$certificateId}.{$extension}"));

        return $response;
    }

    private function submission(SubmitAccreditationRequest $request, SubmitAuditorAccreditation $action, bool $renew): OperationResource
    {
        [$userId, $revision] = $this->envelope($request);
        /** @var UploadedFile $certificate */
        $certificate = $request->validated('certificate');

        return $this->present($action->handle($userId, $revision, (int) $request->validated('expected_revision'),
            (string) $request->validated('licence'), (string) $request->validated('expires_on'),
            $certificate->getClientOriginalName(), (string) $certificate->get(), (string) $request->validated('request_id'), $renew),
            $userId, $revision, 'accreditation');
    }

    /**
     * A recorded outcome as the shared operation Resource. A completed command names the page to
     * continue to, and `allowed_actions` is read afresh: a replayed receipt never supplies facts.
     *
     * @param  array<string, mixed>  $result
     */
    private function present(array $result, int $userId, int $revision, string $section): OperationResource
    {
        if ($result['status'] === 'completed') {
            $result['data'] = [...(array) $result['data'], 'next' => AuditorProfileResource::next($section)];
        }
        $result['allowed_actions'] = $this->accreditation->handle($userId, $revision)['allowed_actions'];

        return new OperationResource($result);
    }

    /** @return array{int, int} */
    private function envelope(AuditorCommandRequest $request): array
    {
        return [$this->userId($request), (int) $request->validated('identity_context_revision')];
    }

    private function contextRevision(int $userId): int
    {
        return (int) $this->identity->context($userId, 'auditor')['context_revision'];
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function requireToken(Request $request, string $ability): void
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan($ability), 403);
    }
}
