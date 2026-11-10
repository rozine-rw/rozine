<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * A Business's prospectus for one accepted application (BRS v3.0 PRO-R02 to PRO-R04). Drafts are
 * saved as numbered immutable versions under `application.save`; the official representative, a
 * required signatory holding `application.sign`, signs the latest draft once gates P-G01 to P-G11
 * pass, which locks it as the signed version under the fingerprint of its canonical form.
 *
 * @phpstan-import-type Inputs from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type Facts from \App\Domain\Business\ProspectusGates
 * @phpstan-import-type Gate from \App\Domain\Business\ProspectusGates
 *
 * @phpstan-type Prospectus array{application_id: string, business_id: string, title: string, sections: list<string>, version: int, inputs: Inputs,
 *     signed: array{version: int, fingerprint: string, signed_at: string}|null, facts: Facts|null, unavailable: string|null,
 *     evaluation: array{policy_version: string, gates: list<Gate>, may_sign: bool, publishable: bool}|null, can_edit: bool, can_sign: bool}
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

    /** @return array<string, mixed> */
    public function sign(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, string $requestId): array;
}
