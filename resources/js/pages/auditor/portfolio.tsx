import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
} from '@/components/auditor/commands';
import { ConflictRegister } from '@/components/auditor/portfolio/conflict-register';
import { ReportsSection } from '@/components/auditor/portfolio/reports-section';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import { ScreenTitle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorPortfolioProps } from '@/types/auditor';

/**
 * Portfolio (design L325–591), scoped to the MVP: the conflict register on the left and every filed
 * report on the right (MVP-AUDITOR-SCR-07). The design's earnings, origination, calendar and
 * managed-deal panels are Phase 2 (SCR-08 and beyond) and are left out rather than shown empty.
 * A declaration is offered only while `allowed_actions` lists `conflict.declare`.
 */
export default function AuditorPortfolio(props: AuditorPortfolioProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });

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
                            <AuditorCommandNotice
                                placement="page"
                                className="mt-3.5"
                            />
                            <ConflictRegister conflicts={props.conflicts} />
                        </ColumnPad>
                    }
                    right={
                        <ColumnPad side="right">
                            <ReportsSection
                                reports={props.reports}
                                filter={props.filter}
                                filters={props.filters}
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
