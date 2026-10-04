<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\GetIdentityContext;
use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryCommitmentView;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Domain\Operations\CommandRejection;
use RuntimeException;

/**
 * Reads the commitment through the Investor-authorized retained facts first; only then the
 * Party's own recorded confirmation and refund, and the campaign's title and lifecycle.
 *
 * @phpstan-import-type OperationRecord from OperationRecords
 */
final readonly class EloquentPrimaryCommitmentView implements PrimaryCommitmentView
{
    public function __construct(private PrimaryPurchaseIndex $index, private PrimaryCheckout $checkout, private PrimaryCampaignSource $campaigns,
        private CampaignFundingEvidence $fundings, private OperationRecords $operations, private GetIdentityContext $identity) {}

    public function show(int $userId, ?int $contextRevision, string $commitmentId): array
    {
        $target = $this->index->commitment($commitmentId);
        $context = $contextRevision ?? (int) $this->identity->handle($userId)['context_revision'];
        $facts = $this->checkout->commitmentFacts($userId, $context, $target['campaign_id'], $commitmentId);
        $confirmation = $facts['confirmation'];
        if ($confirmation === null || $confirmation['commitment_id'] !== $commitmentId || $facts['reservation_id'] !== $target['reservation_id']) {
            throw new RuntimeException('COMMITMENT_INTEGRITY_FAILED');
        }
        $actor = 'party:'.$facts['party_id'];
        $confirmed = array_find($this->operations->forTarget($actor, 'primary.confirm', 'primary_reservation', $facts['reservation_id']),
            fn (array $operation): bool => $operation['operation_id'] === $confirmation['operation_id']);
        if ($confirmed === null || ($confirmed['result']['status'] ?? null) !== 'completed'
            || ($confirmed['result']['data']['commitment_id'] ?? null) !== $commitmentId) {
            throw new RuntimeException('COMMITMENT_INTEGRITY_FAILED');
        }
        $refunds = array_values(array_filter($this->operations->forTarget($actor, 'primary.refund', 'primary_reservation', $facts['reservation_id']),
            fn (array $operation): bool => ($operation['result']['status'] ?? null) === 'completed'));
        $refund = $refunds[0] ?? null;
        if (count($refunds) > 1 || ($refund !== null && (($refund['result']['data']['commitment_id'] ?? null) !== $commitmentId
            || ($refund['result']['data']['amount'] ?? null) !== $facts['principal']['amount']))) {
            throw new RuntimeException('COMMITMENT_INTEGRITY_FAILED');
        }
        $campaign = $this->campaigns->presentation($facts['campaign_id']);
        if ($refund === null && ($campaign['closed'] || $this->fundings->find($facts['campaign_id']) !== null)) {
            throw new CommandRejection('COMMITMENT_STATE_UNAVAILABLE', 409);
        }
        $units = $facts['units'];

        return ['identity_context_revision' => $context, 'commitment' => ['id' => $commitmentId, 'revision' => $facts['revision'],
            'campaign_id' => $facts['campaign_id'], 'reservation_id' => $facts['reservation_id'], 'deal_name' => $campaign['title'],
            'units' => $units, 'ordinals' => $facts['ordinal_ranges'], 'principal' => $facts['principal'], 'rights' => $facts['rights'],
            'state' => $refund === null ? 'confirmed' : 'cancelled', 'cancelled_by' => $refund === null ? null : 'investor', 'closing' => null,
            'terms' => $facts['terms'],
            'confirmation' => $this->receipt($confirmed, $commitmentId, 'RZC-', $confirmation['confirmed_at'], $facts['principal'], $units,
                $facts['terms']['policy_version'], $facts['terms']['disclosure_version']),
            'refund' => $refund === null ? null : $this->receipt($refund, $refund['operation_id'], 'RZF-', $refund['recorded_at'],
                ['currency' => 'RWF', 'amount' => $facts['principal']['amount']], $units, (string) $refund['result']['policy_version'], null),
            'holding' => null, 'allowed_actions' => []]];
    }

    /**
     * @param  OperationRecord  $operation
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    private function receipt(array $operation, string $receiptId, string $prefix, string $recordedAt, array $amount, string $units,
        string $policyVersion, ?string $disclosureVersion): array
    {
        return ['receipt_id' => $receiptId, 'operation_id' => $operation['operation_id'], 'request_id' => $operation['request_id'],
            'code' => $operation['result']['code'], 'recorded_at' => $recordedAt, 'amount' => $amount, 'units' => $units,
            'reference' => $prefix.strtoupper(substr($receiptId, -10)), 'revision' => $operation['result']['revision'],
            'policy_version' => $policyVersion, 'disclosure_version' => $disclosureVersion];
    }
}
