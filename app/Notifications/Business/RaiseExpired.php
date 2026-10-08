<?php

declare(strict_types=1);

namespace App\Notifications\Business;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** The Business's raise expired unfunded after 30 days; investors were refunded and no fee applies (BR-28, FR-606). */
class RaiseExpired extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $target,
        public readonly string $committed,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your raise closed without full funding'))
            ->markdown('mail.business.raise-expired', [
                'businessName' => $this->businessName,
                'details' => [
                    __('Target') => $this->francs($this->target),
                    __('Committed by investors') => $this->francs($this->committed),
                    __('Fee') => $this->francs('0'),
                ],
                'url' => $this->url,
            ]);
    }
}
