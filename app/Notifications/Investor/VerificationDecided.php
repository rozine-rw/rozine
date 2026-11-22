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
        public readonly string $purpose = 'investor',
    ) {}

    public static function approved(string $url): self
    {
        return new self($url, null);
    }

    /** Verified for Business or Auditor access: the next step is asking for that access on the launcher. */
    public static function approvedFor(string $purpose, string $url): self
    {
        return new self($url, null, $purpose);
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
                'purpose' => $this->purpose,
                'reason' => $this->rejectionReason,
                'url' => $this->url,
            ]);
    }
}
