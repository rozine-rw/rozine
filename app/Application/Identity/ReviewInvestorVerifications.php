<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationReviewStore;

/**
 * Compliance's Investor identity review (`investors.verify`): the queue, one case with its private
 * documents, and the approve and reject commands.
 *
 * @phpstan-import-type Queue from InvestorVerificationReviewStore
 * @phpstan-import-type Review from InvestorVerificationReviewStore
 */
final class ReviewInvestorVerifications
{
    public function __construct(private InvestorVerificationReviewStore $store) {}

    /** @return Queue */
    public function queue(int $actorId, string $tab, string $search, ?string $before, int $limit): array
    {
        return $this->store->queue($actorId, $tab, $search, $before, $limit);
    }

    /** @return Review */
    public function show(int $actorId, string $verificationId): array
    {
        return $this->store->show($actorId, $verificationId);
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $verificationId, string $documentId): array
    {
        return $this->store->document($actorId, $verificationId, $documentId);
    }

    /** @return array<string, mixed> */
    public function approve(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array
    {
        return $this->store->approve($actorId, $verificationId, $expectedRevision, $reason, $requestId);
    }

    /** @return array<string, mixed> */
    public function reject(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array
    {
        return $this->store->reject($actorId, $verificationId, $expectedRevision, $reason, $requestId);
    }
}
