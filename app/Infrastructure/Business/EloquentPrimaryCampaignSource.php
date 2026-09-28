<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\LoanSchedule;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use App\Models\BusinessExposureReservation;
use App\Models\BusinessProfile;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

/** @phpstan-import-type CampaignInput from PrimaryCampaignSource */
final class EloquentPrimaryCampaignSource implements PrimaryCampaignSource
{
    public function __construct(private PublishedCampaignEvidence $publications, private CampaignClosureEvidence $closures,
        private BusinessExposureStore $exposures, private CanonicalJson $json) {}

    public function lockBusiness(string $campaignId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('PRIMARY_TRANSACTION_REQUIRED');
        }
        $candidate = BusinessCampaign::query()->whereKey($campaignId)->first()
            ?? throw new CommandRejection('CAMPAIGN_NOT_FOUND', 404);
        BusinessProfile::query()->whereKey($candidate->business_id)->lockForUpdate()->firstOrFail();
    }

    /** @return CampaignInput */
    public function lock(string $campaignId): array
    {
        $this->lockBusiness($campaignId);
        $campaign = BusinessCampaign::query()->whereKey($campaignId)->lockForUpdate()->firstOrFail();
        $payload = $this->publications->find($campaign->id);
        if ($this->closures->find($campaign->id) !== null || now()->lt($campaign->live_at) || now()->gte($campaign->expires_at)) {
            throw new CommandRejection('CAMPAIGN_CLOSED');
        }
        $release = BusinessApplicationRelease::query()->whereKey($campaign->business_application_release_id)->firstOrFail();
        $retained = $release->payload;
        if (! hash_equals($release->sha256, hash('sha256', $this->json->encode($retained)))
            || ($retained['release_id'] ?? null) !== $release->id
            || ($retained['business_id'] ?? null) !== $campaign->business_id || $release->business_id !== $campaign->business_id
            || ($retained['application_id'] ?? null) !== $campaign->business_application_id || $release->business_application_id !== $campaign->business_application_id
            || ($retained['exposure_reservation_id'] ?? null) !== $campaign->exposure_reservation_id || $release->exposure_reservation_id !== $campaign->exposure_reservation_id
            || ($retained['actor_user_id'] ?? null) !== $release->actor_user_id
            || $this->json->encode($retained['binding'] ?? []) !== $this->json->encode($payload['binding'] ?? [])
            || ($payload['binding']['reservation_id'] ?? null) !== $campaign->exposure_reservation_id
            || ($payload['binding']['principal'] ?? null) !== $campaign->principal) {
            throw new RuntimeException('APPLICATION_RELEASE_INTEGRITY_FAILED');
        }
        $exposure = array_find($this->exposures->current($campaign->business_id), fn (array $item): bool => $item['id'] === $campaign->exposure_reservation_id);
        $reservation = BusinessExposureReservation::query()->whereKey($campaign->exposure_reservation_id)->firstOrFail();
        $quoteBinding = $payload['binding']['application']['quote'] ?? [];
        if ($exposure === null || $exposure['principal'] !== $campaign->principal
            || ($reservation->payload['quote_id'] ?? null) !== ($quoteBinding['id'] ?? null)
            || ($reservation->payload['quote_sha256'] ?? null) !== ($quoteBinding['sha256'] ?? null)) {
            throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
        }

        return ['id' => $campaign->id, 'business_id' => $campaign->business_id, 'application_id' => $campaign->business_application_id,
            'exposure_reservation_id' => $campaign->exposure_reservation_id, 'publication_sha256' => $campaign->sha256,
            ...$this->terms($campaign, $payload), 'live_at' => $campaign->live_at->toDateTimeImmutable(), 'expires_at' => $campaign->expires_at->toDateTimeImmutable()];
    }

    /**
     * @param  array<string, mixed>  $publication
     * @return array{principal: string, units: string, rate_pct: string, term_months: int, policy_version: string, payments: list<string>}
     */
    private function terms(BusinessCampaign $campaign, array $publication): array
    {
        $disclosed = $publication['quote'] ?? [];
        $quote = BusinessApplicationQuote::query()->where('business_application_id', $campaign->business_application_id)
            ->whereKey($disclosed['quote_id'] ?? '')->first();
        if ($quote === null) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        $payload = $quote->payload;
        $binding = $publication['binding']['application']['quote'] ?? [];
        $offer = $payload['result']['capacity']['offer'] ?? [];
        $principal = $campaign->principal;
        $tenor = $payload['draft']['term_months'] ?? null;
        $rate = $payload['result']['pricing']['percent'] ?? null;
        $policy = $payload['policy_version'] ?? null;
        if (! hash_equals($quote->sha256, hash('sha256', $this->json->encode($payload)))
            || ($binding['id'] ?? null) !== $quote->id || ($binding['revision'] ?? null) !== $quote->revision || ($binding['sha256'] ?? null) !== $quote->sha256
            || ($payload['result']['eligible'] ?? null) !== true || ($disclosed['status'] ?? null) !== 'ready'
            || ! is_int($tenor) || ! in_array($tenor, [3, 4, 5, 6], true) || ! is_string($rate) || ! preg_match('/^(1[0-4]\.[0-9]|15\.0)$/D', $rate)
            || ! is_string($policy) || trim($policy) === '' || ! preg_match('/^[1-9][0-9]{6,8}$/D', $principal)
            || BigInteger::of($principal)->isLessThan(3000000) || BigInteger::of($principal)->isGreaterThan(100000000) || ! BigInteger::of($principal)->mod(5000)->isZero()) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        $schedule = LoanSchedule::build($principal, $tenor, BigRational::of($rate)->dividedBy(100));
        $money = $schedule->toArray();
        $payments = array_map(strval(...), $schedule->instalments);
        $instalments = array_map(fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount], $payments);
        $units = (string) BigInteger::of($principal)->dividedBy(5000);
        $expected = ['status' => 'ready', 'quote_id' => $quote->id, 'quote_revision' => $quote->revision,
            'policy_version' => $policy, 'principal' => $money['principal'], 'term_months' => $tenor, 'rate_pct' => $rate,
            'interest' => $money['contractual_return'], 'total' => $money['total'], 'units' => $units,
            'unit_price' => ['currency' => 'RWF', 'amount' => '5000'],
            'schedule' => array_map(fn (int $index, array $amount): array => ['instalment' => $index + 1, 'amount' => $amount], array_keys($instalments), $instalments)];
        if ($this->json->encode(array_intersect_key($offer, $money)) !== $this->json->encode($money)
            || ($offer['units'] ?? null) !== $units
            || $this->json->encode(array_intersect_key($disclosed, $expected)) !== $this->json->encode($expected)) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }

        return ['principal' => $principal, 'units' => $units, 'rate_pct' => $rate, 'term_months' => $tenor,
            'policy_version' => $policy, 'payments' => $payments];
    }
}
