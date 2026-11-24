<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use Closure;

/**
 * A person's application to switch from Investor to Business representative or Auditor (Erastus, 23 Nov
 * 2026). It may be filled in and sent once the person's own KYC has been sent, without waiting for its
 * approval; Compliance approves the switch only once that KYC is approved and the Investor role has
 * nothing left open. Every participant command locks the account and its Party first and rechecks the
 * identity context.
 *
 * @phpstan-import-type State from \App\Domain\Identity\RoleApplicationCase
 *
 * @phpstan-type Standing array{
 *     revision: int,
 *     status: 'none'|'draft'|'submitted'|'changes_requested'|'approved'|'rejected',
 *     role: 'business'|'auditor'|null,
 *     state: State|null,
 *     kyc: 'none'|'draft'|'submitted'|'approved'|'rejected',
 *     roles: list<string>,
 *     blockers: list<string>,
 *     identity_context_revision: int
 * }
 * @phpstan-type Row array{id: string, revision: int, role: 'business'|'auditor', status: string, name: string, email: string,
 *     company: string|null, company_code: string|null, submitted_at: string|null, kyc: string}
 * @phpstan-type Queue array{role: 'business'|'auditor', tab: string, entries: list<Row>, counts: array<string, int>}
 * @phpstan-type Review array{
 *     id: string, revision: int, role: 'business'|'auditor', status: string, submitted_at: string|null, decided_at: string|null,
 *     account: array{name: string, email: string, two_factor: bool}, kyc: string, kyc_verification_id: string|null,
 *     state: State, people: array<string, array{full_name: string, roles: list<string>}>,
 *     documents: list<array{id: string, slot: string, filename: string, media_type: string, size_bytes: int, uploaded_at: string, current: bool}>,
 *     history: list<array{revision: int, status: string, command: string, reason: string|null, at: string}>,
 *     blockers: list<string>, problems: list<string>, business_id: string|null, allowed_actions: list<string>
 * }
 * @phpstan-type Approval array{party_id: string, user_id: int, email: string, state: State, reference: string, request_id: string,
 *     certificate: array{filename: string, content: string}|null}
 */
interface RoleApplicationStore
{
    /** @return Standing */
    public function get(int $userId): array;

    /**
     * Saves one step of the application for `$role`. A first save starts it; choosing the other role
     * while it may still be changed starts it again for that role.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, int $expectedRevision, string $role, string $step, array $input, string $requestId): array;

    /** @return array<string, mixed> */
    public function upload(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array;

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, bool $declared, string $requestId): array;

    /**
     * The person's current uploads by slot, to show back to them: never the bytes.
     *
     * @return array<string, array{id: string, filename: string, media_type: string}>
     */
    public function uploads(int $userId): array;

    /** @return array{filename: string, media_type: string, content: string} */
    public function ownDocument(int $userId, string $slot): array;

    /** @return Queue */
    public function queue(int $actorId, string $role, string $tab, string $search, int $limit): array;

    /**
     * The open application of each listed person, by party id, for the admin directories' rows.
     *
     * @param  list<string>  $partyIds
     * @return array<string, array{id: string, role: string, status: string}>
     */
    public function forParties(array $partyIds): array;

    /** @return Review */
    public function show(int $actorId, string $applicationId): array;

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $applicationId, string $documentId): array;

    /**
     * Approve (`approve`), ask for more (`more`) or reject (`reject`) a sent application with a note. An
     * approval runs `$switch` under the application's lock once the KYC is approved, the application is
     * complete and the Investor role has nothing open; it returns the business onboarded, if any. Asking
     * for more names the answers and documents to change (`$items`, each `{key, note?}`), which the
     * applicant sees flagged. Every decision is sent to the applicant in the app and by email.
     *
     * @param  Closure(Approval): (string|null)  $switch
     * @param  list<mixed>  $items
     * @return array<string, mixed>
     */
    public function decide(int $actorId, string $applicationId, int $expectedRevision, string $decision, string $note, string $requestId, Closure $switch, array $items = []): array;
}
