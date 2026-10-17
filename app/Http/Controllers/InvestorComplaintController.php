<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\OpenComplaint;
use App\Application\Identity\ReplyToComplaint;
use App\Http\Requests\Investor\ComplaintMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * The Investor's in-app complaints (BRS v2.0 CMP-R03), opened and added to from the Help center's
 * support chat with plain Inertia forms. A refusal comes back as a form error on the message.
 */
class InvestorComplaintController extends Controller
{
    /** Refusals that are not about the text, in the words the chat shows. */
    private const MESSAGES = [
        'COMPLAINT_NOT_FOUND' => 'We could not find that conversation.',
        'COMPLAINT_RESOLVED' => 'This complaint is resolved. Start a new message to raise something else.',
        'IDEMPOTENCY_CONFLICT' => 'That message was already sent with different text. Try again.',
    ];

    public function store(ComplaintMessageRequest $request, OpenComplaint $action): RedirectResponse
    {
        return $this->answer($action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'),
            $request->validated('body')));
    }

    public function reply(ComplaintMessageRequest $request, string $complaint, ReplyToComplaint $action): RedirectResponse
    {
        return $this->answer($action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'), $complaint,
            $request->validated('body')));
    }

    /** @param  array<string, mixed>  $result */
    private function answer(array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['body' => [self::MESSAGES[$result['code']] ?? 'We could not send your message. Try again.']]
                : $fields);
        }

        return redirect()->route('investor.profile', ['section' => 'help']);
    }
}
