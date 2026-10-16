<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * One Investor commitment as `investor.commitments.show` reads it (C3 v2 §2c). It projects only
 * what is authenticated today: the retained purchase facts, the recorded confirmation and any
 * Investor refund. A commitment whose campaign has since been funded or closed is refused
 * `COMMITMENT_STATE_UNAVAILABLE` until its funding, issue and return sources are wired.
 */
interface PrimaryCommitmentView
{
    /**
     * Refuses `NOT_FOUND` (404) for an unknown commitment and `COMMITMENT_NOT_FOUND` (404) for one
     * of another Party, after current Investor authority.
     *
     * @return array{identity_context_revision: int, commitment: array<string, mixed>}
     */
    public function show(int $userId, ?int $contextRevision, string $commitmentId): array;
}
