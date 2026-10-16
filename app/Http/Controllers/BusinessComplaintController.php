<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\OpenComplaint;
use App\Application\Identity\ReplyToComplaint;
use App\Http\Requests\Business\BusinessComplaintMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * A Business's in-app complaints (BRS v2.0 CMP-R03), opened and added to from the Profile's support
 * center chat by anyone who may view the Business. A refusal comes back as a form error on the message.
 */
class BusinessComplaintController extends Controller
{
    /** Refusals that are not about the text, in the words the chat shows. */
    private const array MESSAGES = [
        'COMPLAINT_NOT_FOUND' => 'We could not find that conversation.',
        'COMPLAINT_RESOLVED' => 'This complaint is resolved. Start a new message to raise something else.',
        'IDEMPOTENCY_CONFLICT' => 'That message was already sent with different text. Try again.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function store(BusinessComplaintMessageRequest $request, OpenComplaint $action): RedirectResponse
    {
        $business = (string) $request->route('business');

        return $this->answer($business, $action->forBusiness($this->userId($request), $this->revision($request), $business,
            (string) $request->validated('request_id'), $request->validated('body')));
    }

    public function reply(BusinessComplaintMessageRequest $request, ReplyToComplaint $action): RedirectResponse
    {
        $business = (string) $request->route('business');

        return $this->answer($business, $action->forBusiness($this->userId($request), $this->revision($request), $business,
            (string) $request->validated('request_id'), (string) $request->route('complaint'), $request->validated('body')));
    }

    private function userId(BusinessComplaintMessageRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(BusinessComplaintMessageRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision') ?? $this->identity->context($this->userId($request), 'business')['context_revision']);
    }

    /** @param  array<string, mixed>  $result */
    private function answer(string $business, array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['body' => [self::MESSAGES[$result['code']] ?? 'We could not send your message. Try again.']]
                : $fields);
        }

        return redirect()->route('business.profile', ['business' => $business, 'section' => 'support']);
    }
}
