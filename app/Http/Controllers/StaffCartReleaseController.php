<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\ResolveStuckCartRelease;
use App\Http\Requests\Staff\RetryCartReleaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Retries a stuck whole-cart release from Exceptions (`cart.releases.resolve`, #383 step 4c). It
 * releases every line of the cart together; a release already settled or not yet due is refused with
 * a reason the console shows on the row.
 */
class StaffCartReleaseController extends Controller
{
    private const array MESSAGES = [
        'CART_RESERVATION_NOT_HELD' => 'This cart was already released. Refresh the list.',
        'CART_SETTLEMENT_NOT_DUE' => 'This cart is still inside its hold window. Try again once it has closed.',
        'CART_RESERVATION_NOT_FOUND' => 'This cart checkout no longer exists.',
    ];

    public function __invoke(RetryCartReleaseRequest $request, string $checkout, ResolveStuckCartRelease $action): RedirectResponse
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'), $checkout);
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['request_id' => [self::MESSAGES[$result['code']] ?? 'We could not release this cart. Try again.']]);
        }

        return redirect()->route('staff.exceptions.index', ['kind' => 'cart_release']);
    }
}
