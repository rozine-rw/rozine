<?php

declare(strict_types=1);

namespace App\Notifications\Account;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Where a person's own onboarding stands (Erastus, 24 Nov 2026: every change is emailed as well as
 * shown in the app): their identity check received, or their application to become a Business
 * representative or an Auditor received, sent back with the items to change, approved or rejected.
 */
class OnboardingUpdate extends RozineMail
{
    /**
     * @param  'kyc_received'|'role_received'|'role_returned'|'role_approved'|'role_rejected'  $event
     * @param  'business'|'auditor'|null  $role
     * @param  list<array{label: string, note: string}>  $items
     */
    private function __construct(
        public readonly string $event,
        public readonly ?string $role,
        public readonly ?string $company,
        public readonly ?string $note,
        public readonly array $items,
        public readonly string $url,
    ) {}

    public static function kycReceived(string $url): self
    {
        return new self('kyc_received', null, null, null, [], $url);
    }

    /** @param 'business'|'auditor' $role */
    public static function roleReceived(string $role, ?string $company, string $url): self
    {
        return new self('role_received', $role, $company, null, [], $url);
    }

    /**
     * @param  'business'|'auditor'  $role
     * @param  list<array{label: string, note: string}>  $items
     */
    public static function roleReturned(string $role, ?string $company, string $note, array $items, string $url): self
    {
        return new self('role_returned', $role, $company, $note, $items, $url);
    }

    /** @param 'business'|'auditor' $role */
    public static function roleApproved(string $role, ?string $company, string $url): self
    {
        return new self('role_approved', $role, $company, null, [], $url);
    }

    /** @param 'business'|'auditor' $role */
    public static function roleRejected(string $role, ?string $company, string $note, string $url): self
    {
        return new self('role_rejected', $role, $company, $note, [], $url);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->event) {
            'kyc_received' => __('We have your identity check'),
            'role_received' => $this->role === 'business' ? __('We have your business application') : __('We have your Auditor application'),
            'role_returned' => __('Compliance needs changes to your application'),
            'role_approved' => $this->role === 'business' ? __('Your business is approved on Rozine') : __('You are now a Rozine Audit Partner'),
            default => __('Your application was not approved'),
        };

        return (new MailMessage)->subject($subject)->markdown('mail.account.onboarding-update', [
            'event' => $this->event, 'role' => $this->role, 'company' => $this->company, 'note' => $this->note,
            'items' => $this->items, 'url' => $this->url,
        ]);
    }
}
