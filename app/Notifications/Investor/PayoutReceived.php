<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A monthly repayment credited to an Investor's wallet from one holding (FR-110 payouts). The amounts
 * arrive as the ledger posted them; the email shows them and never adds them up itself.
 */
class PayoutReceived extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $amount,
        public readonly string $principal,
        public readonly string $return,
        public readonly int $paymentNumber,
        public readonly int $paymentCount,
        public readonly ?DateTimeInterface $nextPaymentOn,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $total = $this->francs($this->amount);

        return (new MailMessage)
            ->subject(__('Payout received from :business', ['business' => $this->businessName]))
            ->markdown('mail.investor.payout-received', [
                'businessName' => $this->businessName,
                'total' => $total,
                'paymentNumber' => $this->paymentNumber,
                'paymentCount' => $this->paymentCount,
                'details' => [
                    __('Principal') => $this->francs($this->principal),
                    __('Return') => $this->francs($this->return),
                    __('Total paid to you') => $total,
                    __('Next payment') => $this->nextPaymentOn === null ? __('Fully repaid') : $this->day($this->nextPaymentOn),
                ],
                'url' => $this->url,
            ]);
    }
}
