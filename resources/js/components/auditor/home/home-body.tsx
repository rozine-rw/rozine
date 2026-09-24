import { Link } from '@inertiajs/react';
import { AssignedJobCard } from '@/components/auditor/assigned-job-card';
import { AvailabilityRow } from '@/components/auditor/availability';
import { AuditorCommandNotice } from '@/components/auditor/commands';
import { ActivityList } from '@/components/auditor/home/activity-list';
import { Hero } from '@/components/auditor/home/hero';
import { TopRow } from '@/components/auditor/home/top-row';
import { ColumnPad, TabColumns } from '@/components/auditor/tab-columns';
import { AMBER_TEXT, Eyebrow } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { AuditorHomeProps, AuditorStanding } from '@/types/auditor';

/** "N Flash Audits nearby" (design L144–156): pulses until the partner looks. */
function NearbyAlert({
    count,
    closest,
    jobs,
}: {
    count: number;
    closest: string | null;
    jobs: RouteLink;
}) {
    const { t } = useTranslation();

    return (
        <Link
            href={jobs}
            className="mt-4 block w-full animate-[rz-aud-pulse_2.4s_infinite] rounded-2xl border-[1.5px] border-[#f0d18f] bg-rz-surface p-[15px] text-left dark:border-[rgba(240,160,96,.45)]"
        >
            <span className="flex items-center gap-3">
                <span className="relative flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#c2661f]">
                    <span
                        aria-hidden
                        className="absolute inset-0 animate-[rz-aud-ping_1.9s_ease-out_infinite] rounded-xl bg-[#c2661f]"
                    />
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="relative size-[22px]"
                    >
                        <path
                            d="M12 21.2c4.1-4 6.2-7.1 6.2-9.6A6.2 6.2 0 0 0 5.8 11.6c0 2.5 2.1 5.6 6.2 9.6Z"
                            fill="rgba(255,255,255,.2)"
                            stroke="#fff"
                            strokeWidth="1.7"
                            strokeLinejoin="round"
                        />
                        <circle cx="12" cy="11.3" r="2.35" fill="#fff" />
                    </svg>
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block text-[14.5px] font-bold text-rz-ink">
                        {count === 1
                            ? t('auditor.home.nearby_one')
                            : t('auditor.home.nearby_other', { count })}
                    </span>
                    {closest !== null && (
                        <span
                            className={cn(
                                'mt-0.5 block text-[11.5px]',
                                AMBER_TEXT,
                            )}
                        >
                            {t('auditor.home.nearby_sub', {
                                distance: closest,
                            })}
                        </span>
                    )}
                </span>
                <span aria-hidden className="shrink-0 text-[18px] text-rz-ink">
                    →
                </span>
            </span>
        </Link>
    );
}

function StandingTile({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <div className="rounded-2xl border border-rz-border bg-rz-surface p-[13px]">
            <p className="text-[10px] font-bold text-rz-secondary uppercase">
                {label}
            </p>
            <p className={cn('mt-1 text-[20px] font-bold', tone)}>{value}</p>
        </div>
    );
}

/** Your standing (design L174–181), coloured by the server's own flags. */
function Standing({ standing }: { standing: AuditorStanding }) {
    const { t } = useTranslation();

    return (
        <section>
            <Eyebrow as="h2" className="mt-5">
                {t('auditor.standing.title')}
            </Eyebrow>
            <div className="mt-2.5 grid grid-cols-2 gap-[9px]">
                <StandingTile
                    label={t('auditor.standing.on_time')}
                    value={
                        standing.on_time_pct === null
                            ? '—'
                            : `${standing.on_time_pct}%`
                    }
                    tone="text-rz-positive"
                />
                <StandingTile
                    label={t('auditor.standing.avg_variance')}
                    value={
                        standing.avg_variance_pct === null
                            ? '—'
                            : `${standing.avg_variance_pct}%`
                    }
                    tone={
                        standing.variance_flagged
                            ? 'text-rz-danger-text'
                            : 'text-rz-positive'
                    }
                />
                <StandingTile
                    label={t('auditor.standing.jobs_done')}
                    value={String(standing.jobs_done)}
                    tone="text-rz-ink"
                />
                <StandingTile
                    label={t('auditor.standing.clock_expiries')}
                    value={String(standing.clock_expiries)}
                    tone={
                        standing.clock_expiries > 0
                            ? 'text-rz-ink'
                            : 'text-rz-positive'
                    }
                />
            </div>
        </section>
    );
}

/**
 * Home (design L86–203): wallet and bell above; identity, availability, nearby work, jobs on the
 * clock and standing on the left; recent activity on the right.
 */
export function HomeBody(props: AuditorHomeProps) {
    const { t } = useTranslation();

    return (
        <TabColumns
            header={
                <TopRow
                    available={props.wallet.available}
                    unread={props.unread_notifications}
                    links={props.links}
                />
            }
            left={
                <ColumnPad side="left">
                    <div className="mt-3.5 lg:mt-0">
                        <Hero
                            serverTime={props.server_time}
                            auditor={props.auditor}
                            qualityScore={props.quality_score}
                            earned={props.earned_this_month}
                            activeDeals={props.active_deals}
                            licenceExpiresOn={props.licence_expires_on}
                        />
                    </div>
                    <AvailabilityRow availability={props.availability} />
                    <AuditorCommandNotice placement="page" className="mt-3.5" />
                    {props.nearby.count > 0 && (
                        <NearbyAlert
                            count={props.nearby.count}
                            closest={props.nearby.closest_km}
                            jobs={props.links.jobs}
                        />
                    )}
                    {props.in_progress.length > 0 && (
                        <section>
                            <Eyebrow as="h2" className="mt-5">
                                {t('auditor.home.in_progress')}
                            </Eyebrow>
                            <div className="mt-2.5 flex flex-col gap-[11px]">
                                {props.in_progress.map((job) => (
                                    <AssignedJobCard
                                        key={job.id}
                                        job={job}
                                        serverTime={props.server_time}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                    <Standing standing={props.standing} />
                </ColumnPad>
            }
            right={
                <ColumnPad side="right">
                    <ActivityList
                        activity={props.activity}
                        serverTime={props.server_time}
                    />
                </ColumnPad>
            }
        />
    );
}
