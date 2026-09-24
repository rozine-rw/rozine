<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\ApplicationDraft;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationVersion;

/**
 * @phpstan-import-type Fields from ApplicationDraft
 * @phpstan-import-type Application from BusinessApplicationStore
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 */
final class EloquentBusinessApplicationStore implements BusinessApplicationStore
{
    public function __construct(
        private WithBusinessAuthority $authority,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private ApplicationDraft $drafts,
    ) {}

    /** @return array<string, mixed> */
    public function create(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.create', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $expectedRevision, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

                return $this->journal->execute('party:'.$partyId, $userId, 'application.create', $requestId, 'business', $businessId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.create', null, fn (): bool => true);
                    },
                    function () use ($userId, $partyId, $business, $expectedRevision): OperationResult {
                        if ($expectedRevision !== 0) {
                            throw new CommandRejection('VERSION_CONFLICT', 409, 0);
                        }
                        $existing = BusinessApplication::query()->where('business_id', $business['id'])->where('status', 'draft')->lockForUpdate()->first();
                        if ($existing !== null) {
                            return new OperationResult('APPLICATION_RESUMED', ['application' => $this->snapshot($existing)], $existing->revision);
                        }
                        $application = new BusinessApplication;
                        $application->forceFill(['business_id' => $business['id'], 'revision' => 1, 'status' => 'draft', 'step' => 'business',
                            'draft' => $this->drafts->empty(), 'mandate_version' => $business['mandate_version']])->save();
                        $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                        return new OperationResult('APPLICATION_CREATED', ['application' => $this->snapshot($application)], 1);
                    });
            });
    }

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $step, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $step, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                $application = $this->application($businessId, $applicationId);

                return $this->journal->execute('party:'.$partyId, $userId, 'application.save', $requestId, 'application', $applicationId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'draft' => $fields, 'step' => $step],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function () use ($application, $business, $userId, $partyId, $expectedRevision, $fields, $step): OperationResult {
                        $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                        $fields = $this->drafts->normalize($fields);
                        if (! in_array($step, ['business', 'raise'], true)) {
                            throw new CommandRejection('APPLICATION_STEP_INVALID', 422, $application->revision,
                                ['step' => ['A draft may resume at the Business or Raise step.']]);
                        }
                        $application->forceFill(['draft' => $fields, 'step' => $step, 'revision' => $expectedRevision + 1,
                            'mandate_version' => $business['mandate_version']])->save();
                        $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                        return new OperationResult('APPLICATION_SAVED', ['application' => $this->snapshot($application)], $application->revision);
                    });
            });
    }

    /** @return Application */
    public function get(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (): array => $this->snapshot($this->application($businessId, $applicationId)));
    }

    /** @return Application|null */
    public function current(int $userId, int $contextRevision, string $businessId): ?array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function () use ($businessId): ?array {
                $application = BusinessApplication::query()->where('business_id', $businessId)->where('status', 'draft')->first();

                return $application === null ? null : $this->snapshot($application);
            });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        $permission = match ($command) {
            'create' => 'application.create',
            'save' => 'application.save',
            default => throw new CommandRejection('OPERATION_NOT_FOUND', 404),
        };
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->journal->find('party:'.$partyId, 'application.'.$command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $permission, $partyId, $command): void {
                if ($type !== ($command === 'create' ? 'business' : 'application')) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $businessId = $type === 'business' ? $id : BusinessApplication::query()->whereKey($id)->value('business_id');
                if (! is_string($businessId)) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $businessId, $permission, null,
                    function (array $business, array $identity) use ($partyId): void {
                        if (($identity['party']['id'] ?? null) !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                    });
            });
    }

    private function application(string $businessId, string $applicationId): BusinessApplication
    {
        return BusinessApplication::query()->where('business_id', $businessId)->whereKey($applicationId)->lockForUpdate()->first()
            ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
    }

    private function recordVersion(BusinessApplication $application, int $userId, string $partyId, int $mandateVersion): void
    {
        (new BusinessApplicationVersion)->forceFill(['business_application_id' => $application->id, 'revision' => $application->revision,
            'snapshot' => $this->snapshot($application), 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
            'mandate_version' => $mandateVersion, 'policy_version' => 'engineering-2026-09-23.4'])->save();
    }

    /** @return Application */
    private function snapshot(BusinessApplication $application): array
    {
        return ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
            'status' => $application->status, 'step' => $application->step, 'draft' => $application->draft, 'mandate_version' => $application->mandate_version];
    }
}
