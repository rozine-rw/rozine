<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetInvestorVerification;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Domain\Identity\IdentityViolation;
use App\Http\Requests\Investor\SaveVerificationRequest;
use App\Http\Requests\Investor\SubmitVerificationRequest;
use App\Http\Requests\Investor\UploadVerificationDocumentRequest;
use App\Http\Resources\InvestorVerificationResource;
use App\Http\Resources\OperationResource;
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
 * An unverified person's Investor identity submission (MVP-INVESTOR-SCR-03). The page posts plain
 * Inertia forms: each command still carries its `request_id`, context and expected revision, and a
 * recorded refusal comes back as form errors on the same page.
 */
class InvestorVerificationController extends Controller
{
    /** Refusals that are not about one field, in the words the page shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => 'This form changed in another window. Check your answers and try again.',
        'VERIFICATION_NOT_EDITABLE' => 'Your details are with our Compliance team, so they can no longer be changed.',
        'VERIFICATION_NOT_REQUIRED' => 'Your identity is already verified.',
        'VERIFICATION_STEP_OUT_OF_ORDER' => 'Finish the earlier steps first.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function show(Request $request, GetInvestorVerification $verification): Response|RedirectResponse|InvestorVerificationResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('investor:read'), 403);
        $userId = (int) $request->user()?->getAuthIdentifier();
        $submission = [...$verification->handle($userId), 'documents' => $verification->uploads($userId)];
        if ($request->routeIs('api.*')) {
            return new InvestorVerificationResource($submission);
        }
        if ($submission['verified']) {
            return redirect()->route('dashboard');
        }
        // The launcher's Business and Auditor cards send an unverified person here `for` that app: the
        // check is recorded for it with the first saved step, while the answers can still change.
        $for = match ($request->query('for')) {
            'investor' => 'investor',
            'business' => 'business',
            'auditor' => 'auditor',
            default => null,
        };
        if ($for !== null && in_array($submission['status'], ['draft', 'rejected'], true)) {
            $submission['purpose'] = $for;
        }
        // An Auditor account must hold two-factor authentication (AUD-R01), so it is set up before the
        // identity and licence check starts, not after Compliance has approved it.
        if ($this->needsTwoFactor($request, $submission['purpose'], $submission['status'])) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Turn on two-factor authentication first. Then open the Auditor card again to send your ID and ICPAR licence.')]);

            return redirect()->route('security.edit');
        }

        return Inertia::render('investor/verification', (new InvestorVerificationResource($submission))->resolve($request));
    }

    /**
     * The person's own upload on one side, shown back to them in place. Every stored type is a
     * signature-checked PDF, PNG or JPEG; nosniff keeps the browser to it and the sandbox stops a
     * PDF from running anything.
     */
    public function document(Request $request, GetInvestorVerification $verification): SymfonyResponse
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('investor:read'), 403);
        $document = $verification->document((int) $request->user()?->getAuthIdentifier(), (string) $request->route('side'));

        return response($document['content'], 200, [
            'Content-Type' => $document['media_type'],
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $document['filename'],
                str_replace('%', '_', Str::ascii($document['filename'])) ?: 'document'),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function save(SaveVerificationRequest $request, SaveInvestorVerification $action, GetInvestorVerification $verification): RedirectResponse|OperationResource
    {
        $this->requireTwoFactor($request, $verification, $request->validated('purpose'));

        return $this->answer($request, fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('step'),
            $request->safe()->only(['date_of_birth', 'id_type', 'id_number', 'purpose', 'licence_number', 'licence_expires_on']), (string) $request->validated('request_id')));
    }

    public function upload(UploadVerificationDocumentRequest $request, UploadInvestorVerificationDocument $action, GetInvestorVerification $verification): RedirectResponse|OperationResource
    {
        $this->requireTwoFactor($request, $verification);
        /** @var UploadedFile $file */
        $file = $request->validated('file');

        return $this->answer($request, fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('slot'), $file->getClientOriginalName(),
            (string) $file->get(), (string) $request->validated('request_id')));
    }

    /**
     * Once the details are with Compliance there is nothing left to do on this page, so the
     * Investor goes straight to their home (Deals, where the gate reads "pending"), told the
     * submission arrived, instead of waiting on a locked form. A person checked for Business or
     * Auditor access goes back to the launcher, where that card waits for the decision.
     */
    public function submit(SubmitVerificationRequest $request, SubmitInvestorVerification $action, GetInvestorVerification $verification): RedirectResponse|OperationResource
    {
        $this->requireTwoFactor($request, $verification);
        $answer = $this->answer($request, fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('request_id')));
        if ($answer instanceof OperationResource) {
            return $answer;
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your details are with our Compliance team. We will tell you when they have been reviewed.')]);

        return redirect()->route($verification->handle((int) $request->user()?->getAuthIdentifier())['purpose'] === 'investor' ? 'investor.deals' : 'dashboard');
    }

    /** Whether an editable check made for Auditor access still waits on the account's two-factor setup. */
    private function needsTwoFactor(Request $request, string $purpose, string $status): bool
    {
        return $purpose === 'auditor' && in_array($status, ['draft', 'rejected'], true)
            && $request->user()?->hasEnabledTwoFactorAuthentication() !== true;
    }

    /**
     * Refuses a change to an Auditor check from an account without two-factor authentication. An
     * account the check does not describe is left to the command, which refuses it in its own words.
     */
    private function requireTwoFactor(Request $request, GetInvestorVerification $verification, mixed $purpose = null): void
    {
        if ($request->user()?->hasEnabledTwoFactorAuthentication() === true) {
            return;
        }
        try {
            $recorded = $verification->handle((int) $request->user()?->getAuthIdentifier());
        } catch (IdentityViolation) {
            return;
        }
        if ($this->needsTwoFactor($request, is_string($purpose) ? $purpose : $recorded['purpose'], $recorded['status'])) {
            throw ValidationException::withMessages(['form' => [__('Turn on two-factor authentication in your security settings before you send your Auditor identity check.')]]);
        }
    }

    /**
     * A recorded refusal and one refused before it was recorded (an idempotency conflict) arrive alike:
     * as form errors on the page, or as the operation receipt over the API.
     *
     * @param  Closure(): array<string, mixed>  $command
     */
    private function answer(Request $request, Closure $command): RedirectResponse|OperationResource
    {
        $result = $command();
        if ($request->routeIs('api.*')) {
            return OperationResource::fromResult($result, 'engineering-2026-10-06.1');
        }
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'];
            throw ValidationException::withMessages($fields === []
                ? ['form' => [self::MESSAGES[$result['code']] ?? 'We could not save this step. Try again.']]
                : $fields);
        }

        return redirect()->route('investor.verification');
    }
}
