<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AccreditationProfile
 *
 * @phpstan-type Profile array{id: string|null, party_id: string, revision: int, state: State}
 * @phpstan-type Download array{filename: string, media_type: string, content: string, sha256: string}
 */
interface AuditorProfileStore
{
    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, string $licence, string $expiresOn, string $filename, string $content, string $requestId, bool $renew = false): array;

    /** @return array<string, mixed> */
    public function withdraw(int $userId, int $contextRevision, int $expectedRevision, string $submissionId, string $requestId): array;

    /**
     * Whether dispatch may offer the partner work and, when given, the districts they accept and how
     * many jobs a week they take (the Profile's Availability & coverage).
     *
     * @param  list<mixed>|null  $districts
     * @return array<string, mixed>
     */
    public function availability(int $userId, int $contextRevision, int $expectedRevision, bool $accepting, string $requestId, ?array $districts = null, ?int $maxJobsPerWeek = null): array;

    /**
     * The partner's registered address (province, district, sector, cell and street).
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    public function address(int $userId, int $contextRevision, int $expectedRevision, array $address, string $requestId): array;

    /**
     * Records the ICPAR licence Compliance verified with an Auditor application (one step): the
     * certificate is kept as the partner's own, their standing is active from that review, the
     * districts and address they applied with become their coverage, and they start accepting
     * audits. Runs inside the approval's transaction, after it has locked the Party.
     *
     * @param  list<string>  $districts
     * @param  array{province: string, district: string, sector: string, cell: string, street: string}|null  $address
     */
    public function accreditFromIdentityCheck(int $actorId, string $partyId, string $licence, string $expiresOn, string $filename, string $content, string $reference, string $reason, array $districts = [], ?array $address = null): void;

    /** @return array<string, mixed> */
    public function review(int $actorId, string $partyId, int $expectedRevision, string $decision, ?string $submissionId, string $checkedAt, string $reference, string $reason, string $requestId): array;

    /**
     * Reminds each active partner whose licence lapses within the renewal notice to upload the
     * renewal, once per licence expiry.
     *
     * @return int the reminders recorded
     */
    public function remindRenewals(): int;

    /**
     * A partner's coverage as staff who verify partners see it on the Auditors page: whether they
     * accept audits, the districts they accept, their weekly limit and their registered address.
     *
     * @return array{accepting: bool, districts: list<string>, max_jobs_per_week: int, address: array{province: string, district: string, sector: string, cell: string, street: string}|null}|null
     */
    public function coverage(int $actorId, string $partyId): ?array;

    /** @return array<string, mixed> */
    public function accreditation(int $userId, int $contextRevision): array;

    /** @return Profile */
    public function get(int $userId, int $contextRevision): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;

    /** @return Download */
    public function readCertificate(int $userId, ?int $contextRevision, string $partyId, string $certificateId, bool $staff): array;
}
