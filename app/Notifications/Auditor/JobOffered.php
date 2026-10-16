<?php

declare(strict_types=1);

namespace App\Notifications\Auditor;

use App\Notifications\RozineMail;
use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;

/** A job dispatched to an Audit Partner, with the deadline to accept it (FR-302, FR-303). */
class JobOffered extends RozineMail
{
    public function __construct(
        public readonly AuditJobType $type,
        public readonly string $businessName,
        public readonly string $location,
        public readonly DateTimeInterface $respondBy,
        public readonly string $url,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $flash = $this->type === AuditJobType::Flash;

        return (new MailMessage)
            ->subject($flash
                ? __('Flash Audit offered: :business', ['business' => $this->businessName])
                : __('New audit job: :business', ['business' => $this->businessName]))
            ->markdown('mail.auditor.job-offered', [
                'flash' => $flash,
                'businessName' => $this->businessName,
                'location' => $this->location,
                'respondBy' => $this->moment($this->respondBy),
                'details' => [
                    __('Job type') => $flash ? __('Flash Audit') : __('Routine audit'),
                    __('Business') => $this->businessName,
                    __('Location') => $this->location,
                    __('Respond by') => $this->moment($this->respondBy),
                ],
                'url' => $this->url,
            ]);
    }
}
