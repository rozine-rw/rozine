<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorIndependenceStore;
use App\Application\Business\WithBusinessReview;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditorIndependence;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorIndependenceVersion;

/** @phpstan-import-type Review from AuditorIndependenceStore */
final class EloquentAuditorIndependenceStore implements AuditorIndependenceStore
{
    public function __construct(
        private WithBusinessReview $businesses,
        private AuthorizeStaffPermission $staff,
        private OperationJournal $journal,
        private AuditorIndependence $independence,
    ) {}

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    public function record(int $actorId, string $businessId, string $partyId, int $expectedRevision, array $facts, string $checkedAt, string $reference, string $reason, string $requestId): array
    {
        return $this->businesses->handle($actorId, $businessId, true,
            function (array $business) use ($actorId, $businessId, $partyId, $expectedRevision, $facts, $checkedAt, $reference, $reason, $requestId): array {
                $review = AuditorIndependenceReview::query()->where('business_id', $businessId)->where('party_id', $partyId)->lockForUpdate()->first();

                return $this->journal->execute('staff:'.$actorId, $actorId, 'audit.independence.review', $requestId, 'business', $businessId,
                    ['party_id' => $partyId, 'expected_revision' => $expectedRevision, 'facts' => $facts,
                        'checked_at' => $checkedAt, 'reference' => $reference, 'reason' => $reason],
                    function () use ($actorId): void {
                        $this->staff->handle($actorId, 'audit.assignments.manage', fn (): bool => true);
                    }, function () use ($review, $business, $actorId, $businessId, $partyId, $expectedRevision, $facts, $checkedAt, $reference, $reason): OperationResult {
                        if (($review->revision ?? 0) !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', 409, $review->revision ?? 0);
                        }
                        $state = $this->independence->review($review?->state, $facts, $business['mandate_version'],
                            in_array($partyId, array_column($business['mandate']['people'], 'party_id'), true), $checkedAt, $reference, $reason, now()->toDateTimeImmutable());
                        $record = $review ?? new AuditorIndependenceReview;
                        $record->forceFill(['business_id' => $businessId, 'party_id' => $partyId, 'revision' => $expectedRevision + 1, 'state' => $state])->save();
                        (new AuditorIndependenceVersion)->forceFill(['review_id' => $record->id, 'revision' => $record->revision,
                            'snapshot' => $this->snapshot($record), 'actor_user_id' => $actorId, 'reason' => $reason,
                            'policy_version' => 'engineering-2026-09-23.4'])->save();

                        return new OperationResult('AUDITOR_INDEPENDENCE_RECORDED', ['review_id' => $record->id], $record->revision);
                    });
            }, [$partyId]);
    }

    /** @return Review|null */
    public function get(int $actorId, string $businessId, string $partyId): ?array
    {
        return $this->businesses->handle($actorId, $businessId, false,
            fn (): ?array => $this->staff->handle($actorId, 'audit.assignments.manage', function () use ($businessId, $partyId): ?array {
                $record = AuditorIndependenceReview::query()->where('business_id', $businessId)->where('party_id', $partyId)->first();

                return $record === null ? null : $this->snapshot($record);
            }));
    }

    /** @return Review */
    private function snapshot(AuditorIndependenceReview $review): array
    {
        return ['id' => $review->id, 'business_id' => $review->business_id, 'party_id' => $review->party_id,
            'revision' => $review->revision, 'state' => $review->state];
    }
}
