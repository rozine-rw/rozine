<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\Contracts\HeldVisitDocuments;
use App\Application\Business\Contracts\UpcomingInstalments;
use App\Application\Business\Contracts\VisitReadyTicks;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Auditor\VisitReadyChecklist;
use App\Domain\Identity\PartyNoticeKind;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The visit-ready checklist notices (BRS v3 VIS-R03, AUD-R04), once per Business and instalment:
 * at D−14 the checklist is ready in the repayment reminder; at D−11 a reminder names how many items
 * are still not ticked, and is not sent when every item is. Neither counts a certificate the CPA
 * already holds. A run that misses the day catches up
 * until the next step begins (D−12 for the first notice, D−10 for the reminder), never after.
 */
final readonly class RemindVisitReady
{
    /** The reminder is sent from D−11 until the monthly figures fall due at D−10. */
    private const int REMIND_DAYS_BEFORE = 11;

    public function __construct(private UpcomingInstalments $instalments, private VisitReadyTicks $ticks, private PartyNotices $notices,
        private BusinessNoticeAudience $audience, private VisitReadyChecklist $checklist, private HeldVisitDocuments $held) {}

    /** @return array{opened: int, reminded: int} */
    public function handle(): array
    {
        $today = CarbonImmutable::now('Africa/Kigali')->startOfDay();
        $from = $today->addDays(VisitReadyChecklist::VISIT_FROM_DAYS_BEFORE)->toDateString();
        $to = $today->addDays(VisitReadyChecklist::OPENS_DAYS_BEFORE)->toDateString();
        $sent = ['opened' => 0, 'reminded' => 0];
        foreach ($this->instalments->between($from, $to) as ['business_id' => $business, 'due_on' => $due]) {
            $days = (int) $today->diffInDays(CarbonImmutable::parse($due, 'Africa/Kigali')->startOfDay());
            $party = $this->audience->findEntityParty($business);
            if ($party === null) {
                continue;
            }
            $items = array_column($this->checklist->items($this->held->held($business, VisitReadyChecklist::visitBy($due))), 'key');
            if ($days > self::REMIND_DAYS_BEFORE) {
                DB::transaction(fn () => $this->notices->record($party, PartyNoticeKind::BusinessVisitReadyOpen,
                    ['business_id' => $business, 'due_on' => $due, 'items' => (string) count($items)], 'business-visit-ready-open:'.$business.':'.$due));
                $sent['opened']++;

                continue;
            }
            $open = count(array_diff($items, $this->ticks->ticked($business, $due)));
            if ($open > 0) {
                DB::transaction(fn () => $this->notices->record($party, PartyNoticeKind::BusinessVisitReadyReminder,
                    ['business_id' => $business, 'due_on' => $due, 'open' => (string) $open, 'items' => (string) count($items)], 'business-visit-ready-reminder:'.$business.':'.$due));
                $sent['reminded']++;
            }
        }

        return $sent;
    }
}
