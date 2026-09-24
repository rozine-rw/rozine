import type { ReactNode } from 'react';
import { AssignedJobCard } from '@/components/auditor/assigned-job-card';
import { EligibleCard } from '@/components/auditor/jobs/eligible-card';
import { MonthlySection } from '@/components/auditor/jobs/monthly-section';
import { RadiusMap } from '@/components/auditor/jobs/radius-map';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import type { ColumnOverlay } from '@/components/auditor/tab-columns';
import { EmptyState, Eyebrow, ScreenTitle } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorJobsProps } from '@/types/auditor';

type JobsBodyProps = AuditorJobsProps & {
    overlay?: ColumnOverlay | null;
    backdrop?: boolean;
    /** What happened to the last command, when Jobs is the page itself. */
    notice?: ReactNode;
};

/**
 * Jobs (MVP-AUDITOR-SCR-01, design L205–323): the Flash Audits dispatch offers this partner, with
 * the radius map, and any work already on the clock; beside them, the monthly reports for the notes
 * they steward. The desk-review cards (disputes) are Phase 2 and are left out.
 */
export function JobsBody({
    overlay = null,
    backdrop = false,
    notice = null,
    ...props
}: JobsBodyProps) {
    const { t } = useTranslation();

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
                    {notice}
                    <RadiusMap
                        radiusKm={props.radius_km}
                        jobs={props.eligible}
                    />
                    {props.assigned.length > 0 && (
                        <section>
                            <Eyebrow as="h2" className="mt-5">
                                {t('auditor.jobs.assigned')}
                            </Eyebrow>
                            <div className="mt-2.5 flex flex-col gap-[11px]">
                                {props.assigned.map((job) => (
                                    <AssignedJobCard
                                        key={job.id}
                                        job={job}
                                        serverTime={props.server_time}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                    {props.eligible.length > 0 ? (
                        <div className="mt-3.5 flex flex-col gap-3">
                            {props.eligible.map((job) => (
                                <EligibleCard
                                    key={job.id}
                                    job={job}
                                    serverTime={props.server_time}
                                    flashHours={props.flash_hours}
                                    declineOptions={props.decline_options}
                                />
                            ))}
                        </div>
                    ) : (
                        <EmptyState>
                            {t('auditor.jobs.empty', {
                                radius: props.radius_km,
                            })}
                        </EmptyState>
                    )}
                </ColumnPad>
            }
            right={
                <ColumnPad side="right">
                    <MonthlySection
                        windows={props.monthly.windows}
                        reports={props.monthly.reports}
                    />
                </ColumnPad>
            }
        />
    );
}
