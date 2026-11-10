<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\Contracts\EnteredBusinesses;
use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Business\ReadinessRequirements;
use App\Domain\Business\ReadinessStage;
use App\Domain\Identity\PartyNoticeKind;

/**
 * The readiness sweep (BRS v3.0 RDY-R02, RDY-R03, NTF-R01), run hourly so every transition lands
 * on its exact Kigali day. For each Business that has entered the platform it settles the stage
 * from its published monthly reports, records any requirement that stage triggers, turns Due and
 * Lapsed requirements Overdue on the day their grace ends, and tells the Business: a requirement
 * Due, 14 days left, Overdue, and a stage change on the report that moved it. Each notice has its
 * own event key, so a replayed sweep records nothing new, and every step reads what the last one
 * stored: a sweep stopped part-way is finished by the next one. Business-only.
 */
final class EvaluateBusinessReadiness
{
    public function __construct(private ReadinessRequirementStore $requirements, private PublishedMonthlyReports $reports,
        private ReadinessStage $stage, private ReadinessRequirements $rules, private PartyNotices $notices, private BusinessNoticeAudience $audience,
        private EnteredBusinesses $entered) {}

    /** @return int the Businesses evaluated */
    public function handle(): int
    {
        $today = now('Africa/Kigali')->toDateString();
        $businesses = $this->entered->all();
        foreach ($businesses as $businessId) {
            $this->evaluate($businessId, $today);
        }

        return count($businesses);
    }

    private function evaluate(string $businessId, string $today): void
    {
        $party = $this->audience->entityParty($businessId);
        $months = ReadinessStage::months($this->reports->forBusiness($businessId));
        $now = $this->stage->assess($months);
        $before = $this->stage->assess(array_slice($months, 0, -1));
        if ($now['stage'] !== null && $before['stage'] !== null && $now['stage'] !== $before['stage']) {
            $period = $now['months'][0]['period'];
            $this->notices->record($party, PartyNoticeKind::BusinessReadinessStageChanged, ['business_id' => $businessId,
                'stage' => (string) $now['stage'], 'previous' => (string) $before['stage'], 'period' => $period],
                "readiness-stage:{$businessId}:{$period}:{$now['stage']}");
        }
        foreach ($this->rules->triggered($now['stage'] ?? 0) as $code) {
            $start = $this->rules->start($code, $today);
            $this->requirements->trigger($businessId, $code, $start['status'], $start['due_on'], $today);
        }
        foreach ($this->requirements->forBusiness($businessId) as $requirement) {
            $code = $requirement['code'];
            $dueOn = $requirement['due_on'];
            if ($dueOn === null) {
                continue;
            }
            $facts = ['business_id' => $businessId, 'code' => $code, 'due_on' => $dueOn];
            if ($requirement['status'] === 'due') {
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementDue, $facts, "readiness-due:{$businessId}:{$code}:{$requirement['triggered_on']}");
            }
            if ($this->rules->on($requirement['status'], $dueOn, $today) === 'overdue') {
                $this->requirements->move($businessId, $code, ['due', 'lapsed'], 'overdue', $dueOn);
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementOverdue, $facts, "readiness-overdue:{$businessId}:{$code}:{$dueOn}");
            } elseif ($this->rules->daysLeft($dueOn, $today) <= ReadinessRequirements::REMINDER_DAYS) {
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementClosing, $facts, "readiness-closing:{$businessId}:{$code}:{$dueOn}");
            }
        }
    }
}
