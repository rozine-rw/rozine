<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\AcceptedApplicationStore;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCampaign;
use Closure;
use Illuminate\Support\Str;
use RuntimeException;

final class EloquentBusinessCampaignStore implements BusinessCampaignStore
{
    public const FEE_VERSION = 'listing-fee-waiver-1';

    public function __construct(private AcceptedApplicationStore $accepted, private BusinessAuthorityStore $businesses,
        private WithBusinessAuthority $authority, private AuthorizeStaffPermission $staff, private IdentityRepository $identities,
        private OperationJournal $journal, private CanonicalJson $json) {}

    /** @return array<string, mixed> */
    public function release(int $userId, string $applicationId, int $expectedRevision, string $reason, string $requestId): array
    {
        return $this->staffScope($userId, $applicationId, function (BusinessApplication $application) use ($userId, $expectedRevision, $reason, $requestId): array {
            $input = ['expected_revision' => $expectedRevision, 'reason' => $reason];
            try {
                $this->json->encode($input);
            } catch (CommandRejection) {
                $input = ['invalid_input_sha256' => hash('sha256', serialize($input))];
            }

            return $this->journal->execute('staff:'.$userId, $userId, 'application.release', $requestId, 'application', $application->id,
                $input, function (): void {},
                function (string $operationId) use ($application, $expectedRevision, $reason, $requestId, $userId): OperationResult {
                    $existing = $this->releaseRecord($application);
                    if ($expectedRevision !== ($existing === null ? 0 : 1)) {
                        throw new CommandRejection('VERSION_CONFLICT', revision: $existing === null ? 0 : 1);
                    }
                    if ($existing !== null) {
                        throw new CommandRejection('APPLICATION_ALREADY_RELEASED', revision: 1);
                    }
                    if (trim($reason) === '' || mb_strlen($reason) > 1000 || preg_match('/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u', $reason)) {
                        throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['reason' => ['Provide a factual release reason of at most 1,000 characters.']]);
                    }

                    return $this->accepted->withReleaseInput($application->business_id, $application->id,
                        function (array $input) use ($application, $reason, $requestId, $userId, $operationId): OperationResult {
                            $release = new BusinessApplicationRelease;
                            $release->id = (string) Str::ulid();
                            $payload = ['release_id' => $release->id, 'business_id' => $application->business_id, 'application_id' => $application->id,
                                'exposure_reservation_id' => $input['reservation_id'], 'binding' => $this->binding($input),
                                'actor_user_id' => $userId, 'reason' => trim($reason), 'request_id' => $requestId, 'operation_id' => $operationId,
                                'recorded_at' => now()->toIso8601String()];
                            $release->forceFill(['business_id' => $application->business_id, 'business_application_id' => $application->id,
                                'exposure_reservation_id' => $input['reservation_id'], 'actor_user_id' => $userId,
                                'payload' => $payload, 'sha256' => $this->hash($payload)])->save();

                            return new OperationResult('APPLICATION_RELEASED', ['application_id' => $application->id, 'business_id' => $application->business_id,
                                'receipt' => $this->receipt($release->id, $payload, 'APPLICATION_RELEASED')], 1);
                        });
                });
        });
    }

    /** @return array<string, mixed> */
    public function publish(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, string $feeVersion, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $applicationId, $expectedRevision, $feeVersion, $requestId): array {
                $partyId = $identity['party']['id'];
                if (! in_array($partyId, $business['mandate']['required_signatories'], true)) {
                    throw new CommandRejection('ACTION_FORBIDDEN', 403);
                }
                $application = $this->application($business['id'], $applicationId);

                return $this->journal->execute('party:'.$partyId, $userId, 'application.publish', $requestId, 'application', $application->id,
                    ['identity_context_revision' => $contextRevision, 'expected_application_revision' => $expectedRevision, 'fee_disclosure_version' => $feeVersion],
                    function (): void {}, function (string $operationId) use ($application, $expectedRevision, $feeVersion, $requestId, $userId, $partyId): OperationResult {
                        if ($application->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $application->revision);
                        }
                        if ($feeVersion !== self::FEE_VERSION) {
                            throw new CommandRejection('FEE_DISCLOSURE_CHANGED', revision: $application->revision);
                        }
                        $release = $this->releaseRecord($application);
                        if ($release === null) {
                            throw new CommandRejection('STAFF_RELEASE_REQUIRED', revision: $application->revision);
                        }
                        if (BusinessCampaign::query()->where('business_application_id', $application->id)->exists()) {
                            throw new CommandRejection('LISTING_ALREADY_PUBLISHED', revision: $application->revision);
                        }

                        return $this->accepted->withReleaseInput($application->business_id, $application->id,
                            function (array $input) use ($application, $release, $requestId, $userId, $partyId, $operationId): OperationResult {
                                if ($this->json->encode($release->payload['binding']) !== $this->json->encode($this->binding($input))) {
                                    throw new CommandRejection('STAFF_RELEASE_REQUIRED', revision: $application->revision);
                                }
                                $campaign = new BusinessCampaign;
                                $campaign->id = (string) Str::ulid();
                                $live = now('UTC')->toImmutable()->startOfSecond();
                                $payload = ['campaign_id' => $campaign->id, 'business_id' => $application->business_id, 'application_id' => $application->id,
                                    'release_id' => $release->id, 'exposure_reservation_id' => $input['reservation_id'], 'principal' => $input['principal'],
                                    'binding' => $this->binding($input), 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
                                    'operation_id' => $operationId, 'request_id' => $requestId, 'recorded_at' => $live->toIso8601String(), 'expires_at' => $live->addDays(30)->toIso8601String(),
                                    'listing_fee' => ['currency' => 'RWF', 'amount' => '0'], 'fee_disclosure_version' => self::FEE_VERSION,
                                    'title' => $application->draft['title'], 'quote' => $input['quote'], 'public_evidence' => $input['public_evidence']];
                                $campaign->forceFill(['business_id' => $application->business_id, 'business_application_id' => $application->id,
                                    'business_application_release_id' => $release->id, 'exposure_reservation_id' => $input['reservation_id'], 'actor_user_id' => $userId,
                                    'principal' => $input['principal'], 'live_at' => $live, 'expires_at' => $live->addDays(30),
                                    'payload' => $payload, 'sha256' => $this->hash($payload)])->save();

                                return new OperationResult('LISTING_PUBLISHED', ['application_id' => $application->id, 'business_id' => $application->business_id,
                                    'campaign_id' => $campaign->id, 'receipt' => $this->receipt($campaign->id, $payload, 'LISTING_PUBLISHED')], $application->revision);
                            });
                    });
            });
    }

    /** @return array<string, mixed> */
    public function staffPage(int $userId, string $applicationId): array
    {
        return $this->staffScope($userId, $applicationId, fn (BusinessApplication $application): array => $this->page($application));
    }

    /** @return array<string, mixed> */
    public function publishPage(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($businessId, $applicationId, $contextRevision): array {
                $person = array_find($business['mandate']['people'], fn (array $person): bool => $person['party_id'] === $identity['party']['id']);
                $canPublish = in_array('application.sign', $person['permissions'] ?? [], true)
                    && in_array($identity['party']['id'], $business['mandate']['required_signatories'], true);

                return [...$this->page($this->application($businessId, $applicationId)), 'identity_context_revision' => $contextRevision, 'can_publish' => $canPublish];
            });
    }

    /** @return array<string, mixed> */
    public function campaign(int $userId, int $contextRevision, string $businessId, string $campaignId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, function () use ($businessId, $campaignId, $contextRevision): array {
            $campaign = BusinessCampaign::query()->where('business_id', $businessId)->whereKey($campaignId)->first()
                ?? throw new CommandRejection('CAMPAIGN_NOT_FOUND', 404);
            $payload = $this->campaignPayload($campaign);

            return ['identity_context_revision' => $contextRevision, 'business_id' => $businessId, 'id' => $campaign->id, 'revision' => 1,
                'title' => $payload['title'], 'principal' => $payload['principal'], 'quote' => $payload['quote'],
                'progress' => ['phase' => 'raising', 'lifecycle' => 'live', 'restriction' => null,
                    'committed' => ['currency' => 'RWF', 'amount' => '0'], 'reserved' => ['currency' => 'RWF', 'amount' => '0'],
                    'remaining' => ['currency' => 'RWF', 'amount' => $payload['principal']],
                    'units' => ['total' => $payload['quote']['units'], 'available' => $payload['quote']['units'], 'reserved' => '0', 'committed' => '0'],
                    'investors' => 0, 'funded_pct' => '0.0', 'clock' => ['starts_at' => $payload['recorded_at'], 'expires_at' => $payload['expires_at']]],
                'receipt' => $this->receipt($campaign->id, $payload, 'LISTING_PUBLISHED')];
        });
    }

    /** @return array<string, mixed> */
    public function findRelease(int $userId, string $requestId): array
    {
        $this->staff->check($userId, 'applications.review');

        return $this->journal->find('staff:'.$userId, 'application.release', $requestId,
            function (string $type, string $id) use ($userId): void {
                if ($type !== 'application') {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->staffScope($userId, $id, fn (): bool => true);
            });
    }

    /** @return array<string, mixed> */
    public function findPublication(int $userId, int $contextRevision, string $requestId): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404);

        return $this->journal->find('party:'.$partyId, 'application.publish', $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                $application = $type === 'application' ? BusinessApplication::query()->find($id) : null;
                if ($application === null) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $application->business_id, 'business.view', null,
                    function (array $business, array $identity) use ($partyId): void {
                        if ($identity['party']['id'] !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                    });
            });
    }

    /** @template TResult
     * @param  Closure(BusinessApplication): TResult  $operation
     * @return TResult
     */
    private function staffScope(int $userId, string $applicationId, Closure $operation): mixed
    {
        $this->staff->check($userId, 'applications.review');
        $application = BusinessApplication::query()->find($applicationId) ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);

        return $this->businesses->withAudit(null, null, $application->business_id, [], false,
            fn (): mixed => $this->staff->handle($userId, 'applications.review', fn (): mixed => $operation($this->application($application->business_id, $applicationId))));
    }

    private function application(string $businessId, string $applicationId): BusinessApplication
    {
        return BusinessApplication::query()->where('business_id', $businessId)->whereKey($applicationId)->lockForUpdate()->first()
            ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
    }

    /** @return array<string, mixed> */
    private function page(BusinessApplication $application): array
    {
        $release = $this->releaseRecord($application);
        $campaign = BusinessCampaign::query()->where('business_application_id', $application->id)->first();
        $cause = null;
        try {
            $this->accepted->withReleaseInput($application->business_id, $application->id, function (array $input) use ($release): void {
                if ($release !== null && $this->json->encode($release->payload['binding']) !== $this->json->encode($this->binding($input))) {
                    throw new CommandRejection('STAFF_RELEASE_REQUIRED');
                }
            });
        } catch (CommandRejection $failure) {
            $cause = $failure->reason;
        } catch (IdentityViolation) {
            $cause = 'AUTHORITY_CHANGED';
        }
        $submission = BusinessApplicationSubmission::query()->where('business_application_id', $application->id)->whereKey($application->current_submission_id)->first();
        if ($submission !== null && $this->hash($submission->payload) !== $submission->sha256) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }

        return ['application' => ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
            'title' => $application->draft['title'], 'draft' => $application->draft], 'review' => $submission?->payload['review'],
            'public_evidence' => $submission?->payload['public_evidence'], 'submitted_at' => $submission?->payload['submitted_at'],
            'fee_disclosure' => ['version' => self::FEE_VERSION, 'text' => 'The listing fee is waived for the MVP. You pay RWF 0 to publish.'],
            'cause' => $cause, 'release' => $release === null ? null : $this->receipt($release->id, $release->payload, 'APPLICATION_RELEASED'),
            'listing' => $campaign === null ? null : ['id' => $campaign->id, 'receipt' => $this->receipt($campaign->id, $this->campaignPayload($campaign), 'LISTING_PUBLISHED')]];
    }

    private function releaseRecord(BusinessApplication $application): ?BusinessApplicationRelease
    {
        $release = BusinessApplicationRelease::query()->where('business_application_id', $application->id)->first();
        if ($release !== null && ($this->hash($release->payload) !== $release->sha256 || $release->payload['release_id'] !== $release->id
            || $release->payload['business_id'] !== $application->business_id || $release->business_id !== $application->business_id
            || $release->payload['application_id'] !== $application->id || $release->payload['exposure_reservation_id'] !== $release->exposure_reservation_id
            || $release->payload['actor_user_id'] !== $release->actor_user_id)) {
            throw new RuntimeException('APPLICATION_RELEASE_INTEGRITY_FAILED');
        }

        return $release;
    }

    /** @return array<string, mixed> */
    private function campaignPayload(BusinessCampaign $campaign): array
    {
        $payload = $campaign->payload;
        if ($this->hash($payload) !== $campaign->sha256 || $payload['campaign_id'] !== $campaign->id
            || $payload['business_id'] !== $campaign->business_id || $payload['application_id'] !== $campaign->business_application_id
            || $payload['release_id'] !== $campaign->business_application_release_id || $payload['exposure_reservation_id'] !== $campaign->exposure_reservation_id
            || $payload['principal'] !== $campaign->principal || $payload['actor_user_id'] !== $campaign->actor_user_id
            || $payload['recorded_at'] !== $campaign->live_at->toIso8601String() || $payload['expires_at'] !== $campaign->expires_at->toIso8601String()) {
            throw new RuntimeException('CAMPAIGN_INTEGRITY_FAILED');
        }

        return $payload;
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function binding(array $input): array
    {
        return ['application' => $input['binding'], 'agreement_sha256' => $input['agreement_sha256'], 'report' => $input['report'],
            'reservation_id' => $input['reservation_id'], 'principal' => $input['principal']];
    }

    /** @param array<string, mixed> $payload */
    private function hash(array $payload): string
    {
        return hash('sha256', $this->json->encode($payload));
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function receipt(string $id, array $payload, string $code): array
    {
        return ['receipt_id' => $id, 'operation_id' => $payload['operation_id'], 'request_id' => $payload['request_id'], 'code' => $code, 'recorded_at' => $payload['recorded_at'],
            'amount' => ['currency' => 'RWF', 'amount' => '0'], 'units' => null, 'reference' => $id, 'revision' => 1,
            'policy_version' => 'engineering-2026-09-23.4', 'disclosure_version' => $payload['fee_disclosure_version'] ?? null];
    }
}
