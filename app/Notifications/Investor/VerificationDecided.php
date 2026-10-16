<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** Compliance's decision on an Investor's identity verification (KYC): verified, or rejected with its reason. */
class VerificationDecided extends RozineMail
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
                ? __('You are verified on Rozine')
                : __('Your identity details need another look'))
            ->markdown('mail.investor.verification-decided', [
                'approved' => $this->rejectionReason === null,
                'reason' => $this->rejectionReason,
                'url' => $this->url,
            ]);
    }
}
