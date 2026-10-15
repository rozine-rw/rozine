<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\SaveBusinessContact;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\SaveBusinessContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/** Saves the Profile's "Company information" and returns to it; a refusal comes back as field errors. */
class BusinessContactController extends Controller
{
    /** @var array<string, string> */
    private const array MESSAGES = [
        'ACTION_FORBIDDEN' => 'You do not have permission to change the company information.',
        'MANDATE_REQUIRED' => 'You do not have permission to change the company information.',
        'IDEMPOTENCY_CONFLICT' => 'This save was already sent with different details. Try again.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function update(SaveBusinessContactRequest $request, SaveBusinessContact $action): RedirectResponse
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $business = (string) $request->route('business');
        $revision = (int) ($request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision']);
        $result = $action->handle($userId, $revision, $business, (string) $request->validated('request_id'),
            $request->safe()->only(['email', 'phone', 'province', 'district', 'sector', 'cell', 'street']));
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'];
            throw ValidationException::withMessages($fields === []
                ? ['email' => [self::MESSAGES[$result['code']] ?? 'We could not save the company information. Try again.']]
                : $fields);
        }

        return redirect()->route('business.profile', ['business' => $business, 'section' => 'company']);
    }
}
