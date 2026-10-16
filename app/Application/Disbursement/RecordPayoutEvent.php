<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;

/**
 * An authenticated provider callback. It is verified by the provider adapter, retained whatever
 * it says, and then reconciled deterministically; arrival time is never an effective time.
 */
final class RecordPayoutEvent
{
    public function __construct(private DisbursementStore $store, private PayoutProvider $provider) {}

    /**
     * @param  array<string, mixed>  $message
     * @return array{disposition: string, decision: string}
     */
    public function handle(array $message): array
    {
        $recorded = $this->store->observe($this->provider->verify($message), 'callback');

        return ['disposition' => $recorded['disposition'], 'decision' => $this->store->reconcile($recorded['intent_id'])];
    }
}
