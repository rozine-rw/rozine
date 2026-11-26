<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\OnboardBusiness;
use App\Application\Identity\ApplyForRole;
use App\Http\Requests\Identity\SaveRoleApplicationRequest;
use App\Http\Requests\Identity\SubmitRoleApplicationRequest;
use App\Http\Requests\Identity\UploadRoleApplicationDocumentRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Switching from Investor to Business representative or Auditor (Erastus, 23 Nov 2026): everyone starts
 * as an Investor, and once their own KYC is sent they may apply for one other role here without waiting
 * for its approval. Each command carries its `request_id`, context and expected revision, and a recorded
 * refusal comes back as form errors on the same page.
 */
class RoleApplicationController extends Controller
{
    /** Refusals that are not about one field, in the words the page shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => 'This form changed in another window. Check your answers and try again.',
        'APPLICATION_NOT_EDITABLE' => 'Your application is with our Compliance team, so it can no longer be changed.',
        'APPLICATION_STEP_OUT_OF_ORDER' => 'Finish the earlier steps first.',
        'KYC_NOT_SENT' => 'Send your own identity check (KYC) first. You can continue here straight after.',
        'ROLE_ALREADY_HELD' => 'Your account already has a Business or Auditor role.',
        'TWO_FACTOR_REQUIRED' => 'Turn on two-factor authentication in your security settings before you send your Auditor application.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function show(Request $request, ApplyForRole $applications): Response|RedirectResponse
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $standing = $applications->standing($userId);
        // An approved institution leaves the person an Investor, so the approval itself closes this page.
        if (array_intersect(['business', 'auditor'], $standing['roles']) !== [] || $standing['status'] === 'approved') {
            return redirect()->route('dashboard');
        }
        if (! in_array($standing['kyc'], ['submitted', 'approved'], true)) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Send your own identity check (KYC) first. You can continue straight after, without waiting for approval.')]);

            return redirect()->route('investor.verification');
        }
        $chosen = in_array($request->query('for'), ['business', 'auditor', 'institution'], true) ? (string) $request->query('for') : null;
        $editable = in_array($standing['status'], ['none', 'draft', 'changes_requested', 'rejected'], true);
        $role = $editable && $chosen !== null ? $chosen : $standing['role'];

        return Inertia::render('role/apply', [
            'role' => $role,
            'application' => [
                'status' => $standing['status'], 'revision' => $standing['revision'], 'role' => $standing['role'],
                'step' => $standing['state']['step'] ?? null, 'answers' => $standing['state']['answers'] ?? null, 'note' => $standing['state']['note'] ?? null,
                // The answers and documents Compliance flagged when it asked for more, shown on the form until it is sent again.
                'requested_changes' => $standing['status'] === 'changes_requested' ? ($standing['state']['revision'] ?? null) : null,
                'uploads' => $applications->uploads($userId),
            ],
            'kyc' => $standing['kyc'],
            'blockers' => $standing['blockers'],
            'two_factor' => $request->user()?->hasEnabledTwoFactorAuthentication() === true,
            'identity_context_revision' => $standing['identity_context_revision'],
            'catalog' => [...$applications->institutionCatalog(), 'industries' => OnboardBusiness::sectors(), 'districts' => array_merge(...array_values((array) config('rwanda.districts', []))), 'provinces' => (array) config('rwanda.districts', [])],
        ]);
    }

    /** The person's own upload in one slot, shown back to them in place, sandboxed like their KYC documents. */
    public function document(Request $request, ApplyForRole $applications): SymfonyResponse
    {
        $document = $applications->document((int) $request->user()?->getAuthIdentifier(), (string) $request->route('slot'));

        return response($document['content'], 200, [
            'Content-Type' => $document['media_type'],
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $document['filename'],
                str_replace('%', '_', Str::ascii($document['filename'])) ?: 'document'),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function save(SaveRoleApplicationRequest $request, ApplyForRole $applications): RedirectResponse
    {
        /** @var array<string, mixed> $answers */
        $answers = (array) $request->validated('answers', []);
        $role = (string) $request->validated('role');

        return $this->answer($role, fn (): array => $applications->save((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), $role, (string) $request->validated('step'), $answers, (string) $request->validated('request_id')));
    }

    public function upload(UploadRoleApplicationDocumentRequest $request, ApplyForRole $applications): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->validated('file');

        return $this->answer(null, fn (): array => $applications->upload((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('slot'), $file->getClientOriginalName(),
            (string) $file->get(), (string) $request->validated('request_id')));
    }

    /** Once sent there is nothing left to do here, so the person goes back to the launcher, where the card shows the review. */
    public function submit(SubmitRoleApplicationRequest $request, ApplyForRole $applications): RedirectResponse
    {
        $this->answer(null, fn (): array => $applications->submit((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), $request->boolean('declared'), (string) $request->validated('request_id')));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your application is with our Compliance team. We will tell you when it has been reviewed.')]);

        return redirect()->route('dashboard');
    }

    /** @param  Closure(): array<string, mixed>  $command */
    private function answer(?string $role, Closure $command): RedirectResponse
    {
        $result = $command();
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['form' => [self::MESSAGES[$result['code']] ?? 'We could not save this. Try again.']]
                : $fields);
        }

        return redirect()->route('role-application.show', $role === null ? [] : ['for' => $role]);
    }
}
