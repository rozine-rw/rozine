<?php

declare(strict_types=1);

namespace App\Notifications\Business;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;
use InvalidArgumentException;

/**
 * Underwriting's decision on a Business application: approved for listing, returned for changes, or
 * declined. A return or decline always carries the reason the Business app shows (FR-205, FR-213).
 */
class ApplicationDecided extends RozineMail
{
    public function __construct(
        public readonly ApplicationOutcome $outcome,
        public readonly string $businessName,
        public readonly string $amount,
        public readonly int $tenorMonths,
        public readonly string $url,
        public readonly ?string $reason = null,
    ) {
        if ($outcome !== ApplicationOutcome::Approved && ($reason === null || trim($reason) === '')) {
            throw new InvalidArgumentException('MAIL_APPLICATION_REASON_REQUIRED');
        }
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(match ($this->outcome) {
                ApplicationOutcome::Approved => __('Your application is approved'),
                ApplicationOutcome::Returned => __('Your application needs changes'),
                ApplicationOutcome::Declined => __('Your application was not approved'),
            })
            ->markdown('mail.business.application-decided', [
                'outcome' => $this->outcome->value,
                'businessName' => $this->businessName,
                'reason' => $this->reason,
                'details' => [
                    __('Business') => $this->businessName,
                    __('Amount') => $this->francs($this->amount),
                    __('Tenor') => trans_choice('{1} :count month|[2,*] :count months', $this->tenorMonths, ['count' => $this->tenorMonths]),
                ],
                'url' => $this->url,
            ]);
    }
}
