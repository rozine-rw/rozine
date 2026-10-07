<?php

declare(strict_types=1);

namespace App\Application\Environment;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;

/**
 * Staging shares production's sending domain, so every real staging message is
 * checked where it is delivered: it reaches only the owner-approved testers,
 * stays within the hourly allowance and always leaves as the approved staging
 * sender, whatever the code that built it asked for.
 */
class StagingMailGuard
{
    public function __construct(private readonly Repository $config, private readonly StagingMailAllowance $allowance) {}

    /**
     * Returning false cancels the message; null lets later listeners run.
     */
    public function handle(MessageSending $event): ?bool
    {
        $message = $event->message;
        $recipients = [...$message->getTo(), ...$message->getCc(), ...$message->getBcc()];

        foreach ($recipients === [] ? [null] : $recipients as $recipient) {
            if ($recipient === null || ! $this->isApprovedTester($recipient->getAddress())) {
                Log::warning('Staging mail withheld: a recipient is not an approved tester.');

                return false;
            }
        }

        if (! $this->allowance->reserve()) {
            Log::warning('Staging mail withheld: the hourly allowance is spent or could not be reserved.');

            return false;
        }

        $message->from(new Address($this->config->string('mail.from.address'), $this->config->string('mail.from.name')));
        $message->getHeaders()->remove('Sender');
        $message->getHeaders()->remove('Return-Path');

        return null;
    }

    private function isApprovedTester(string $address): bool
    {
        $address = strtolower($address);

        foreach ($this->config->array('isolation.staging_mail.recipients') as $approved) {
            if (! is_string($approved)) {
                continue;
            }

            $approved = strtolower($approved);

            if (str_starts_with($approved, '@') ? str_ends_with($address, $approved) : $address === $approved) {
                return true;
            }
        }

        return false;
    }
}
