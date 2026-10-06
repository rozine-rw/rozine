<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\ReviewInvestorVerifications;
use App\Http\Requests\Staff\DecideInvestorVerificationRequest;
use App\Http\Requests\Staff\ListInvestorVerificationsRequest;
use App\Http\Resources\StaffInvestorVerificationsResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as FileResponse;

/**
 * Compliance's Investor identity review (`investors.verify`): the queue, one case with its private
 * documents, and approve or reject with a reason. A recorded refusal returns as a form error.
 */
class StaffInvestorVerificationController extends Controller
{
    /** Refusals in the words the review shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => 'This case changed since you opened it. Review it again.',
        'VERIFICATION_NOT_SUBMITTED' => 'This case is no longer waiting for review.',
        'VERIFICATION_NOT_FOUND' => 'This case no longer exists.',
        'IDENTITY_RECONCILIATION_REQUIRED' => 'This ID is already verified for another person, or the account needs an identity operator. It cannot be approved here.',
        'VERIFIED_PARTICIPANT_ACCOUNT_REQUIRED' => 'The account needs a verified email and cannot be a staff or operator account.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function __construct(private ReviewInvestorVerifications $review) {}

    public function index(ListInvestorVerificationsRequest $request, GetStaffAccess $access): Response
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $queue = $this->review->queue($actorId, (string) $request->validated('tab', 'submitted'), trim((string) $request->validated('search', '')),
            $request->validated('before'), (int) $request->validated('limit', 25));
        $selected = $request->validated('verification');

        return Inertia::render('admin/investor-verifications', (new StaffInvestorVerificationsResource([...$queue,
            'review' => $selected === null ? null : $this->review->show($actorId, (string) $selected),
            'roles' => $access->handle($actorId)['roles']]))->resolve($request));
    }

    public function document(Request $request): FileResponse
    {
        $document = $this->review->document((int) $request->user()?->getAuthIdentifier(), (string) $request->route('verification'), (string) $request->route('document'));

        return response($document['content'], 200, [
            'Content-Type' => $document['media_type'],
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $document['filename'], Str::ascii($document['filename']) ?: 'document'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function approve(DecideInvestorVerificationRequest $request): RedirectResponse
    {
        return $this->answer($request, $this->review->approve(...$this->command($request)));
    }

    public function reject(DecideInvestorVerificationRequest $request): RedirectResponse
    {
        return $this->answer($request, $this->review->reject(...$this->command($request)));
    }

    /** @return array{int, string, int, string, string} */
    private function command(DecideInvestorVerificationRequest $request): array
    {
        return [(int) $request->user()?->getAuthIdentifier(), (string) $request->route('verification'),
            (int) $request->validated('expected_revision'), (string) $request->validated('reason'), (string) $request->validated('request_id')];
    }

    /** @param array<string, mixed> $result */
    private function answer(Request $request, array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['form' => [self::MESSAGES[$result['code']] ?? 'We could not record this decision. Try again.']]);
        }

        return redirect()->route('staff.investor-verifications.index', ['verification' => (string) $request->route('verification')]);
    }
}
