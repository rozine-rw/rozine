<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\EmailVerificationCode;
use App\Http\Requests\Identity\VerifyEmailCodeRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

/**
 * Confirms a new account's email with the six-digit code it was sent, then continues exactly as
 * Fortify's verification link does. The code must have been sent to the account's current
 * address, and the address is marked verified only if it is still that address when written;
 * when nothing is written, only a duplicate submission for the same address may have won. A
 * refused code returns as a form error on the code field.
 */
class EmailVerificationCodeController extends Controller
{
    /** Refusals in the words the verification page shows. */
    private const MESSAGES = [
        'EMAIL_CODE_INVALID' => 'That code is not right. Check the latest email from Rozine and try again.',
        'EMAIL_CODE_EXPIRED' => 'That code has expired. Send a new code and enter it within 10 minutes.',
        'EMAIL_CODE_TOO_MANY_ATTEMPTS' => 'Too many attempts. Wait 15 minutes, then try the latest code again.',
    ];

    public function __construct(private EmailVerificationCode $codes) {}

    public function store(VerifyEmailCodeRequest $request): VerifyEmailResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $email = $user->getEmailForVerification();
            $refusal = $this->codes->confirm((int) $user->getAuthIdentifier(), $email, (string) $request->validated('code'));

            if ($refusal !== null) {
                throw ValidationException::withMessages(['code' => self::MESSAGES[$refusal]]);
            }

            if ($user->markEmailAsVerifiedFor($email)) {
                event(new Verified($user));
            } elseif (! $user->refresh()->isVerifiedAt($email)) {
                throw ValidationException::withMessages(['code' => self::MESSAGES['EMAIL_CODE_INVALID']]);
            }
        }

        return app(VerifyEmailResponse::class);
    }
}
