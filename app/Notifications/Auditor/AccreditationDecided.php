<?php

declare(strict_types=1);

namespace App\Notifications\Auditor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** The verification desk's decision on an Audit Partner's ICPAR accreditation (FR-300, FR-403). */
class AccreditationDecided extends RozineMail
{
    private function __construct(
        public readonly string $url,
        public readonly ?string $rejectionReason,
    ) {}

    public static function approved(string $url): self
    {
        return new self($url, null);
    }

    public static function rejected(string $reason, string $url): self
    {
        return new self($url, $reason);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->rejectionReason === null
                ? __('Your accreditation is active')
                : __('Your accreditation needs another look'))
            ->markdown('mail.auditor.accreditation-decided', [
                'approved' => $this->rejectionReason === null,
                'reason' => $this->rejectionReason,
                'url' => $this->url,
            ]);
    }
}
