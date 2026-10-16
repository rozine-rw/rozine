<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * Local and testing scripts for the synthetic payout provider, behind the synthetic guard: what a
 * send acknowledges, what a query about an intent authenticates next, and a signed callback. It
 * holds no real account, PSP or banking arrangement.
 */
interface SyntheticPayoutScripts
{
    /** @param 'ack'|'nack'|'throw' $answer */
    public function scriptSend(string $answer, bool $idempotent = false): void;

    /**
     * Scripts what a query about this intent authenticates next; null scripts no answer.
     *
     * @param  array<string, string>|null  $overrides  e.g. amount, environment or effective_at
     */
    public function scriptQuery(string $intentId, ?string $state, ?array $overrides = null): void;

    /**
     * A signed synthetic provider message about an intent, as a callback would carry it.
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public function callback(string $intentId, string $state, array $overrides = []): array;
}
