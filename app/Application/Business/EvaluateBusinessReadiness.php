<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Business\ReadinessRequirements;
use App\Domain\Business\ReadinessStage;
use App\Domain\Identity\PartyNoticeKind;
use Illuminate\Support\Facades\DB;

/**
 * The readiness sweep (BRS v3.0 RDY-R02, RDY-R03, NTF-R01), run hourly so every transition lands
 * on its exact Kigali day. For each Business that has entered the platform it settles the stage
 * from its published monthly reports, records any requirement that stage triggers, turns Due and
 * Lapsed requirements Overdue on the day their grace ends, and tells the Business: a requirement
 * Due, 14 days left, Overdue, and a stage change on the report that moved it. Each notice has its
 * own event key, so a replayed sweep records nothing new. Business-only.
 */
final class EvaluateBusinessReadiness
{
    public function __construct(private ReadinessRequirementStore $requirements, private PublishedMonthlyReports $reports,
        private ReadinessStage $stage, private ReadinessRequirements $rules, private PartyNotices $notices, private BusinessNoticeAudience $audience) {}

    /** @return int the Businesses evaluated */
    public function handle(): int
    {
        $today = now('Africa/Kigali')->toDateString();
        $businesses = $this->requirements->enteredBusinesses();
        foreach ($businesses as $businessId) {
            DB::transaction(fn () => $this->evaluate($businessId, $today));
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
            if ($this->requirements->trigger($businessId, $code, $start['status'], $start['due_on'], $today) && $start['status'] === 'due') {
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementDue, ['business_id' => $businessId, 'code' => $code,
                    'due_on' => (string) $start['due_on']], "readiness-due:{$businessId}:{$code}:{$today}");
            }
        }
        foreach ($this->requirements->forBusiness($businessId) as $requirement) {
            $dueOn = $requirement['due_on'];
            if ($dueOn === null || ! in_array($requirement['status'], ['due', 'lapsed'], true)) {
                continue;
            }
            $facts = ['business_id' => $businessId, 'code' => $requirement['code'], 'due_on' => $dueOn];
            if ($this->rules->on($requirement['status'], $dueOn, $today) === 'overdue') {
                $this->requirements->move($businessId, $requirement['code'], [$requirement['status']], 'overdue', $dueOn);
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementOverdue, $facts, "readiness-overdue:{$businessId}:{$requirement['code']}:{$dueOn}");
            } elseif ($this->rules->daysLeft($dueOn, $today) <= ReadinessRequirements::REMINDER_DAYS) {
                $this->notices->record($party, PartyNoticeKind::BusinessReadinessRequirementClosing, $facts, "readiness-closing:{$businessId}:{$requirement['code']}:{$dueOn}");
            }
        }
    }
}
