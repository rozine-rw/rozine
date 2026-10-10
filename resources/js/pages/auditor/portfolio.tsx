import { useState } from 'react';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
} from '@/components/auditor/commands';
import {
    AuditCalendar,
    calendarItems,
    DueList,
    monthStart,
} from '@/components/auditor/portfolio/audit-calendar';
import { ConflictRegister } from '@/components/auditor/portfolio/conflict-register';
import {
    CpaCardLink,
    EarningsSummary,
    ManagedDeals,
    SourcedDeals,
    YieldShare,
} from '@/components/auditor/portfolio/earnings';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import { ScreenTitle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorPortfolioProps } from '@/types/auditor';

/**
 * Portfolio (design L322–591). On the left: how the partner earns, their CPA card, the deals they
 * sourced, the conflict register, this month's earnings, the yield-share chart and the audit
 * calendar. On the right: managed deals and what falls due in the calendar's open month.
 *
 * The calendar is built from the partner's own reads: each verification still owed by its due
 * date, and each filed report (MVP-AUDITOR-SCR-07) with its co-signature state and, if rejected,
 * its linked amendment. Earnings, origination, yield-share and managed deals have no read yet and
 * show the design's empty states. A declaration is offered only while `allowed_actions` lists
 * `conflict.declare`.
 */
export default function AuditorPortfolio(props: AuditorPortfolioProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });
    const { today, items } = calendarItems(props);
    const [offset, setOffset] = useState(0);
    const [picked, setPicked] = useState<string | null>(null);
    const month = monthStart(today, offset);

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.portfolio.head_title')}
                tab="portfolio"
                links={props.links}
                openJobs={props.open_jobs}
            >
                <TabColumns
                    left={
                        <ColumnPad side="left" tab>
                            <ScreenTitle
                                title={t('auditor.portfolio.title')}
                                lead={t('auditor.portfolio.lead')}
                            />
                            <CpaCardLink profile={props.links.profile} />
                            <AuditorCommandNotice
                                placement="page"
                                className="mt-3.5"
                            />
                            <SourcedDeals />
                            <ConflictRegister conflicts={props.conflicts} />
                            <EarningsSummary />
                            <YieldShare />
                            <AuditCalendar
                                today={today}
                                items={items}
                                month={month}
                                onMonth={(step) => {
                                    setOffset((current) => current + step);
                                    setPicked(null);
                                }}
                                picked={picked}
                                onPick={setPicked}
                                complete={props.owed_complete}
                                jobs={props.links.jobs}
                            />
                        </ColumnPad>
                    }
                    right={
                        <ColumnPad side="right">
                            <ManagedDeals />
                            <DueList
                                items={items}
                                month={month}
                                complete={props.owed_complete}
                                jobs={props.links.jobs}
                            />
                        </ColumnPad>
                    }
                />
                <OutcomeModal
                    outcome={center.result?.outcome ?? props.outcome}
                    onDone={center.finish}
                />
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
