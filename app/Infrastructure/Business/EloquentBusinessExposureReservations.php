<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessExposureReservation;
use App\Models\BusinessProfile;
use Brick\Math\BigRational;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** @phpstan-import-type Commitment from \App\Domain\Underwriting\AcceptedExposure */
final class EloquentBusinessExposureReservations
{
    public function __construct(private CanonicalJson $json) {}

    /**
     * Internal source read; the enclosing application command retains the Business lock
     * through quote calculation or the final signature. Never use a client total.
     *
     * @return list<Commitment>
     */
    public function current(string $businessId): array
    {
        return DB::transaction(function () use ($businessId): array {
            BusinessProfile::query()->lockForUpdate()->findOrFail($businessId);

            return array_values(BusinessExposureReservation::query()->where('business_id', $businessId)->orderBy('id')->get()
                ->map(function (BusinessExposureReservation $record): array {
                    $payload = $record->payload;
                    if (! hash_equals($record->sha256, hash('sha256', $this->json->encode($payload)))
                        || $payload['reservation_id'] !== $record->id || $payload['business_id'] !== $record->business_id
                        || $payload['application_id'] !== $record->business_application_id
                        || $payload['submission_id'] !== $record->business_application_submission_id
                        || $payload['principal'] !== $record->principal) {
                        throw new RuntimeException('BUSINESS_EXPOSURE_INTEGRITY_FAILED');
                    }

                    return ['id' => $record->id, 'principal' => $record->principal];
                })->all());
        });
    }

    /** The caller has verified every required signature and holds current source/authority locks. */
    public function reserve(BusinessApplication $application, BusinessApplicationSubmission $submission, BusinessApplicationQuote $quote): void
    {
        $current = $this->current($application->business_id);
        if (($quote->payload['accepted_commitments'] ?? []) !== $current) {
            throw new CommandRejection('QUOTE_STALE', 409, $application->revision);
        }
        $principal = $quote->payload['result']['capacity']['offer']['principal']['amount'];
        $room = $quote->payload['result']['cash_flow']['remaining_room'];
        if (ExactFinancialValue::amount($principal)->toBigRational()->isGreaterThan(BigRational::ofFraction($room['numerator'], $room['denominator']))) {
            throw new CommandRejection('EXPOSURE_LIMIT', 422, $application->revision);
        }
        $record = new BusinessExposureReservation;
        $record->id = $submission->id;
        $payload = ['reservation_id' => $record->id, 'business_id' => $application->business_id,
            'application_id' => $application->id, 'submission_id' => $submission->id, 'submission_sha256' => $submission->sha256,
            'quote_id' => $quote->id, 'quote_sha256' => $quote->sha256, 'principal' => $principal,
            'accepted_at' => $submission->payload['submitted_at'], 'policy_version' => $quote->payload['policy_version']];
        $record->forceFill(['business_id' => $application->business_id, 'business_application_id' => $application->id,
            'business_application_submission_id' => $submission->id, 'principal' => $principal, 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload))])->save();
    }
}
