<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use Closure;

/** Internal full-funding lock. No HTTP or worker activation, payout or Holding issuance. */
interface PrimaryFunding
{
    /**
     * Requires an outer transaction. Retains Business before invoking mandatory server
     * admission, which must lock and verify current eligibility, policy, connections and
     * destination and return their retained evidence. The result must bind campaign_id
     * and publication_sha256 and contain eligibility, policy, connections and destination
     * checks, each with status passed and nonempty server evidence. It is never client input. Missing input must throw/refuse;
     * there is no permissive default. Admission may not perform external effects.
     * Campaign, roots, commitments and Party-sorted wallets follow those locks. The
     * complete immutable purchase/cash set is rechecked even on a retry. All evidence
     * rolls back together on refusal. Funding retains committed cash and the original
     * exposure once; conversion and issued Holdings require reconciled-success issue.
     *
     * @param  Closure(string): array<string, mixed>  $admit
     * @return array<string, mixed>
     */
    public function lock(string $campaignId, Closure $admit): array;
}
