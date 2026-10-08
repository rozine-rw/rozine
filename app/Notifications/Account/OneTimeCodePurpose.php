<?php

declare(strict_types=1);

namespace App\Notifications\Account;

/** Why a one-time code was sent, which decides the wording and the app it belongs to. */
enum OneTimeCodePurpose: string
{
    /** Confirming a new account's email address during sign-up. */
    case SignUp = 'sign_up';

    /** Confirming a business at registration, sent to the contact RDB holds for it. */
    case BusinessRegistration = 'business_registration';
}
