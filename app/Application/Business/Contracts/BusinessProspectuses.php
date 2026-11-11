<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * A Business's prospectus for one accepted application (BRS v3.0 PRO-R02 to PRO-R04). Drafts are
 * saved as numbered immutable versions under `application.save`; the official representative (the
 * first required signatory, holding `application.sign`) alone signs the latest draft once gates P-G01 to P-G11
 * pass, which locks it as the signed version under the fingerprint of its canonical form.
 *
 * @phpstan-import-type Inputs from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type Facts from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type Gate from \App\Domain\Business\ProspectusGates
 *
 * @phpstan-type Prospectus array{application_id: string, business_id: string, title: string, sections: list<string>, version: int, inputs: Inputs,
 *     signed: array{version: int, fingerprint: string, signed_at: string}|null, facts: Facts|null, unavailable: string|null,
 *     evaluation: array{policy_version: string, gates: list<Gate>, may_sign: bool, publishable: bool}|null,
 *     documents: list<array{id: string, kind: string, filename: string, media_type: string, size_bytes: int, uploaded_at: string}>, can_edit: bool, can_sign: bool}
 * @phpstan-type SignedSummary array{version: int, fingerprint: string, signed_at: string, signed_by: string}
 * @phpstan-type PublishedInputs array{purpose: string, use_of_funds: list<array{label: string, amount: string}>,
 *     forecast: list<array{month: string, revenue: string, costs: string}>, assumptions: string, downside: string,
 *     profiles: array<int, array{name: string, role: string, text: string}>, largest_customer_share_percent: int|null,
 *     risks: array<int, array{text: string, concentration: bool}>}
 * @phpstan-type PublishedProspectus array{summary: SignedSummary, inputs: PublishedInputs,
 *     facts: array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null},
 *     quoted_lines: list<int>, contracted_months: list<string>}
 */
interface BusinessProspectuses
{
    /**
     * The latest version with its gate evaluation, read under `business.view`. Before the first save
     * the inputs are blank, with a profile prefilled for each person the prospectus must introduce.
     *
     * @return Prospectus
     */
    public function current(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, array $input, string $requestId): array;

    /**
     * Keeps a quote, invoice or signed contract as evidence the prospectus may cite (P-G05, P-G06).
     *
     * @return array<string, mixed>
     */
    public function upload(int $userId, int $contextRevision, string $businessId, string $applicationId, string $kind, string $filename, string $content, string $requestId): array;

    /** @return array<string, mixed> */
    public function sign(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, string $requestId): array;

    /**
     * The signed prospectus behind a published campaign, as an Investor reads it (PRO-R04), or null
     * when that campaign's application has no signed version. It carries no document ids, filenames
     * or registered names: only which lines cite a quote and which months cite a contract. It names
     * the person who signed, as recorded in the signed document.
     *
     * @return PublishedProspectus|null
     */
    public function published(string $campaignId): ?array;
}
