<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use RuntimeException;

/** Original underwriting evidence for an authorized staff read, never current release authority. */
final class RetainedStaffUnderwritingBasis
{
    public function __construct(private CanonicalJson $json) {}

    /** @return array<string, mixed> */
    public function project(BusinessApplication $application, BusinessApplicationSubmission $submission): array
    {
        $payload = $this->authenticated($submission, 'APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        $agreement = $payload['agreement'] ?? null;
        $review = Arr::get($payload, 'review.application');
        $reviewQuote = Arr::get($payload, 'review.quote');
        if (! is_array($agreement) || ! is_array($review) || ! is_array($reviewQuote)
            || $application->current_submission_id !== $submission->id
            || $submission->business_application_id !== $application->id
            || ($payload['submission_id'] ?? null) !== $submission->id
            || ($payload['application_id'] ?? null) !== $application->id
            || ($payload['application_revision'] ?? null) !== $submission->revision
            || ($payload['quote_id'] ?? null) !== $submission->business_application_quote_id
            || ($payload['binding_sha256'] ?? null) !== $submission->binding_sha256
            || ($agreement['application_id'] ?? null) !== $application->id
            || ($agreement['quote_id'] ?? null) !== $submission->business_application_quote_id
            || ($review['id'] ?? null) !== $application->id || ($review['business_id'] ?? null) !== $application->business_id
            || ($review['revision'] ?? null) !== $submission->revision
            || ! hash_equals($submission->binding_sha256, hash('sha256', $this->json->encode($agreement)))) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }
        $quote = BusinessApplicationQuote::query()->find($submission->business_application_quote_id);
        if ($quote === null) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        $original = $this->authenticated($quote, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
        $result = $original['result'] ?? null;
        if (! is_array($result) || $quote->business_application_id !== $application->id
            || ($original['quote_id'] ?? null) !== $quote->id || ($original['quote_revision'] ?? null) !== $quote->revision
            || ($original['application_id'] ?? null) !== $application->id || ($original['business_id'] ?? null) !== $application->business_id
            || ($agreement['quote_revision'] ?? null) !== $quote->revision
            || ($agreement['quote_sha256'] ?? null) !== $quote->sha256
            || ($reviewQuote['quote_id'] ?? null) !== $quote->id || ($reviewQuote['quote_revision'] ?? null) !== $quote->revision
            || ! is_int($original['application_revision'] ?? null) || $original['application_revision'] < 1
            || $original['application_revision'] >= $submission->revision
            || ! is_array($original['draft'] ?? null) || ! is_array($review['draft'] ?? null)
            || $this->json->encode($original['draft']) !== $this->json->encode($review['draft'])
            || ($result['eligible'] ?? null) !== true
            || ($result['policy_version'] ?? null) !== ($original['policy_version'] ?? null)
            || ($result['calculation_version'] ?? null) !== ($original['calculation_version'] ?? null)
            || ($reviewQuote['policy_version'] ?? null) !== ($original['policy_version'] ?? null)
            || ($reviewQuote['calculation_version'] ?? null) !== ($original['calculation_version'] ?? null)
            || Arr::get($reviewQuote, 'requested_principal.amount') !== ($original['draft']['target'] ?? null)
            || ($reviewQuote['term_months'] ?? null) !== ($original['draft']['term_months'] ?? null)
            || ($reviewQuote['rate_pct'] ?? null) !== Arr::get($result, 'pricing.percent')
            || ($reviewQuote['principal'] ?? null) !== Arr::get($result, 'capacity.offer.principal')
            || ($reviewQuote['offered_principal'] ?? null) !== Arr::get($result, 'maximum_capacity.offer.principal')) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        $scorecard = $this->scorecard($result['scorecard'] ?? null);
        if ($scorecard['rating'] !== Arr::get($payload, 'public_evidence.rating.score')
            || mb_strtolower($scorecard['band']) !== Arr::get($payload, 'public_evidence.rating.band')
            || mb_strtolower($scorecard['band']) !== Arr::get($reviewQuote, 'rate_basis.band')) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        foreach ([$original['policy_version'] ?? null, $original['calculation_version'] ?? null,
            $original['evaluated_at'] ?? null, $agreement['policy_version'] ?? null] as $value) {
            if (! is_string($value) || $value === '') {
                throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
            }
        }

        return ['contract_version' => 'retained-underwriting-basis-v1',
            'application' => ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $submission->revision],
            'submission' => ['id' => $submission->id, 'revision' => $submission->revision, 'sha256' => $submission->sha256,
                'binding_sha256' => $submission->binding_sha256],
            'quote' => ['id' => $quote->id, 'revision' => $quote->revision, 'sha256' => $quote->sha256,
                'application_revision' => $original['application_revision'], 'evaluated_at' => $original['evaluated_at']],
            'pricing_policy_version' => $original['policy_version'], 'calculation_version' => $original['calculation_version'],
            'acceptance_policy_version' => $agreement['policy_version'], 'scorecard' => $scorecard];
    }

    /** @return array<string, mixed> */
    private function authenticated(BusinessApplicationSubmission|BusinessApplicationQuote $record, string $failure): array
    {
        try {
            $payload = $record->getAttributeValue('payload');
            if (! is_array($payload) || ! hash_equals($record->sha256, hash('sha256', $this->json->encode($payload)))) {
                throw new RuntimeException($failure);
            }
        } catch (DecryptException|CommandRejection $exception) {
            throw new RuntimeException($failure, previous: $exception);
        }

        return $payload;
    }

    /** @return array{scorecard_version: string, rating: string, band: string, score: array{numerator: string, denominator: string}, uncapped_score: array{numerator: string, denominator: string}, reason_codes: list<string>, components: array<string, array{numerator: string, denominator: string}>} */
    private function scorecard(mixed $scorecard): array
    {
        if (! is_array($scorecard) || ! is_string($scorecard['scorecard_version'] ?? null) || $scorecard['scorecard_version'] === ''
            || ! is_string($scorecard['rating'] ?? null) || ! preg_match('/^(?:[0-4]\.[0-9]|5\.0)$/D', $scorecard['rating'])
            || ! is_string($scorecard['band'] ?? null) || $scorecard['band'] === ''
            || ! is_array($scorecard['reason_codes'] ?? null) || ! array_is_list($scorecard['reason_codes'])) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        foreach ($scorecard['reason_codes'] as $reason) {
            if (! is_string($reason) || $reason === '') {
                throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
            }
        }
        $components = [];
        foreach (['coverage', 'cfads_margin', 'nocf_stability', 'positive_months', 'history_depth', 'conduct'] as $component) {
            $components[$component] = $this->ratio(Arr::get($scorecard, 'components.'.$component));
        }

        return ['scorecard_version' => $scorecard['scorecard_version'], 'rating' => $scorecard['rating'], 'band' => $scorecard['band'],
            'score' => $this->ratio($scorecard['score'] ?? null), 'uncapped_score' => $this->ratio($scorecard['uncapped_score'] ?? null),
            'reason_codes' => $scorecard['reason_codes'], 'components' => $components];
    }

    /** @return array{numerator: string, denominator: string} */
    private function ratio(mixed $ratio): array
    {
        if (! is_array($ratio) || ! is_string($ratio['numerator'] ?? null) || ! is_string($ratio['denominator'] ?? null)
            || ! preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $ratio['numerator']) || ! preg_match('/^[1-9][0-9]*$/D', $ratio['denominator'])) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }

        return ['numerator' => $ratio['numerator'], 'denominator' => $ratio['denominator']];
    }
}
