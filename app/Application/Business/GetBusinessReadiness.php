<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\MonthlySubmissionStore;
use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Domain\Business\ReadinessMilestones;
use App\Domain\Business\ReadinessRequirements;
use App\Domain\Business\ReadinessSignals;
use App\Domain\Business\ReadinessStage;
use Brick\Math\BigInteger;

/**
 * The Business Readiness tab for a current mandate holder (BRS v3.0 Section 20). Its stage comes
 * from the verified revenue of the Business's published monthly reports, read under the same
 * `business.view` authority as every other Business tab. Only published reports count: a sealed
 * report still inside its review window is not final, and a period counts once, at its newest
 * report. Readiness is Business-only: no Investor page reads it, and it never changes a label, a
 * return or any note's terms.
 *
 * Its requirements are the ones triggered so far, as the hourly readiness sweep keeps them. Its
 * guidance lines (RDY-R06) compare the monthly figures the Business sent with what its published
 * reports verified, held to the targets of its stage. Its syllabus counts the core Academy
 * modules of its stage passed (RDY-R07). Its milestones (RDY-R08) read the stage, the business's
 * own profile, its funded notes and requirement F4.
 *
 * @phpstan-import-type Stage from ReadinessStage
 * @phpstan-import-type Listed from ReadinessRequirements
 * @phpstan-import-type Line from ReadinessSignals
 * @phpstan-import-type Track from ReadinessMilestones
 */
final class GetBusinessReadiness
{
    public function __construct(private WithBusinessAuthority $authority, private PublishedMonthlyReports $reports, private ReadinessStage $stage,
        private ReadinessRequirementStore $requirements, private ReadinessRequirements $rules, private MonthlySubmissionStore $submissions,
        private ReadinessSignals $signals, private BusinessAcademySyllabus $academy, private BusinessCampaignStore $campaigns) {}

    /** @return array{identity_context_revision: int, business_id: string, readiness: Stage, requirements: list<Listed>, guidance: list<Line>,
     *     syllabus: array{passed: int, total: int}, milestones: array{bond: Track, clinic: Track}} */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $business = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (array $business): array => $business);
        $id = $business['id'];
        $requirements = $this->requirements->forBusiness($id);
        /** @var list<array{status: string}> $notes */
        $notes = $this->campaigns->home($userId, $contextRevision, $id)['notes'];
        $reports = $this->reports->forBusiness($id);
        $core = $this->academy->forBusiness($id)['core'];
        $readiness = $this->stage->assess(ReadinessStage::months($reports));
        $declared = array_map(fn (array $sent): array => ['period' => $sent['period'], 'money_in' => $sent['figures']['money_in'],
            'money_out' => (string) BigInteger::of($sent['figures']['costs_total'])->plus(BigInteger::of($sent['figures']['paid_to_owners'])),
            'forecast_period' => $sent['forecast_period'], 'forecast' => $sent['figures']['forecast']], $this->submissions->forBusiness($id));

        return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'readiness' => $readiness,
            'requirements' => $this->rules->listed($requirements, now('Africa/Kigali')->toDateString()),
            'guidance' => $this->signals->guidance(ReadinessSignals::verified($reports), $declared, $readiness['stage']),
            'syllabus' => ['passed' => count(array_filter($core, fn (array $entry): bool => $entry['status']['state'] === 'passed')), 'total' => count($core)],
            'milestones' => ReadinessMilestones::assess(['stage' => $readiness['stage'],
                'company' => $business['entity_kind'] === 'organization' && $business['profile']['company_code'] !== null,
                'established_year' => $business['profile']['established_year'],
                'notes' => count(array_filter($notes, fn (array $note): bool => $note['status'] === 'funded')),
                'audited' => in_array('F4', array_column(array_filter($requirements, fn (array $row): bool => $row['status'] === 'met'), 'code'), true)],
                now('Africa/Kigali')->toDateTimeImmutable())];
    }
}
