<?php

declare(strict_types=1);

namespace App\Notifications\Staff;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** A held cart checkout could not be expired, so its Investor's cash is still held (#383 step 4c). */
class CartSettlementStuck extends RozineMail
{
    public function __construct(
        public readonly string $checkoutId,
        public readonly string $total,
        public readonly string $reasonCode,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('A cart checkout could not be released'))
            ->markdown('mail.staff.cart-settlement-stuck', [
                'details' => [
                    __('Checkout') => strtoupper($this->checkoutId),
                    __('Held') => $this->francs($this->total),
                    __('Reason') => $this->reasonCode,
                ],
                'url' => $this->url,
            ]);
    }
}
