<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** The receipt for a confirmed primary investment: the exact francs in and the projected francs back (FR-104). */
class InvestmentConfirmed extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $amount,
        public readonly int $notes,
        public readonly string $expectedReturn,
        public readonly string $maturityValue,
        public readonly DateTimeInterface $maturityDate,
        public readonly string $reference,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Investment confirmed: :business', ['business' => $this->businessName]))
            ->markdown('mail.investor.investment-confirmed', [
                'businessName' => $this->businessName,
                'amount' => $this->francs($this->amount),
                'details' => [
                    __('Amount invested') => $this->francs($this->amount),
                    __('Notes') => trans_choice('{1} :count note|[2,*] :count notes', $this->notes, ['count' => $this->notes]),
                    __('Expected return') => $this->francs($this->expectedReturn),
                    __('Maturity value') => $this->francs($this->maturityValue),
                    __('Maturity date') => $this->day($this->maturityDate),
                    __('Reference') => $this->reference,
                ],
                'url' => $this->url,
            ]);
    }
}
