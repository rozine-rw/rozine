<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** A completed wallet deposit or withdrawal, with the balance it left (FR-109). */
class WalletTransferCompleted extends RozineMail
{
    public function __construct(
        public readonly WalletTransfer $transfer,
        public readonly string $amount,
        public readonly string $account,
        public readonly string $reference,
        public readonly string $availableBalance,
        public readonly DateTimeInterface $completedAt,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $amount = $this->francs($this->amount);
        $deposit = $this->transfer === WalletTransfer::Deposit;

        return (new MailMessage)
            ->subject($deposit
                ? __('Deposit received: :amount', ['amount' => $amount])
                : __('Withdrawal sent: :amount', ['amount' => $amount]))
            ->markdown('mail.investor.wallet-transfer-completed', [
                'deposit' => $deposit,
                'amount' => $amount,
                'account' => $this->account,
                'details' => [
                    __('Amount') => $amount,
                    ($deposit ? __('From') : __('To')) => $this->account,
                    __('Reference') => $this->reference,
                    __('Completed') => $this->moment($this->completedAt),
                    __('Available balance') => $this->francs($this->availableBalance),
                ],
                'url' => $this->url,
            ]);
    }
}
