<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Rozine stopped or restarted resale for this Investor (BRS v3.0 SEC-R08, SEC-R09): Compliance
 * cancelled their listing, or a halt cancelled it or released a reservation they held, or the halt
 * that did so was lifted. Each states the written reason Compliance recorded.
 */
class ResaleUpdate extends RozineMail
{
    /** @param  'listing_cancelled'|'listing_halted'|'reservation_released'|'reopened'  $event */
    private function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $units,
        public readonly string $reason,
        public readonly string $url,
    ) {}

    /** Compliance cancelled the Investor's listing, or a halt did. */
    public static function listingCancelled(string $title, string $units, string $reason, bool $byHalt, string $url): self
    {
        return new self($byHalt ? 'listing_halted' : 'listing_cancelled', $title, $units, $reason, $url);
    }

    /** A halt ended the Investor's reservation; the held cash is back in their wallet. */
    public static function reservationReleased(string $title, string $units, string $reason, string $url): self
    {
        return new self('reservation_released', $title, $units, $reason, $url);
    }

    /** The halt that stopped the Investor's order was lifted; `title` is empty for the whole market. */
    public static function reopened(string $title, string $reason, string $url): self
    {
        return new self('reopened', $title, '', $reason, $url);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scope = $this->title === '' ? __('the resale market') : $this->title;

        return (new MailMessage)
            ->subject(match ($this->event) {
                'listing_cancelled' => __('Rozine cancelled your :title listing', ['title' => $this->title]),
                'listing_halted' => __('Resale of :title is halted: your listing was cancelled', ['title' => $this->title]),
                'reservation_released' => __('Resale of :title is halted: your reservation was released', ['title' => $this->title]),
                default => __('Resale has reopened on :scope', ['scope' => $scope]),
            })
            ->markdown('mail.investor.resale-update', [
                'event' => $this->event,
                'title' => $this->title,
                'scope' => $scope,
                'units' => $this->units,
                'reason' => $this->reason,
                'url' => $this->url,
            ]);
    }
}
