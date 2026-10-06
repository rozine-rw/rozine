<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetInvestorVerification;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Http\Requests\Investor\SaveVerificationRequest;
use App\Http\Requests\Investor\SubmitVerificationRequest;
use App\Http\Requests\Investor\UploadVerificationDocumentRequest;
use App\Http\Resources\InvestorVerificationResource;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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

    public function show(Request $request, GetInvestorVerification $verification): Response|RedirectResponse
    {
        $submission = $verification->handle((int) $request->user()?->getAuthIdentifier());
        if ($submission['verified']) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('investor/verification', (new InvestorVerificationResource($submission))->resolve($request));
    }

    public function save(SaveVerificationRequest $request, SaveInvestorVerification $action): RedirectResponse
    {
        return $this->answer(fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('step'),
            $request->safe()->only(['date_of_birth', 'id_type', 'id_number']), (string) $request->validated('request_id')));
    }

    public function upload(UploadVerificationDocumentRequest $request, UploadInvestorVerificationDocument $action): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->validated('file');

        return $this->answer(fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('slot'), $file->getClientOriginalName(),
            (string) $file->get(), (string) $request->validated('request_id')));
    }

    public function submit(SubmitVerificationRequest $request, SubmitInvestorVerification $action): RedirectResponse
    {
        return $this->answer(fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (int) $request->validated('expected_revision'), (string) $request->validated('request_id')));
    }

    /**
     * A recorded refusal and one refused before it was recorded (an idempotency conflict) arrive alike.
     *
     * @param  Closure(): array<string, mixed>  $command
     */
    private function answer(Closure $command): RedirectResponse
    {
        $result = $command();
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
