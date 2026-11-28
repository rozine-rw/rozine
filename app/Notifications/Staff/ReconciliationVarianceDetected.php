<?php

declare(strict_types=1);

namespace App\Notifications\Staff;

use App\Application\Operations\Contracts\ReconciliationVariances;
use App\Notifications\RozineMail;
use Illuminate\Notifications\Messages\MailMessage;

/** @phpstan-import-type Variance from ReconciliationVariances */
class ReconciliationVarianceDetected extends RozineMail
{
    /** @param Variance $variance */
    public function __construct(public readonly array $variance, public readonly string $url) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject(__('A reconciliation variance needs a recorded resolution'))
            ->line(__('Business date').': '.$this->variance['business_date'])
            ->line(__('Account').': '.$this->variance['account_id'])
            ->line(__('Ledger minus statement').': '.$this->variance['difference'].' RWF')
            ->action(__('Open Reconciliation'), $this->url);
    }
}
