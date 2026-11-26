<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ChangePlatformPolicies;
use App\Http\Requests\Staff\DecidePolicyChangeRequest;
use App\Http\Requests\Staff\ProposePolicyChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The Policies page's changes (26 Nov 2026): staff holding `policies.change` propose a new value, and
 * each founder approves or refuses it separately. A change takes effect once both founders approve.
 * A recorded refusal returns as a form error.
 */
class StaffPolicyChangeController extends Controller
{
    /** Refusals in the words the page shows. */
    private const MESSAGES = [
        'POLICY_CHANGE_PENDING' => 'A change to this policy is already waiting for the founders. Decide that one first.',
        'POLICY_CHANGE_CLOSED' => 'This change was already decided.',
        'POLICY_CHANGE_NOT_FOUND' => 'This change no longer exists.',
        'POLICY_ALREADY_DECIDED' => 'You already decided this change. The other founder decides next.',
        'STAFF_PERMISSION_REQUIRED' => 'Your role cannot do this.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function __construct(private ChangePlatformPolicies $policies) {}

    public function propose(ProposePolicyChangeRequest $request): RedirectResponse
    {
        return $this->answer($this->policies->propose((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('key'),
            (string) $request->validated('value'), (string) $request->validated('reason'), (string) $request->validated('request_id')), 'proposed');
    }

    public function approve(DecidePolicyChangeRequest $request): RedirectResponse
    {
        return $this->decide($request, 'approve');
    }

    public function reject(DecidePolicyChangeRequest $request): RedirectResponse
    {
        return $this->decide($request, 'reject');
    }

    private function decide(DecidePolicyChangeRequest $request, string $decision): RedirectResponse
    {
        $result = $this->policies->decide((int) $request->user()?->getAuthIdentifier(), (string) $request->route('change'), $decision,
            (string) $request->validated('reason'), (string) $request->validated('request_id'));

        return $this->answer($result, $decision === 'reject' ? 'rejected' : (($result['data']['outcome'] ?? null) === 'applied' ? 'applied' : 'approved'));
    }

    /** @param array<string, mixed> $result */
    private function answer(array $result, string $toast): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fieldErrors */
            $fieldErrors = $result['field_errors'] ?? [];

            throw ValidationException::withMessages($fieldErrors !== [] ? $fieldErrors
                : ['form' => [self::MESSAGES[$result['code']] ?? 'We could not record this change. Try again.']]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(match ($toast) {
            'proposed' => 'Change proposed. It takes effect once both founders approve it.',
            'approved' => 'Approved. The change takes effect once the other founder approves it too.',
            'applied' => 'Approved by both founders. The new value is now in force.',
            default => 'Refused. The value in force is unchanged.',
        })]);

        return to_route('staff.sections.show', ['section' => 'policies']);
    }
}
