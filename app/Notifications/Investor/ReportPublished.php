<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** A monthly report published for a business the Investor holds, after its Audit Partner's seal (FR-103, FR-110). */
class ReportPublished extends RozineMail
{
    public function __construct(
        public readonly string $businessName,
        public readonly string $period,
        public readonly string $auditPartner,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':business published its :period report', ['business' => $this->businessName, 'period' => $this->period]))
            ->markdown('mail.investor.report-published', [
                'businessName' => $this->businessName,
                'period' => $this->period,
                'auditPartner' => $this->auditPartner,
                'url' => $this->url,
            ]);
    }
}
