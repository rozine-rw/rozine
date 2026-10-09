<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * An unverified person's own Investor identity submission. Every entry point locks the account
 * and its Party before the submission, rechecks the identity context, and never sets Party
 * verification: that stays with the verified-person writer.
 *
 * @phpstan-import-type State from \App\Domain\Identity\InvestorVerificationCase
 *
 * @phpstan-type Submission array{
 *     revision: int,
 *     status: 'draft'|'submitted'|'approved'|'rejected',
 *     state: State,
 *     verified: bool,
 *     identity_context_revision: int
 * }
 */
interface InvestorVerificationStore
{
    /** @return Submission */
    public function get(int $userId): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, int $expectedRevision, string $step, array $input, string $requestId): array;

    /** @return array<string, mixed> */
    public function upload(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array;

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, string $requestId): array;

    /**
     * The identity document on the person's approved verification, for their own Profile. Null until
     * staff approve a submission, and for a person verified without one.
     *
     * @return array{id_type: 'national_id'|'passport'|'drivers_license', id_number: string}|null
     */
    public function approvedDocument(int $userId): ?array;
}
