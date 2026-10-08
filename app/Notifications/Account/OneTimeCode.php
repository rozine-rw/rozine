<?php

declare(strict_types=1);

namespace App\Notifications\Account;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;
use InvalidArgumentException;

/**
 * The onboarding one-time code: a six-digit code the person types back into the app to confirm
 * their email at sign-up, or to confirm a business at registration. The code never appears in
 * the subject, so it does not show on a locked phone's notification preview.
 */
class OneTimeCode extends RozineMail
{
    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
        public readonly OneTimeCodePurpose $purpose = OneTimeCodePurpose::SignUp,
        public readonly ?string $businessName = null,
    ) {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            throw new InvalidArgumentException('MAIL_ONE_TIME_CODE_NOT_SIX_DIGITS');
        }

        if ($purpose === OneTimeCodePurpose::BusinessRegistration && ($businessName === null || trim($businessName) === '')) {
            throw new InvalidArgumentException('MAIL_ONE_TIME_CODE_BUSINESS_REQUIRED');
        }
    }

    public function toMail(object $notifiable): MailMessage
    {
        $forBusiness = $this->purpose === OneTimeCodePurpose::BusinessRegistration;

        return (new MailMessage)
            ->subject($forBusiness
                ? __('Your code to register :business on Rozine', ['business' => $this->businessName])
                : __('Your Rozine verification code'))
            ->markdown('mail.account.one-time-code', [
                'audience' => $forBusiness ? 'business' : 'account',
                'forBusiness' => $forBusiness,
                'businessName' => $this->businessName,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ]);
    }
}
