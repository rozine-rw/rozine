<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\AcceptedApplicationStore;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\PublishedCampaignEvidence;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessProfile;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class EloquentBusinessCampaignStore implements BusinessCampaignStore
{
    public const FEE_VERSION = 'listing-fee-waiver-1';

    public function __construct(private AcceptedApplicationStore $accepted, private BusinessAuthorityStore $businesses,
        private WithBusinessAuthority $authority, private AuthorizeStaffPermission $staff, private IdentityRepository $identities,
        private OperationJournal $journal, private CanonicalJson $json, private CampaignClosureEvidence $closures, private BusinessExposureStore $exposures, private PublishedCampaignEvidence $publications, private PrimaryReservations $primary, private CampaignReservationSummary $reservations, private CampaignFundingEvidence $fundings,
        private ChangeFeed $changes) {}

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
                            $this->changes->record(ChangeScope::staffQueue('applications'), 'staff_queue', 'applications');

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
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, function (array $business, array $identity) use ($businessId, $campaignId, $contextRevision): array {
            $campaign = BusinessCampaign::query()->where('business_id', $businessId)->whereKey($campaignId)->first()
                ?? throw new CommandRejection('CAMPAIGN_NOT_FOUND', 404);
            $payload = $this->publications->find($campaign->id);
            $closure = $this->closures->find($campaign->id);
            $projection = $closure === null ? $this->campaignProjection($campaign->id, $payload) : null;
            $progress = $projection !== null ? $projection['progress']
                : ['phase' => $closure['phase'], 'committed_refunded' => $closure['committed_refunded'],
                    'investors' => $closure['investors'], 'closed_at' => $closure['closed_at']];
            $person = array_find($business['mandate']['people'], fn (array $person): bool => $person['party_id'] === $identity['party']['id']);
            $canCancel = $closure === null && $progress['phase'] === 'raising' && now()->lt($campaign->expires_at)
                && $progress['committed']['amount'] === '0' && ! $projection['has_unreturned_holds']
                && in_array('application.sign', $person['permissions'] ?? [], true)
                && in_array($identity['party']['id'], $business['mandate']['required_signatories'], true);

            return ['identity_context_revision' => $contextRevision, 'business_id' => $businessId, 'id' => $campaign->id, 'revision' => $closure === null ? 1 : 2,
                'lifecycle' => $closure['phase'] ?? $progress['lifecycle'], 'can_cancel' => $canCancel,
                'title' => $payload['title'], 'principal' => $payload['principal'], 'quote' => $payload['quote'],
                'progress' => $progress,
                'receipt' => $this->receipt($campaign->id, $payload, 'LISTING_PUBLISHED')];
        });
    }

    /**
     * Retained raise or funding-lock progress; this never dispatches or certifies payment.
     * Returned and overdue claims remain unavailable until ordinal recycling exists.
     *
     * @param  array<string, mixed>  $payload
     * @return array{progress: array<string, mixed>, has_unreturned_holds: bool}
     */
    private function campaignProjection(string $campaignId, array $payload): array
    {
        $at = now('UTC');
        $summary = $this->reservations->read($campaignId, $at->toDateTimeImmutable());
        $remaining = BigInteger::of($payload['principal'])->minus($summary['committed_principal'])->minus($summary['held_principal']);
        $available = BigInteger::of($payload['quote']['units'])->minus($summary['occupied_units']);
        $unavailable = BigInteger::of($summary['occupied_units'])->minus($summary['committed_units'])->minus($summary['held_units']);
        if ($remaining->isNegative() || $available->isNegative() || $unavailable->isNegative()) {
            throw new RuntimeException('RESERVATION_SUMMARY_INTEGRITY_FAILED');
        }

        $hasUnreturnedHolds = $summary['held_principal'] !== '0' || $summary['expired_hold_principal'] !== '0';
        $funding = $this->fundings->find($campaignId);
        if ($funding !== null) {
            return ['has_unreturned_holds' => $hasUnreturnedHolds, 'progress' => ['phase' => 'funded', 'lifecycle' => 'funded_pending_disbursement', 'restriction' => null,
                'committed' => ['currency' => 'RWF', 'amount' => $funding['principal']],
                'investors' => count(array_unique(array_column($funding['commitments'], 'party_id'))),
                'funded_at' => $funding['recorded_at'], 'closing' => ['stage' => 'awaiting_disbursement']]];
        }
        $lifecycle = match (true) {
            $at->gte($payload['expires_at']) => 'closing_pending_settlement',
            BigInteger::of($summary['committed_principal'])->isEqualTo($payload['principal']) => 'sold_out_pending_settlement',
            $available->isZero() && BigInteger::of($summary['held_units'])->isPositive() => 'fully_reserved',
            $available->isZero() => 'inventory_unavailable',
            default => 'live',
        };

        return ['has_unreturned_holds' => $hasUnreturnedHolds, 'progress' => ['phase' => 'raising', 'lifecycle' => $lifecycle, 'restriction' => null,
            'committed' => ['currency' => 'RWF', 'amount' => $summary['committed_principal']],
            'reserved' => ['currency' => 'RWF', 'amount' => $summary['held_principal']],
            'remaining' => ['currency' => 'RWF', 'amount' => (string) $remaining],
            'units' => ['total' => $payload['quote']['units'], 'available' => (string) $available,
                'reserved' => $summary['held_units'], 'committed' => $summary['committed_units'], 'unavailable' => (string) $unavailable],
            'investors' => $summary['investors'],
            'funded_pct' => (string) BigDecimal::of($summary['committed_principal'])->multipliedBy(100)
                ->dividedBy($payload['principal'], 1, RoundingMode::Down),
            'clock' => ['starts_at' => $payload['recorded_at'], 'expires_at' => $payload['expires_at']]]];
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

    /** @return array<string, mixed> */
    public function cancel(int $userId, int $contextRevision, string $businessId, string $campaignId, int $expectedRevision, ?string $reason, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $campaignId, $expectedRevision, $reason, $requestId): array {
                $partyId = $identity['party']['id'];
                if (! in_array($partyId, $business['mandate']['required_signatories'], true)) {
                    throw new CommandRejection('ACTION_FORBIDDEN', 403);
                }
                $campaign = BusinessCampaign::query()->where('business_id', $business['id'])->whereKey($campaignId)->lockForUpdate()->first()
                    ?? throw new CommandRejection('CAMPAIGN_NOT_FOUND', 404);

                $input = ['identity_context_revision' => $contextRevision, 'expected_campaign_revision' => $expectedRevision, 'reason' => $reason];
                try {
                    $this->json->encode($input);
                } catch (CommandRejection) {
                    $input = ['invalid_input_sha256' => hash('sha256', serialize($input))];
                }

                return $this->journal->execute('party:'.$partyId, $userId, 'campaign.cancel', $requestId, 'campaign', $campaign->id, $input,
                    function (): void {}, function (string $operationId) use ($campaign, $expectedRevision, $reason, $requestId, $userId, $partyId): OperationResult {
                        $this->publications->find($campaign->id);
                        $closure = $this->closures->find($campaign->id);
                        $revision = $closure === null ? 1 : 2;
                        $data = ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id];
                        if ($expectedRevision !== $revision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $revision, data: $data);
                        }
                        if ($closure !== null) {
                            throw new CommandRejection('CAMPAIGN_CLOSED', revision: $revision, data: $data);
                        }
                        if ($reason !== null && (! mb_check_encoding($reason, 'UTF-8') || mb_strlen($reason) > 1000 || preg_match('/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u', $reason))) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, $revision, fieldErrors: ['reason' => ['Use at most 1,000 characters without control characters.']], data: $data);
                        }
                        $closure = $this->close($campaign, 'cancelled', $userId, $partyId, $operationId, $requestId, $reason);
                        $receipt = [...$this->receipt($closure->id, $closure->payload, 'CAMPAIGN_CANCELLED'), 'revision' => 2,
                            'exposure_released' => ['currency' => 'RWF', 'amount' => $campaign->principal],
                            'committed_refunded' => $closure->payload['committed_refunded'], 'investors' => $closure->payload['investors']];

                        return new OperationResult('CAMPAIGN_CANCELLED', [...$data, 'receipt' => $receipt], 2);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findCancellation(int $userId, int $contextRevision, string $requestId): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404);

        return $this->journal->find('party:'.$partyId, 'campaign.cancel', $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                $campaign = $type === 'campaign' ? BusinessCampaign::query()->find($id) : null;
                if ($campaign === null) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $campaign->business_id, 'business.view', null,
                    function (array $business, array $identity) use ($partyId): void {
                        if ($identity['party']['id'] !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                    });
            });
    }

    public function expireDue(int $limit): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new CommandRejection('INVALID_SWEEP_LIMIT', 422);
        }
        $cutoff = now();
        $cursor = null;
        $expired = 0;
        $failure = null;
        while ($expired < $limit) {
            $query = BusinessCampaign::query()->where('expires_at', '<=', $cutoff)
                ->whereNotIn('id', BusinessCampaignClosure::query()->select('business_campaign_id'));
            if ($cursor !== null) {
                $query->where(fn (Builder $query): Builder => $query->where('expires_at', '>', $cursor->expires_at)
                    ->orWhere(fn (Builder $query): Builder => $query->where('expires_at', $cursor->expires_at)->where('id', '>', $cursor->id)));
            }
            $due = $query->orderBy('expires_at')->orderBy('id')->limit($limit - $expired)->get(['id', 'business_id', 'expires_at']);
            if ($due->isEmpty()) {
                break;
            }
            foreach ($due as $candidate) {
                $cursor = $candidate;
                try {
                    $expired += DB::transaction(function () use ($candidate): int {
                        BusinessProfile::query()->whereKey($candidate->business_id)->lockForUpdate()->firstOrFail();
                        $campaign = BusinessCampaign::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                        $this->publications->find($campaign->id);
                        if ($this->closures->find($campaign->id) !== null || $this->fundings->find($campaign->id) !== null || now()->lt($campaign->expires_at)) {
                            return 0;
                        }
                        $this->close($campaign, 'expired', null, null, null, null, null);

                        return 1;
                    }, 3);
                } catch (Throwable $exception) {
                    if ($exception instanceof CommandRejection && $exception->reason === 'CAMPAIGN_SETTLEMENT_REQUIRED') {
                        Log::notice('Campaign expiry deferred until commitments are settled.', [
                            'campaign_id' => $candidate->id, 'business_id' => $candidate->business_id, 'code' => $exception->reason,
                        ]);

                        continue;
                    }
                    $reasonCode = $this->expiryFailureReason($exception);
                    DB::table('business_campaign_expiry_failures')->upsert([['business_campaign_id' => $candidate->id,
                        'last_attempted_at' => now('UTC')->format('Y-m-d H:i:s.uP'), 'exception_class' => $exception::class,
                        'reason_code' => $reasonCode]], ['business_campaign_id'], ['last_attempted_at', 'exception_class', 'reason_code']);
                    Log::error('Business campaign expiry failed.', [
                        'campaign_id' => $candidate->id, 'business_id' => $candidate->business_id,
                        'exception_class' => $exception::class, 'reason_code' => $reasonCode,
                    ]);
                    $failure ??= $exception;
                }
            }
        }
        if ($failure !== null) {
            throw $failure;
        }

        return $expired;
    }

    private function expiryFailureReason(Throwable $exception): string
    {
        $reason = $exception instanceof CommandRejection || $exception instanceof WalletViolation
            ? $exception->reason : $exception->getMessage();

        return in_array($reason, ['CAMPAIGN_INTEGRITY_FAILED', 'CAMPAIGN_CLOSURE_INTEGRITY_FAILED',
            'CAMPAIGN_EXPOSURE_INTEGRITY_FAILED', 'BUSINESS_EXPOSURE_INTEGRITY_FAILED',
            'APPLICATION_RELEASE_INTEGRITY_FAILED', 'APPLICATION_QUOTE_INTEGRITY_FAILED',
            'RESERVATION_INTEGRITY_FAILED', 'CAMPAIGN_NOT_FOUND', 'RESERVATION_NOT_FOUND',
            'PRIMARY_TRANSACTION_REQUIRED', 'PRIMARY_CASH_ISOLATION_REQUIRED', 'WALLET_POSTING_CONFLICT',
            'WALLET_POSTING_STATE_INVALID'], true) ? $reason : 'UNCLASSIFIED_EXPIRY_FAILURE';
    }

    /**
     * Releases exposure only after every original principal return is verified and bound.
     * System expiry settles partially funded purchases atomically with its durable cause.
     * Business cancellation still requires separately returned original cash.
     */
    private function close(BusinessCampaign $campaign, string $phase, ?int $userId, ?string $partyId, ?string $operationId, ?string $requestId, ?string $reason): BusinessCampaignClosure
    {
        if ($this->fundings->find($campaign->id) !== null) {
            throw new CommandRejection('CAMPAIGN_FUNDED', revision: 1, data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);
        }
        $reservation = array_find($this->exposures->current($campaign->business_id), fn (array $entry): bool => $entry['id'] === $campaign->exposure_reservation_id);
        if ($reservation === null || $reservation['principal'] !== $campaign->principal) {
            throw new RuntimeException('CAMPAIGN_EXPOSURE_INTEGRITY_FAILED');
        }
        $closure = new BusinessCampaignClosure;
        $closure->id = (string) Str::ulid();
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        if ($phase === 'cancelled' && $recordedAt->gte($campaign->expires_at)) {
            throw new CommandRejection('CAMPAIGN_CLOSED', revision: 1, data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);
        }
        try {
            if ($phase === 'expired') {
                $this->primary->settleExpiredCampaign($campaign->id, $closure->id);
            }
            $returned = $this->primary->lockReturnedCampaign($campaign->id);
        } catch (CommandRejection|WalletViolation $exception) {
            if (! in_array($exception->reason, ['CAMPAIGN_SETTLEMENT_REQUIRED', 'PRIMARY_RETURNED_CASH_REQUIRED'], true)) {
                throw $exception;
            }
            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,
                data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);
        }
        $bindings = array_map(fn (array $purchase): array => [
            'primary_reservation_id' => $purchase['reservation_id'], 'primary_reservation_version_id' => $purchase['version_id'],
            'version_sha256' => $purchase['version_sha256'], 'primary_commitment_id' => $purchase['commitment_id'],
            'party_id' => $purchase['party_id'], 'wallet_id' => $purchase['cash']->walletId, 'principal' => $purchase['cash']->amount,
            'origin_operation_id' => $purchase['cash']->originOperationId, 'hold_entry_id' => $purchase['cash']->holdEntryId,
            'commit_entry_id' => $purchase['cash']->commitEntryId, 'return_entry_id' => $purchase['cash']->returnEntryId,
            'return_kind' => $purchase['cash']->returnKind,
        ], $returned->returns);
        $closedAt = $phase === 'expired' ? $campaign->expires_at : $recordedAt;
        $payload = ['closure_id' => $closure->id, 'campaign_id' => $campaign->id, 'campaign_sha256' => $campaign->sha256,
            'business_id' => $campaign->business_id, 'exposure_reservation_id' => $campaign->exposure_reservation_id,
            'principal_released' => $campaign->principal, 'phase' => $phase, 'scope' => 'unfunded-returned-v1',
            'actor_user_id' => $userId, 'actor_party_id' => $partyId, 'operation_id' => $operationId, 'request_id' => $requestId,
            'reason' => $reason === null ? null : trim($reason), 'recorded_at' => $recordedAt->toIso8601String(),
            'closed_at' => $closedAt->toIso8601String(), 'committed_refunded' => ['currency' => 'RWF', 'amount' => $returned->refundedPrincipal],
            'released_held' => ['currency' => 'RWF', 'amount' => $returned->releasedPrincipal], 'investors' => $returned->refundedInvestors,
            'cash_returns' => $bindings, 'revision' => 2];
        $closure->forceFill(['business_campaign_id' => $campaign->id, 'business_id' => $campaign->business_id,
            'exposure_reservation_id' => $campaign->exposure_reservation_id, 'principal' => $campaign->principal,
            'phase' => $phase, 'actor_user_id' => $userId, 'closed_at' => $closedAt, 'payload' => $payload, 'sha256' => $this->hash($payload)])->save();
        $this->changes->record(ChangeScope::business($campaign->business_id), 'campaign', $campaign->id, 2);

        if ($bindings !== []) {
            DB::table('primary_campaign_closure_returns')->insert(array_map(fn (array $binding): array => [
                ...$binding, 'business_campaign_closure_id' => $closure->id,
            ], $bindings));
        }

        return $closure;
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
        $submission = BusinessApplicationSubmission::query()->where('business_application_id', $application->id)->whereKey($application->current_submission_id)->first();
        if ($submission !== null && $this->hash($submission->payload) !== $submission->sha256) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }
        $release = $this->releaseRecord($application);
        $campaign = BusinessCampaign::query()->where('business_application_id', $application->id)->first();
        $cause = null;
        $gates = array_fill_keys(['engine', 'authority', 'report'], 'RELEASE_CHECK_NOT_COMPLETED');
        $prerequisites = ['signatures_retained' => false, 'quote_current' => false, 'terms_current' => false];
        try {
            $this->accepted->withReleaseInput($application->business_id, $application->id, function (array $input) use ($release): void {
                if ($release !== null && $this->json->encode($release->payload['binding']) !== $this->json->encode($this->binding($input))) {
                    throw new CommandRejection('STAFF_RELEASE_REQUIRED');
                }
            }, function (string $gate) use (&$gates): void {
                $gates[$gate] = null;
            });
        } catch (CommandRejection $failure) {
            $cause = $failure->reason;
            $gate = match ($cause) {
                'REPORT_NOT_CURRENT' => 'report',
                'AUTHORITY_CHANGED', 'SIGNATURES_REQUIRED', 'TERMS_CHANGED', 'STAFF_RELEASE_REQUIRED' => 'authority',
                default => 'engine',
            };
            $gates[$gate] = $cause;
        } catch (IdentityViolation) {
            $cause = 'AUTHORITY_CHANGED';
            $gates['authority'] = $cause;
        }
        try {
            $prerequisites = $this->accepted->publicationPrerequisites($application->business_id, $application->id);
        } catch (IdentityViolation) {
            $gates['authority'] = 'AUTHORITY_CHANGED';
        }

        return ['application' => ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
            'title' => $application->draft['title'], 'draft' => $application->draft], 'review' => $submission?->payload['review'],
            'public_evidence' => $submission?->payload['public_evidence'], 'submitted_at' => $submission?->payload['submitted_at'],
            'fee_disclosure' => ['version' => self::FEE_VERSION, 'text' => 'The listing fee is waived for the MVP. You pay RWF 0 to publish.'],
            'cause' => $cause, 'gates' => $gates, 'prerequisites' => $prerequisites, 'release' => $release === null ? null : $this->receipt($release->id, $release->payload, 'APPLICATION_RELEASED'),
            'listing' => $campaign === null ? null : ['id' => $campaign->id, 'receipt' => $this->receipt($campaign->id, $this->publications->find($campaign->id), 'LISTING_PUBLISHED')]];
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
