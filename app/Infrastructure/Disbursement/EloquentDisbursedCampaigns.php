<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\DisbursedCampaigns;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use Brick\Math\BigInteger;

/**
 * Reads an issued closing with its disbursement and intent. The destination is the one the intent
 * was bound to (`authorized` or `auto_intent_recorded`), masked as recorded; the amount is the
 * principal less the platform fee the disbursement opened under.
 */
final class EloquentDisbursedCampaigns implements DisbursedCampaigns
{
    /** The disbursement receipts' policy, as `EloquentDisbursementStore` records them. */
    private const string POLICY_VERSION = 'brs-2.0-2026-10-16.1';

    public function find(string $campaignId): ?array
    {
        $disbursement = Disbursement::query()->where('business_campaign_id', $campaignId)->first();
        $closing = $disbursement === null ? null : DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->where('kind', 'issued')->first();
        $intent = $closing?->intent_id === null ? null : DisbursementIntent::query()->find($closing->intent_id);
        if ($disbursement === null || $closing === null || $intent === null || $closing->effective_at === null || $closing->effective_date === null) {
            return null;
        }
        $money = fn (BigInteger|string $amount): array => ['currency' => 'RWF', 'amount' => (string) $amount];
        $net = BigInteger::of($disbursement->amount)->minus($disbursement->platform_fee);
        $bound = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->whereIn('kind', ['authorized', 'auto_intent_recorded'])
            ->orderByDesc('revision')->first();

        return ['gross' => $money($disbursement->amount), 'platform_fee' => $money($disbursement->platform_fee), 'amount' => $money($net),
            'destination' => (string) ($bound->payload['destination']['masked'] ?? ''),
            'disbursement_effective_at' => $closing->effective_at->toIso8601String(), 'effective_date' => $closing->effective_date->format('Y-m-d'),
            'receipt' => ['receipt_id' => $closing->id, 'operation_id' => (string) ($closing->operation_id ?? $intent->operation_id),
                'request_id' => $intent->request_id, 'code' => 'DISBURSEMENT_ISSUED', 'recorded_at' => $closing->created_at->toIso8601String(),
                'amount' => $money($net), 'units' => null, 'reference' => 'DSB-'.strtoupper(substr($disbursement->id, -8)), 'revision' => 1,
                'policy_version' => self::POLICY_VERSION, 'disclosure_version' => null]];
    }
}
