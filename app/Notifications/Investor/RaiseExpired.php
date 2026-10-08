<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** A raise the Investor committed to expired unfunded after 30 days; their commitment came back in full, fee-free (BR-28, FR-606). */
class RaiseExpired extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $refund,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $refund = $this->francs($this->refund);

        return (new MailMessage)
            ->subject(__('Refunded: the :business raise closed', ['business' => $this->businessName]))
            ->markdown('mail.investor.raise-expired', [
                'businessName' => $this->businessName,
                'refund' => $refund,
                'details' => [
                    __('Returned to your wallet') => $refund,
                    __('Fee') => $this->francs('0'),
                ],
                'url' => $this->url,
            ]);
    }
}
