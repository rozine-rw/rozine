<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use App\Models\BusinessProfile;
use RuntimeException;

/**
 * Immutable purchase projection for FundedCampaigns integration, including issue retries after
 * committed cash has left the wallets. It neither locks nor writes; the caller retains Business,
 * staff and campaign/disbursement gates before using these facts to settle. The existing Holding
 * source authenticates every acknowledged purchase replay; persisted Holdings, current admission
 * and authenticated closing authority remain separate requirements.
 * The Business name is a current display label and supplies no financial or mandate authority.
 */
final class RetainedFundedCampaignFacts
{
    public function __construct(private CampaignFundingEvidence $fundings, private PublishedCampaignEvidence $publications, private CanonicalJson $json, private HoldingSource $holdings) {}

    public function find(string $campaignId): ?FundedCampaign
    {
        $funding = $this->fundings->find($campaignId);
        if ($funding === null) {
            return null;
        }
        $publication = $this->publications->find($campaignId);
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->sole();
        foreach (['campaign_id', 'business_id', 'exposure_reservation_id', 'principal'] as $binding) {
            if (($funding[$binding] ?? null) !== ($publication[$binding] ?? null)) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
        }
        $termMonths = $publication['quote']['term_months'] ?? null;
        if (($funding['publication_sha256'] ?? null) !== $campaign->sha256 || ! is_int($termMonths)) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }
        $this->requireOriginalExposure($campaign, $publication);
        $commitments = array_map(function (array $purchase) use ($campaignId, $termMonths): FundedCommitment {
            if (($purchase['terms']['term_months'] ?? null) !== $termMonths) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
            $this->requireAcknowledgedPurchase($campaignId, $purchase);

            return new FundedCommitment($purchase['commitment_id'], $purchase['party_id'], $purchase['cash']['origin_operation_id'],
                (int) $purchase['units'], $purchase['ordinals'], $purchase['rights'], $purchase['terms'], $purchase['principal']);
        }, $funding['commitments']);
        usort($commitments, fn (FundedCommitment $left, FundedCommitment $right): int => strcmp($left->id, $right->id));
        $business = BusinessProfile::query()->whereKey($funding['business_id'])->sole();

        return new FundedCampaign($campaignId, $funding['business_id'], $business->profile['name'], $publication['title'],
            $funding['exposure_reservation_id'], $funding['principal'], $funding['recorded_at'], $termMonths, $commitments);
    }

    /** @param array<string, mixed> $purchase */
    private function requireAcknowledgedPurchase(string $campaignId, array $purchase): void
    {
        $facts = $this->holdings->facts($purchase['commitment_id']);
        $expected = ['business_campaign_id' => $campaignId, 'commitment_id' => $purchase['commitment_id'],
            'primary_reservation_id' => $purchase['reservation_id'], 'party_id' => $purchase['party_id'], 'units' => (int) $purchase['units'],
            'principal' => $purchase['principal'], 'ordinals' => array_map(fn (array $range): array => ['first' => (int) $range['first'], 'last' => (int) $range['last']], $purchase['ordinals']),
            'rights' => $purchase['rights'], 'terms' => $purchase['terms'], 'confirmation_version_id' => $purchase['confirmation_version_id']];
        if ($this->json->encode(array_intersect_key($facts, $expected)) !== $this->json->encode($expected)) {
            throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
        }
    }

    /** Historical acceptance only; current exposure totals and eligibility are separate gates.
     *
     * @param  array<string, mixed>  $publication
     */
    private function requireOriginalExposure(BusinessCampaign $campaign, array $publication): void
    {
        $exposure = BusinessExposureReservation::query()->whereKey($campaign->exposure_reservation_id)->first();
        $release = BusinessApplicationRelease::query()->whereKey($campaign->business_application_release_id)->first();
        if ($exposure === null || $release === null || $exposure->business_id !== $campaign->business_id
            || $exposure->business_application_id !== $campaign->business_application_id || $exposure->principal !== $campaign->principal
            || $release->business_id !== $campaign->business_id || $release->business_application_id !== $campaign->business_application_id
            || $release->exposure_reservation_id !== $exposure->id) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }
        $submission = BusinessApplicationSubmission::query()->whereKey($exposure->business_application_submission_id)->first();
        $quote = BusinessApplicationQuote::query()->whereKey($exposure->payload['quote_id'] ?? '')->first();
        if ($submission === null || $quote === null || $submission->business_application_id !== $campaign->business_application_id
            || $quote->business_application_id !== $campaign->business_application_id || $submission->business_application_quote_id !== $quote->id) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }
        foreach ([$exposure, $release, $submission, $quote] as $record) {
            if (! hash_equals($record->sha256, hash('sha256', $this->json->encode($record->payload)))) {
                throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
            }
        }
        $binding = $publication['binding']['application'] ?? [];
        $accepted = $submission->payload;
        $priced = $quote->payload;
        $released = $release->payload;
        if (! is_array($binding) || ! is_array($released['binding'] ?? null) || ! is_array($publication['binding'] ?? null)
            || ! is_array($binding['submission'] ?? null) || ! is_array($binding['quote'] ?? null)) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }
        if (($accepted['submission_id'] ?? null) !== $submission->id || ($accepted['application_id'] ?? null) !== $campaign->business_application_id
            || ($accepted['application_revision'] ?? null) !== $submission->revision || ($accepted['quote_id'] ?? null) !== $quote->id
            || ($accepted['binding_sha256'] ?? null) !== $submission->binding_sha256 || ! is_array($accepted['agreement'] ?? null)
            || ! hash_equals($submission->binding_sha256, hash('sha256', $this->json->encode($accepted['agreement'])))
            || ($priced['quote_id'] ?? null) !== $quote->id || ($priced['quote_revision'] ?? null) !== $quote->revision
            || ($priced['application_id'] ?? null) !== $campaign->business_application_id || ($priced['business_id'] ?? null) !== $campaign->business_id
            || ($priced['result']['capacity']['offer']['principal']['amount'] ?? null) !== $campaign->principal
            || ($released['release_id'] ?? null) !== $release->id || ($released['business_id'] ?? null) !== $release->business_id
            || ($released['application_id'] ?? null) !== $release->business_application_id || ($released['exposure_reservation_id'] ?? null) !== $exposure->id
            || ($released['actor_user_id'] ?? null) !== $release->actor_user_id
            || $this->json->encode($released['binding']) !== $this->json->encode($publication['binding'])
            || ($publication['binding']['agreement_sha256'] ?? null) !== $submission->binding_sha256
            || $this->json->encode($binding['submission']) !== $this->json->encode(['id' => $submission->id, 'sha256' => $submission->sha256, 'submitted_at' => $accepted['submitted_at'] ?? null])
            || $this->json->encode($binding['quote']) !== $this->json->encode(['id' => $quote->id, 'revision' => $quote->revision, 'sha256' => $quote->sha256])) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }
        $expected = ['reservation_id' => $exposure->id, 'business_id' => $campaign->business_id, 'application_id' => $campaign->business_application_id,
            'submission_id' => $submission->id, 'submission_sha256' => $submission->sha256, 'quote_id' => $quote->id, 'quote_sha256' => $quote->sha256,
            'principal' => $campaign->principal, 'accepted_at' => $accepted['submitted_at'] ?? null, 'policy_version' => $priced['policy_version'] ?? null];
        if ($this->json->encode($exposure->payload) !== $this->json->encode($expected)) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }
    }
}
