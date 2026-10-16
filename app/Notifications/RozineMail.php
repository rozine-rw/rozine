<?php

declare(strict_types=1);

namespace App\Notifications;

use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * A Rozine transactional email: one markdown template under resources/views/mail, rendered with
 * the Rozine mail theme. Delivery is synchronous because no environment runs a queue worker yet,
 * so a queued message would never leave.
 */
abstract class RozineMail extends Notification
{
    /** Every date a Rozine email shows is read in Kigali. */
    private const string ZONE = 'Africa/Kigali';

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract public function toMail(object $notifiable): MailMessage;

    /** Whole Rwandan francs with grouped digits, e.g. "RWF 1,500,000". */
    protected function francs(string $amount): string
    {
        if (preg_match('/^\d+$/', $amount) !== 1) {
            throw new InvalidArgumentException('MAIL_AMOUNT_NOT_WHOLE_FRANCS');
        }

        $digits = ltrim($amount, '0');

        return 'RWF '.preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $digits === '' ? '0' : $digits);
    }

    /** A calendar date in Kigali, e.g. "7 Oct 2026". */
    protected function day(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone(self::ZONE)->format('j M Y');
    }

    /** An instant in Kigali, e.g. "7 Oct 2026, 14:42 CAT". */
    protected function moment(DateTimeInterface $instant): string
    {
        return Carbon::instance($instant)->setTimezone(self::ZONE)->format('j M Y, H:i T');
    }
}
