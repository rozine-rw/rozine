<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\EnteredBusinesses;
use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Domain\Business\ReadinessRequirements;
use App\Domain\Business\ReadinessStage;

/**
 * The Business Readiness tab for a current mandate holder (BRS v3.0 Section 20). Its stage comes
 * from the verified revenue of the Business's published monthly reports, read under the same
 * `business.view` authority as every other Business tab. Only published reports count: a sealed
 * report still inside its review window is not final, and a period counts once, at its newest
 * report. Readiness is Business-only: no Investor page reads it, and it never changes a label, a
 * return or any note's terms.
 *
 * Its requirements are the ones triggered so far, as the hourly readiness sweep keeps them. C1 and
 * F1 read Met from entry only once the Business has entered (a published audit report).
 *
 * @phpstan-import-type Stage from ReadinessStage
 * @phpstan-import-type Listed from ReadinessRequirements
 */
final class GetBusinessReadiness
{
    public function __construct(private WithBusinessAuthority $authority, private PublishedMonthlyReports $reports, private ReadinessStage $stage,
        private ReadinessRequirementStore $requirements, private ReadinessRequirements $rules, private EnteredBusinesses $entered) {}

    /** @return array{identity_context_revision: int, business_id: string, readiness: Stage, requirements: list<Listed>} */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $id = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (array $business): string => (string) $business['id']);
        $months = ReadinessStage::months($this->reports->forBusiness($id));

        return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'readiness' => $this->stage->assess($months),
            'requirements' => $this->rules->listed($this->requirements->forBusiness($id), now('Africa/Kigali')->toDateString(), $this->entered->has($id))];
    }
}
