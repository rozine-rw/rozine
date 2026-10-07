<?php

declare(strict_types=1);

namespace App\Application\Environment;

use App\Application\Environment\Contracts\StagingMailTesterStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mime\Address;

/**
 * Staging shares production's sending domain, so every real staging message is
 * checked where it is delivered: it reaches only the owner-approved testers (the
 * server's STAGING_MAIL_RECIPIENTS plus the named testers a superadmin keeps),
 * stays within the hourly allowance and always leaves as the approved staging
 * sender, whatever the code that built it asked for.
 */
class StagingMailGuard
{
    private const string LIMITER_KEY = 'staging-mail';

    public function __construct(
        private readonly Repository $config,
        private readonly StagingMailTesterStore $testers,
        private readonly EnvironmentIsolation $isolation,
    ) {}

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

        if (RateLimiter::tooManyAttempts(self::LIMITER_KEY, $this->config->integer('isolation.staging_mail.hourly_limit'))) {
            Log::warning('Staging mail withheld: the hourly allowance is spent.');

            return false;
        }

        RateLimiter::hit(self::LIMITER_KEY, 3600);

        $message->from(new Address($this->config->string('mail.from.address'), $this->config->string('mail.from.name')));
        $message->getHeaders()->remove('Sender');
        $message->getHeaders()->remove('Return-Path');

        return null;
    }

    private function isApprovedTester(string $address): bool
    {
        return $this->isolation->stagingMailServerApproves($address) || $this->testers->includes($address);
    }
}
