<?php

declare(strict_types=1);

namespace App\Notifications\Business;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** The arrears warning a Business receives before and after a repayment's due date (FR-208, FR-607). */
class RepaymentReminder extends RozineMail
{
    public function __construct(
        public readonly RepaymentStage $stage,
        public readonly string $businessName,
        public readonly string $amount,
        public readonly DateTimeInterface $dueOn,
        public readonly int $instalmentNumber,
        public readonly int $instalmentCount,
        public readonly string $url,
        public readonly int $daysOverdue = 0,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $dueOn = $this->day($this->dueOn);

        return (new MailMessage)
            ->subject($this->stage === RepaymentStage::Upcoming
                ? __('Repayment due on :date', ['date' => $dueOn])
                : __('Repayment overdue: :business', ['business' => $this->businessName]))
            ->markdown('mail.business.repayment-reminder', [
                'overdue' => $this->stage === RepaymentStage::Overdue,
                'overdueLabel' => trans_choice('{1} :count day overdue|[2,*] :count days overdue', $this->daysOverdue, ['count' => $this->daysOverdue]),
                'businessName' => $this->businessName,
                'amount' => $this->francs($this->amount),
                'dueOn' => $dueOn,
                'details' => [
                    __('Amount due') => $this->francs($this->amount),
                    __('Due date') => $dueOn,
                    __('Instalment') => __(':number of :count', ['number' => $this->instalmentNumber, 'count' => $this->instalmentCount]),
                ],
                'url' => $this->url,
            ]);
    }
}
