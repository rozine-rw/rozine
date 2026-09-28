<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures;
use App\Application\Disbursement\Contracts\SyntheticPayoutScripts;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Domain\Operations\CommandRejection;
use Illuminate\Console\Command;
use LogicException;

/**
 * Synthetic S3-D hooks for independent local checks: fund a synthetic campaign and open its
 * disbursement, script the provider's send and query answers, fail a recheck, and deliver a signed
 * synthetic callback. Local and testing only; nothing here is production funding authority, a
 * verified destination or a real payout.
 */
class PrepareSyntheticDisbursement extends Command
{
    protected $hidden = true;

    protected $signature = 'local:disbursement
        {--seed : Fund a synthetic campaign (two commitments, 600 units) with a verified synthetic destination and open its disbursement}
        {--event= : Deliver a signed synthetic callback for this intent id}
        {--state= : The event state: succeeded, failed, unknown or pending}
        {--event-id= : A provider event id to reuse (duplicate and key-collision checks)}
        {--amount= : Report this amount instead of the intent\'s (mismatch checks)}
        {--effective-at= : The authenticated effective instant (RFC 3339) of a final state}
        {--script-query= : Script the next query answer for this intent id (with --state, or none)}
        {--script-send= : Script what sends answer: ack, nack or throw}
        {--idempotent : With --script-send, declare idempotent sends}
        {--fail-recheck= : Make this campaign id\'s next rechecks fail on a mandate cause}';

    protected $description = 'Prepare synthetic S3-D disbursement scenarios and provider events (local and testing only)';

    public function handle(SyntheticDisbursementGuard $guard, SyntheticDisbursementFixtures $fixtures, SyntheticPayoutScripts $scripts,
        OpenFundedDisbursements $open, RecordPayoutEvent $record): int
    {
        $state = $this->option('state');
        try {
            $guard->assertAllowed();
            if ($this->option('seed')) {
                $campaign = $fixtures->fund();
                $opened = $open->handle();
                $this->table(['Campaign', 'Business', 'Principal', 'Opened'], [[$campaign->campaignId, $campaign->businessId, 'RWF '.$campaign->principal, (string) $opened['opened']]]);
                $this->line('Queue: '.route('staff.disbursements.index', [], false));
            }
            if (is_string($this->option('fail-recheck'))) {
                $fixtures->scriptRecheck($this->option('fail-recheck'), 'failed', ['mandate']);
                $this->line('Rechecks for '.$this->option('fail-recheck').' now fail on: mandate');
            }
            $send = $this->option('script-send');
            if (is_string($send)) {
                if (! in_array($send, ['ack', 'nack', 'throw'], true)) {
                    $this->error('--script-send must be ack, nack or throw.');

                    return self::INVALID;
                }
                $scripts->scriptSend($send, (bool) $this->option('idempotent'));
                $this->line('Sends now answer '.$this->option('script-send').($this->option('idempotent') ? ' (idempotent)' : ''));
            }
            if (is_string($this->option('script-query'))) {
                $scripts->scriptQuery($this->option('script-query'), is_string($state) ? $state : null, $this->overrides());
                $this->line('Queries about '.$this->option('script-query').' now answer '.(is_string($state) ? $state : 'nothing'));
            }
            if (is_string($this->option('event'))) {
                if (! in_array($state, ['succeeded', 'failed', 'unknown', 'pending'], true)) {
                    $this->error('--event needs --state=succeeded, failed, unknown or pending.');

                    return self::INVALID;
                }
                $outcome = $record->handle($scripts->callback($this->option('event'), $state, $this->overrides()));
                $this->line('Callback: '.$outcome['disposition'].'; reconciliation '.$outcome['decision']);
            }
        } catch (LogicException|CommandRejection $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function overrides(): array
    {
        return array_filter(['event_id' => $this->option('event-id'), 'amount' => $this->option('amount'), 'effective_at' => $this->option('effective-at')], is_string(...));
    }
}
