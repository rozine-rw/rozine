<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\WithBusinessReview;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Underwriting\BorrowerCreditFacts;
use App\Models\BusinessCreditSnapshot;
use App\Models\BusinessProfile;
use Closure;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type Snapshot from BusinessCreditFactsStore */
final class EloquentBusinessCreditFactsStore implements BusinessCreditFactsStore
{
    public function __construct(
        private WithBusinessReview $businesses,
        private AuthorizeStaffPermission $staff,
        private EnvironmentIsolation $isolation,
        private BorrowerCreditFacts $facts,
        private OperationJournal $journal,
        private CanonicalJson $json,
    ) {}

    /**
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function recordFixture(int $actorId, string $businessId, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array
    {
        $this->staff->check($actorId, 'businesses.verify');
        $this->isolation->assertSeedingAllowed();

        return $this->businesses->handle($actorId, $businessId, false, function () use ($actorId, $businessId, $expectedRevision, $facts, $sourceReference, $reason, $requestId): array {
            return $this->journal->execute('staff:'.$actorId, $actorId, 'business.credit.fixture', $requestId, 'business', $businessId,
                ['expected_revision' => $expectedRevision, 'facts' => $facts, 'source_reference' => $sourceReference, 'reason' => $reason], function (): void {},
                function () use ($actorId, $businessId, $expectedRevision, $facts, $sourceReference, $reason): OperationResult {
                    if ($expectedRevision < 0 || ! str_starts_with($sourceReference, 'synthetic:') || strlen($sourceReference) > 255
                        || trim(substr($sourceReference, 10)) === '' || ! mb_check_encoding($sourceReference, 'UTF-8') || preg_match('/[\p{Cc}\p{Cf}]/u', $sourceReference)
                        || trim($reason) === '' || mb_strlen($reason) > 2000 || ! mb_check_encoding($reason, 'UTF-8') || preg_match('/[\p{Cc}\p{Cf}]/u', $reason)) {
                        throw new CommandRejection('CREDIT_SOURCE_INVALID', 422);
                    }
                    $prior = BusinessCreditSnapshot::query()->where('business_id', $businessId)->orderByDesc('revision')->first();
                    $revision = $prior->revision ?? 0;
                    if ($expectedRevision !== $revision) {
                        throw new CommandRejection('VERSION_CONFLICT', revision: $revision);
                    }
                    $record = new BusinessCreditSnapshot;
                    $record->forceFill(['business_id' => $businessId, 'revision' => $revision + 1, 'status' => $facts === null ? 'withdrawn' : 'available',
                        'source_kind' => 'isolated_alpha', 'source_reference' => $sourceReference, 'facts' => $facts === null ? null : $this->facts->normalize($facts),
                        'actor_user_id' => $actorId, 'reason' => $reason]);
                    $record->forceFill(['sha256' => $this->digest($record)])->save();

                    return new OperationResult('CREDIT_FACTS_RECORDED', ['credit_facts' => ['id' => $record->id, 'revision' => $record->revision,
                        'status' => $record->status, 'source_kind' => 'isolated_alpha', 'sha256' => $record->sha256]], $record->revision);
                });
        });
    }

    /**
     * @template TResult
     *
     * @param  Closure(Snapshot|null): TResult  $operation
     * @return TResult
     */
    public function withCurrent(string $businessId, Closure $operation): mixed
    {
        return DB::transaction(function () use ($businessId, $operation): mixed {
            if (BusinessProfile::query()->lockForUpdate()->find($businessId) === null) {
                throw new CommandRejection('BUSINESS_NOT_FOUND', 404);
            }
            $record = BusinessCreditSnapshot::query()->where('business_id', $businessId)->orderByDesc('revision')->first();
            if ($record === null || ! $this->isolation->canSeed() || $record->status !== 'available') {
                return $operation(null);
            }
            if ($record->source_kind !== 'isolated_alpha' || $record->facts === null || ! hash_equals($record->sha256, $this->digest($record))) {
                throw new CommandRejection('CREDIT_FACTS_CORRUPTED');
            }

            return $operation(['id' => $record->id, 'business_id' => $record->business_id, 'revision' => $record->revision,
                'sha256' => $record->sha256, 'source_kind' => 'isolated_alpha', 'source_reference' => $record->source_reference, 'facts' => $record->facts]);
        }, 3);
    }

    private function digest(BusinessCreditSnapshot $record): string
    {
        return hash('sha256', $this->json->encode(['business_id' => $record->business_id, 'revision' => $record->revision, 'status' => $record->status,
            'source_kind' => $record->source_kind, 'source_reference' => $record->source_reference, 'facts' => $record->facts]));
    }
}
