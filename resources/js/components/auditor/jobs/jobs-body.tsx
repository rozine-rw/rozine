import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AssignedJobCard } from '@/components/auditor/assigned-job-card';
import { EngagementBanner } from '@/components/auditor/engagement/engagement-banner';
import { EligibleCard } from '@/components/auditor/jobs/eligible-card';
import { MonthlySection } from '@/components/auditor/jobs/monthly-section';
import { RadiusMap } from '@/components/auditor/jobs/radius-map';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import type { ColumnOverlay } from '@/components/auditor/tab-columns';
import {
    EmptyState,
    Eyebrow,
    ScreenTitle,
    ShowMore,
} from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import type { AuditorJobsProps } from '@/types/auditor';

type JobsBodyProps = AuditorJobsProps & {
    overlay?: ColumnOverlay | null;
    backdrop?: boolean;
    /** What happened to the last command, when Jobs is the page itself. */
    notice?: ReactNode;
};

/**
 * The Jobs tab badge: the open offers, but only on a synthetic preview, which holds the complete
 * list. A live, paged page counts only its own records, even on its last page — the server sends
 * no total — so no badge claims one.
 */
export const openOffers = (jobs: AuditorJobsProps): number =>
    jobs.pagination === undefined ? jobs.eligible.length : 0;

/**
 * Jobs (MVP-AUDITOR-SCR-01, design L205–323): the Flash Audits dispatch offers this partner, with
 * the radius map, and any work already on the clock; beside them, the monthly reports for the notes
 * they steward. While the server sends no monthly section, a wide screen puts the work on the clock
 * in the right column instead, so neither column stands empty; a phone keeps one flow, assigned
 * above the offers. The desk-review cards (disputes) are Phase 2 and are left out. The offers end
 * in "Show more" whenever the server names a next page, even after an empty page, which is never
 * read as the end of the history.
 */
export function JobsBody({
    overlay = null,
    backdrop = false,
    notice = null,
    ...props
}: JobsBodyProps) {
    const { t } = useTranslation();
    const next = props.pagination?.next ?? null;
    const wide = useWide();
    /* The assigned list takes the right column only on a wide screen with no monthly section. */
    const assignedBeside = wide && props.monthly === null;
    const assignedCards = (
        <div className="mt-2.5 flex flex-col gap-[11px]">
            {props.assigned.map((job) => (
                <AssignedJobCard
                    key={job.id}
                    job={job}
                    serverTime={props.server_time}
                />
            ))}
        </div>
    );
    let right: ReactNode = null;

    if (props.monthly !== null) {
        right = (
            <ColumnPad side="right">
                <MonthlySection
                    windows={props.monthly.windows}
                    reports={props.monthly.reports}
                />
            </ColumnPad>
        );
    } else if (assignedBeside) {
        right = (
            <ColumnPad side="right">
                <section>
                    <h2 className="text-[17px] font-bold text-rz-ink">
                        {t('auditor.jobs.assigned')}
                    </h2>
                    {props.assigned.length > 0 ? (
                        assignedCards
                    ) : (
                        <EmptyState className="py-[26px]">
                            {next === null
                                ? t('auditor.jobs.assigned_empty')
                                : t('auditor.jobs.assigned_page_empty')}
                        </EmptyState>
                    )}
                </section>
            </ColumnPad>
        );
    }

    return (
        <TabColumns
            backdrop={backdrop}
            overlay={overlay}
            left={
                <ColumnPad side="left" tab>
                    <ScreenTitle
                        title={t('auditor.jobs.title')}
                        lead={t('auditor.jobs.lead', {
                            radius: props.radius_km,
                            hours: props.flash_hours,
                        })}
                    />
                    <EngagementBanner
                        engagement={props.engagement}
                        className="mt-3.5"
                    />
                    {notice}
                    <RadiusMap
                        radiusKm={props.radius_km}
                        jobs={props.eligible}
                        paged={props.pagination !== undefined}
                    />
                    {!assignedBeside && props.assigned.length > 0 && (
                        <section>
                            <Eyebrow as="h2" className="mt-5">
                                {t('auditor.jobs.assigned')}
                            </Eyebrow>
                            {assignedCards}
                        </section>
                    )}
                    {props.eligible.length > 0 ? (
                        <div className="mt-3.5 flex flex-col gap-3">
                            {props.eligible.map((job) => (
                                <EligibleCard
                                    key={job.id}
                                    job={job}
                                    serverTime={props.server_time}
                                    declineOptions={props.decline_options}
                                />
                            ))}
                        </div>
                    ) : next === null ? (
                        <EmptyState>
                            {t('auditor.jobs.empty', {
                                radius: props.radius_km,
                            })}
                        </EmptyState>
                    ) : (
                        (assignedBeside || props.assigned.length === 0) && (
                            <p className="mt-3.5 text-center text-[12px] text-rz-secondary">
                                {t('auditor.jobs.page_empty')}
                            </p>
                        )
                    )}
                    {next !== null && <ShowMore next={next} />}
                    {props.links.conflicts && (
                        <Link
                            href={props.links.conflicts}
                            className="mt-3.5 block text-center text-[12px] font-bold text-rz-ink"
                        >
                            {t('auditor.jobs.conflicts_link')}
                        </Link>
                    )}
                </ColumnPad>
            }
            right={right}
        />
    );
}
