<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** An arrears warning: a scheduled repayment on one of the Investor's holdings has not arrived (FR-110). */
class PaymentDelayed extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $amount,
        public readonly DateTimeInterface $dueOn,
        public readonly int $daysOverdue,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Payment delayed: :business', ['business' => $this->businessName]))
            ->markdown('mail.investor.payment-delayed', [
                'businessName' => $this->businessName,
                'amount' => $this->francs($this->amount),
                'dueOn' => $this->day($this->dueOn),
                'overdue' => trans_choice('{1} :count day overdue|[2,*] :count days overdue', $this->daysOverdue, ['count' => $this->daysOverdue]),
                'url' => $this->url,
            ]);
    }
}
