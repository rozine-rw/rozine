<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use LogicException;
use Throwable;

/**
 * The scheduled reconciler. For each dispatched payout with no closing it records a query call,
 * asks the provider about the same operation outside every transaction, keeps whatever
 * authenticated answer came back, and runs the deterministic reconciliation transaction. It never
 * sends, and it applies a sent, verified outcome without any new staff approval.
 */
final class ReconcileDisbursements
{
    public function __construct(private DisbursementStore $store, private PayoutProvider $provider, private SyntheticDisbursementGuard $guard) {}

    /** @return array{queried: int, observed: int, decisions: array<string, int>} */
    public function handle(?string $intentId = null, int $limit = 25): array
    {
        $this->guard->assertAllowed();
        if ($this->store->transactionOpen()) {
            throw new LogicException('DISBURSEMENT_RECONCILE_TRANSACTION_OPEN: a provider is asked only outside every transaction.');
        }
        $observed = 0;
        $decisions = [];
        $due = $this->store->queryDue($intentId, $limit);
        foreach ($due as $instruction) {
            try {
                $event = $this->provider->query($instruction);
            } catch (Throwable) {
                $event = null;
            }
            if ($event !== null) {
                $this->store->observe($event, 'query', $instruction->intentId);
                $observed++;
            }
            $decision = $this->store->reconcile($instruction->intentId);
            $decisions[$decision] = ($decisions[$decision] ?? 0) + 1;
        }

        return ['queried' => count($due), 'observed' => $observed, 'decisions' => $decisions];
    }
}
