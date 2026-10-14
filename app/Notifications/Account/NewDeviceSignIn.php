<?php

declare(strict_types=1);

namespace App\Notifications\Account;

use App\Notifications\RozineMail;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;

/** A login alert (BRS v2.0 NTF-R01, Security): a device the account had not used signed in. */
class NewDeviceSignIn extends RozineMail
{
    public function __construct(
        public readonly string $device,
        public readonly CarbonImmutable $signedInAt,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New sign-in to your Rozine account'))
            ->markdown('mail.account.new-device-sign-in', [
                'device' => $this->device,
                'moment' => $this->moment($this->signedInAt),
                'url' => $this->url,
            ]);
    }
}
