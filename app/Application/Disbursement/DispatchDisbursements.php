<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use LogicException;
use Throwable;

/**
 * Sends recorded payout intents from the outbox, only after the outermost commit and outside every
 * lock (#172's P1 lesson). Each claim commits after a fresh recheck under the full lock chain,
 * including the recorded maker and checker; a failed recheck closes deterministically before any
 * send. An interrupted claim is resent only when the provider guarantees idempotent sends and no
 * outcome has been observed; otherwise it stays unknown for the reconciler. A send that throws is
 * recorded unsent and never retried automatically.
 */
final class DispatchDisbursements
{
    public function __construct(private DisbursementStore $store, private PayoutProvider $provider, private SyntheticDisbursementGuard $guard) {}

    /** @return array{claimed: int, sent: int} */
    public function handle(?string $intentId = null, int $limit = 25): array
    {
        $this->guard->assertAllowed();
        if ($this->store->transactionOpen()) {
            throw new LogicException('DISBURSEMENT_DISPATCH_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
        $claims = $this->store->claim($intentId, $limit, $this->provider->idempotentSends());
        $sent = 0;
        foreach ($claims as $claim) {
            try {
                $acknowledged = $this->provider->send($claim['instruction']);
            } catch (Throwable) {
                $acknowledged = false;
            }
            $this->store->recordDispatch($claim['instruction']->intentId, $acknowledged);
            $sent += $acknowledged ? 1 : 0;
        }

        return ['claimed' => count($claims), 'sent' => $sent];
    }
}
