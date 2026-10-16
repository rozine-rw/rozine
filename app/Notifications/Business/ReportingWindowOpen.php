<?php

declare(strict_types=1);

namespace App\Notifications\Business;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** The monthly reporting window has opened: what to submit and when it closes (FR-206, FR-607). */
class ReportingWindowOpen extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $period,
        public readonly DateTimeInterface $closesOn,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $closesOn = $this->day($this->closesOn);

        return (new MailMessage)
            ->subject(__('Your :period report is due by :date', ['period' => $this->period, 'date' => $closesOn]))
            ->markdown('mail.business.reporting-window-open', [
                'businessName' => $this->businessName,
                'period' => $this->period,
                'closesOn' => $closesOn,
                'url' => $this->url,
            ]);
    }
}
