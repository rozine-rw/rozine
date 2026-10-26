<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The receipt for a confirmed primary purchase (BRS v2.0 INV-R05): the business, the amount
 * invested and the earnings-fee rate the checkout locked, sent when the commitment is confirmed.
 */
class PurchaseConfirmed extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $amount,
        public readonly int $notes,
        public readonly string $feeTier,
        public readonly int $feeRateBps,
        public readonly string $reference,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Purchase confirmed: :business', ['business' => $this->businessName]))
            ->markdown('mail.investor.purchase-confirmed', [
                'businessName' => $this->businessName,
                'amount' => $this->francs($this->amount),
                'details' => [
                    __('Business') => $this->businessName,
                    __('Amount invested') => $this->francs($this->amount),
                    __('Notes') => trans_choice('{1} :count note|[2,*] :count notes', $this->notes, ['count' => $this->notes]),
                    __('Earnings fee') => __(':rate% (:tier)', ['rate' => number_format($this->feeRateBps / 100, 1), 'tier' => ucfirst($this->feeTier)]),
                    __('Reference') => $this->reference,
                ],
                'url' => $this->url,
            ]);
    }
}
