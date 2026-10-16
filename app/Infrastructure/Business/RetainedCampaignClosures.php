<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Domain\Disbursement\IntentDigest;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use Brick\Math\BigInteger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Verifies closure evidence before it changes the campaign view or accepted exposure. */
final class RetainedCampaignClosures implements CampaignClosureEvidence
{
    public function __construct(private CanonicalJson $json, private CampaignFundingEvidence $funded, private DisbursementClosingEvidence $closings) {}

    /** @return array<string, mixed>|null */
    public function find(string $campaignId): ?array
    {
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->firstOrFail();
        $closure = BusinessCampaignClosure::query()->where('business_campaign_id', $campaign->id)->first();
        if ($closure !== null) {
            $this->verify($closure, $campaign);
        }

        return $closure?->payload;
    }

    /** @return array<string, string> */
    public function released(string $businessId): array
    {
        $closed = BusinessCampaignClosure::query()->where('business_id', $businessId)->get();
        $campaigns = BusinessCampaign::query()->whereIn('id', $closed->pluck('business_campaign_id'))->get()->keyBy('id');
        $released = [];
        foreach ($closed as $closure) {
            $this->verify($closure, $campaigns->get($closure->business_campaign_id) ?? throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED'));
            $released[$closure->exposure_reservation_id] = $closure->principal;
        }

        return $released;
    }

    private function verify(BusinessCampaignClosure $closure, BusinessCampaign $campaign): void
    {
        $payload = $closure->payload;
        if (! hash_equals($closure->sha256, hash('sha256', $this->json->encode($payload)))
            || ! hash_equals($campaign->sha256, hash('sha256', $this->json->encode($campaign->payload)))
            || ($payload['closure_id'] ?? null) !== $closure->id
            || ($payload['campaign_id'] ?? null) !== $campaign->id || $closure->business_campaign_id !== $campaign->id
            || ($payload['campaign_sha256'] ?? null) !== $campaign->sha256
            || ($payload['business_id'] ?? null) !== $campaign->business_id || $closure->business_id !== $campaign->business_id
            || ($payload['exposure_reservation_id'] ?? null) !== $campaign->exposure_reservation_id || $closure->exposure_reservation_id !== $campaign->exposure_reservation_id
            || ($payload['principal_released'] ?? null) !== $campaign->principal || $closure->principal !== $campaign->principal
            || ($payload['phase'] ?? null) !== $closure->phase || ! in_array($closure->phase, ['cancelled', 'expired', 'failed_closing'], true)
            || ($payload['closed_at'] ?? null) !== $closure->closed_at->toIso8601String()
            || ($payload['actor_user_id'] ?? null) !== $closure->actor_user_id
            || ($payload['revision'] ?? null) !== 2) {
            throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
        }
        try {
            DB::select('SELECT check_primary_campaign_closure_returns(?, false)', [$closure->id]);
        } catch (QueryException $exception) {
            throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED', previous: $exception);
        }
        if ($closure->phase === 'failed_closing') {
            DB::transaction(function () use ($closure, $payload, $campaign): void {
                $facts = $this->funded->find($campaign->id) ?? throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
                $closing = $payload['closing'] ?? [];
                if (! is_array($closing) || ! is_string($closing['disbursementId'] ?? null) || ! is_string($closing['cause'] ?? null)
                    || ! is_array($closing['causes'] ?? null)) {
                    throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
                }
                $evidence = $this->closings->find($closure->id);
                $commitments = array_map(fn (array $purchase): array => (new FundedCommitment($purchase['commitment_id'], $purchase['party_id'],
                    $purchase['cash']['origin_operation_id'], (int) $purchase['units'], $purchase['ordinals'], $purchase['rights'],
                    $purchase['terms'], $purchase['principal']))->toArray(), $facts['commitments']);
                usort($commitments, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));
                if ([$evidence->kind, $evidence->campaignId, $evidence->businessId, $evidence->exposureReservationId, $evidence->amount,
                    $evidence->currency, $evidence->commitmentCount, $evidence->commitmentsDigest, $payload['funding_id'] ?? null]
                    !== ['failed_closing', $campaign->id, $campaign->business_id, $campaign->exposure_reservation_id, $campaign->principal,
                        'RWF', count($commitments), IntentDigest::commitments($commitments), $facts['funding_id']]) {
                    throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
                }
                if ($this->json->encode($closing) !== $this->json->encode(get_object_vars($evidence))) {
                    throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
                }
            });
        }
        $bindings = DB::table('primary_campaign_closure_returns')->where('business_campaign_closure_id', $closure->id)
            ->orderBy('primary_reservation_id')->get()->map(function (object $row): array {
                $binding = (array) $row;
                unset($binding['business_campaign_closure_id']);

                return $binding;
            })->all();
        $refunded = BigInteger::zero();
        $released = BigInteger::zero();
        $parties = [];
        foreach ($bindings as $binding) {
            if ($binding['return_kind'] === 'primary_refund') {
                $refunded = $refunded->plus($binding['principal']);
                $parties[$binding['party_id']] = true;
            } else {
                $released = $released->plus($binding['principal']);
            }
        }
        $legacy = ($payload['scope'] ?? null) === 'unfunded-v1' && $bindings === [];
        if ((! $legacy && (($payload['scope'] ?? null) !== ($closure->phase === 'failed_closing' ? 'funded-failed-closing-v1' : 'unfunded-returned-v1')
                || ! is_array($payload['cash_returns'] ?? null) || ! array_is_list($payload['cash_returns'])
                || $this->json->encode(['returns' => $payload['cash_returns']]) !== $this->json->encode(['returns' => $bindings])
                || ($payload['released_held'] ?? null) !== ['currency' => 'RWF', 'amount' => (string) $released]))
            || ($payload['committed_refunded'] ?? null) !== ['currency' => 'RWF', 'amount' => (string) $refunded]
            || ($payload['investors'] ?? null) !== count($parties)) {
            throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
        }
    }
}
