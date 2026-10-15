<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Wallet\LinkBusinessPayoutAccount;
use App\Application\Wallet\UnlinkBusinessPayoutAccount;
use App\Http\Requests\Business\LinkBusinessPayoutAccountRequest;
use App\Http\Requests\Business\UnlinkBusinessPayoutAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Links and unlinks the Business's own accounts from Profile > Linked accounts and returns to it; a refusal comes back as field errors. */
class BusinessPayoutAccountController extends Controller
{
    /** Refusals that are not about one field, in the words the page shows. */
    private const array MESSAGES = [
        'POLICY_INPUT_REQUIRED' => 'Accounts cannot be linked yet. Try again later.',
        'SYNTHETIC_BUSINESS_FUNDING_OFF' => 'Accounts cannot be linked on this test site.',
        'PAYOUT_ACCOUNT_NOT_FOUND' => 'That account is no longer linked.',
    ];

    public function __construct(private AuthorizeActiveRole $identity) {}

    public function link(LinkBusinessPayoutAccountRequest $request, LinkBusinessPayoutAccount $action): RedirectResponse
    {
        $business = (string) $request->route('business');

        return $this->answer($business, $action->handle($this->userId($request), $this->revision($request), $business,
            (string) $request->validated('request_id'), $request->safe()->only(['type', 'network', 'bank', 'number'])));
    }

    public function unlink(UnlinkBusinessPayoutAccountRequest $request, UnlinkBusinessPayoutAccount $action): RedirectResponse
    {
        $business = (string) $request->route('business');

        return $this->answer($business, $action->handle($this->userId($request), $this->revision($request), $business,
            (string) $request->validated('request_id'), (string) $request->route('method')));
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(LinkBusinessPayoutAccountRequest|UnlinkBusinessPayoutAccountRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision') ?? $this->identity->context($this->userId($request), 'business')['context_revision']);
    }

    /** @param  array<string, mixed>  $result */
    private function answer(string $business, array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'];
            throw ValidationException::withMessages($fields === []
                ? ['number' => [self::MESSAGES[$result['code']] ?? 'We could not change the linked accounts. Try again.']]
                : $fields);
        }

        return redirect()->route('business.profile', ['business' => $business, 'section' => 'linked']);
    }
}
