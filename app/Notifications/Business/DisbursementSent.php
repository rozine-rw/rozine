<?php

declare(strict_types=1);

namespace App\Notifications\Business;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** A fully funded raise paid out to the Business's registered account, with its first repayment (FR-205, FR-211). */
class DisbursementSent extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $amount,
        public readonly string $account,
        public readonly string $reference,
        public readonly string $firstRepayment,
        public readonly DateTimeInterface $firstRepaymentOn,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $amount = $this->francs($this->amount);

        return (new MailMessage)
            ->subject(__('Funds sent: :amount', ['amount' => $amount]))
            ->markdown('mail.business.disbursement-sent', [
                'businessName' => $this->businessName,
                'amount' => $amount,
                'account' => $this->account,
                'details' => [
                    __('Amount sent') => $amount,
                    __('To') => $this->account,
                    __('Reference') => $this->reference,
                    __('First repayment') => $this->francs($this->firstRepayment),
                    __('First repayment due') => $this->day($this->firstRepaymentOn),
                ],
                'url' => $this->url,
            ]);
    }
}
