<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The Admin console's Users page: every sign-in account on the platform, whatever it does — Investor,
 * Business, Audit Partner, staff, or none of these yet — with the roles it holds, its identity (KYC)
 * state and its account basics. Reads only and never locks; the caller checks the staff permission.
 *
 * A participant role is a Role Membership of the account's Party, with that membership's status. A
 * staff role is a role on the account's Staff Account, which is enabled or not. A role the person
 * asked for from the launcher and does not hold yet is listed as requested.
 *
 * KYC is the person's identity verification: verified once the Party is verified, otherwise the
 * state of their verification case (in review when submitted, in progress while a draft, rejected
 * while the last decision stands), and not started when no case exists.
 *
 * `everyone`, `new_this_week`, `kyc_verified` and `awaiting_review` count the whole platform's open
 * accounts, whatever the filters, and `closed` counts its closed (soft-deleted) accounts. The page
 * lists open accounts, or with `$closed` the closed ones, newest closure first by default, with
 * the closing date and the holder's own reason.
 *
 * @phpstan-type Kyc 'verified'|'in_review'|'in_progress'|'rejected'|'not_started'
 * @phpstan-type Membership array{role: 'investor'|'business'|'auditor', status: 'pending'|'active'|'suspended'|'revoked', since: string}
 * @phpstan-type Staff array{roles: list<string>, enabled: bool}|null
 * @phpstan-type DirectoryRow array{
 *     id: int, party_id: string|null, name: string, email: string, email_verified: bool, signed_up_at: string, last_seen_at: string|null,
 *     memberships: list<Membership>, staff: Staff, requested: list<'business'|'auditor'|'institution'>, institution: string|null, kyc: Kyc, restricted: bool,
 *     closed_at: string|null, closure_reason: string|null
 * }
 * @phpstan-type Counts array{all: int, investor: int, business: int, auditor: int, staff: int, no_role: int, institution: int}
 * @phpstan-type Directory array{rows: list<DirectoryRow>, matching: int, counts: Counts, everyone: int, new_this_week: int, kyc_verified: int, awaiting_review: int, closed: int}
 * @phpstan-type Detail array{
 *     row: DirectoryRow, email_verified_at: string|null, two_factor: bool, passkeys: int, verification_id: string|null, submitted_at: string|null,
 *     requests: list<array{role: 'business'|'auditor', organization: string, reference: string|null, sent_at: string}>,
 *     history: list<array{id: string, at: string, actor: string, action: string, reason: string}>
 * }
 */
interface UserDirectoryStore
{
    /**
     * One page of accounts under a role chip, KYC filter, sort and name or email search.
     *
     * @param  'all'|'investor'|'institution'|'business'|'auditor'|'staff'|'no_role'  $role
     * @param  'any'|Kyc  $kyc
     * @param  'newest'|'oldest'|'name'  $sort
     * @return Directory
     */
    public function directory(string $role, string $kyc, string $sort, string $search, int $limit, bool $closed = false): array;

    /** @return Detail|null */
    public function user(int $userId): ?array;
}
