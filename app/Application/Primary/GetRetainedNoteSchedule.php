<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use App\Domain\Wallet\WalletMoney;
use RuntimeException;

/**
 * SELECT-only projection through the complete authenticated issued source, in the caller's open
 * transaction. No cache, locks, transaction ownership, current pricing or fee arithmetic. Null is
 * reserved for no issuance; source unavailability and integrity exceptions propagate unchanged.
 */
final readonly class GetRetainedNoteSchedule
{
    public function __construct(private IssuedHoldingEvidence $evidence) {}

    public function find(string $campaignId): ?RetainedNoteSchedule
    {
        $bundle = $this->evidence->find($campaignId);
        if ($bundle === null) {
            return null;
        }
        $closing = new ClosingEvidence(...$bundle['closing']);
        $this->require($bundle['campaign_id'] === $campaignId && $closing->campaignId === $campaignId
            && $bundle['business_id'] === $closing->businessId && $closing->kind === 'issued' && $closing->currency === 'RWF'
            && $closing->dueDates !== null && count($closing->dueDates) === $closing->termMonths
            && $bundle['issued_at'] === $closing->recordedAt && count($bundle['issued_holdings']) === $closing->commitmentCount
            && $closing->commitmentCount > 0);
        $certificates = array_column($bundle['holdings'], null, 'id');
        $this->require(count($certificates) === $closing->commitmentCount);
        $holdings = [];
        $principal = WalletMoney::zero();
        $contractualReturn = WalletMoney::zero();
        $components = array_fill(0, $closing->termMonths, ['principal' => WalletMoney::zero(), 'return' => WalletMoney::zero()]);
        $seen = [];
        foreach ($bundle['issued_holdings'] as $holding) {
            $certificate = $certificates[$holding['holding_id']] ?? null;
            $this->require($certificate !== null && $certificate['commitment_id'] === $holding['commitment_id']
                && ! isset($seen[$holding['commitment_id']]) && $holding['business_campaign_id'] === $campaignId
                && $holding['funding_id'] === $bundle['funding_id'] && $holding['disbursement_closing_id'] === $closing->closingId
                && $holding['closing_sha256'] === $closing->closingSha256 && $holding['issued_at'] === $closing->recordedAt
                && $holding['disbursement_effective_at'] === $closing->effectiveAt && $holding['effective_date'] === $closing->effectiveDate
                && $holding['terms']['term_months'] === $closing->termMonths && count($holding['schedule']) === $closing->termMonths);
            $seen[$holding['commitment_id']] = true;
            $instalments = [];
            $holdingPrincipal = WalletMoney::zero();
            $holdingReturn = WalletMoney::zero();
            foreach ($holding['schedule'] as $offset => $row) {
                $this->require($row['index'] === $offset + 1 && $row['due_date'] === $closing->dueDates[$offset]
                    && $row === [...$holding['rights']['instalments'][$offset], 'due_date' => $closing->dueDates[$offset]]);
                $principalComponent = $this->money($row['principal']);
                $returnComponent = $this->money($row['return']);
                $instalments[] = new RetainedNoteInstalment($row['index'], $row['due_date'], $principalComponent->amount(), $returnComponent->amount(), $principalComponent->plus($returnComponent)->amount());
                $holdingPrincipal = $holdingPrincipal->plus($principalComponent);
                $holdingReturn = $holdingReturn->plus($returnComponent);
                $component = $components[$offset];
                $components[$offset] = ['principal' => $component['principal']->plus($principalComponent), 'return' => $component['return']->plus($returnComponent)];
            }
            $this->require($holdingPrincipal->amount() === $holding['principal']
                && $holdingReturn->compareTo($this->money($holding['rights']['total_return'])) === 0);
            $holdings[] = new RetainedHoldingSchedule($holding['holding_id'], $certificate['sha256'], $holding['commitment_id'],
                $holding['primary_reservation_id'], $holding['party_id'], $holding['units'], $holding['ordinals'], $holding['reservation_sha256'],
                $holding['confirmation_version_id'], $holding['confirmation_revision'], $holding['confirmation_sha256'],
                $holding['rights'], $holding['terms'], $holdingPrincipal->amount(), $holdingReturn->amount(), $holdingPrincipal->plus($holdingReturn)->amount(), $instalments);
            $principal = $principal->plus($holdingPrincipal);
            $contractualReturn = $contractualReturn->plus($holdingReturn);
        }
        $this->require($principal->amount() === $closing->amount);
        $instalments = [];
        foreach ($components as $offset => $component) {
            $instalments[] = new RetainedNoteInstalment($offset + 1, $closing->dueDates[$offset], $component['principal']->amount(),
                $component['return']->amount(), $component['principal']->plus($component['return'])->amount());
        }

        return new RetainedNoteSchedule($campaignId, $bundle['business_id'], $bundle['funding_id'], $closing, $bundle['issued_at'],
            $principal->amount(), $contractualReturn->amount(), $principal->plus($contractualReturn)->amount(), $holdings, $instalments);
    }

    /** @param array{currency: string, amount: string} $money */
    private function money(array $money): WalletMoney
    {
        $this->require($money['currency'] === 'RWF' && preg_match('/^(0|[1-9][0-9]{0,19})$/D', $money['amount']) === 1);

        return WalletMoney::of($money['amount']);
    }

    private function require(bool $condition): void
    {
        if (! $condition) {
            throw new RuntimeException('NOTE_SCHEDULE_INTEGRITY_FAILED');
        }
    }
}
