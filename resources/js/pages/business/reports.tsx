import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { AuditGuide } from '@/components/business/reports/audit-guide';
import { periodLabel } from '@/components/business/reports/period-label';
import { ReportSheet } from '@/components/business/reports/report-sheet';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessReportsProps,
    ReportRow,
    ReportStatus,
} from '@/types/business';

const TABS: ReportStatus[] = ['verified', 'in_audit', 'archived'];

const ROW_STYLE: Record<
    ReportStatus,
    { icon: IconName; tile: string; pill: string }
> = {
    verified: {
        icon: 'bar-chart',
        tile: 'bg-rz-accent-soft',
        pill: 'bg-rz-accent-soft text-rz-accent-app-text',
    },
    in_audit: {
        icon: 'shield',
        tile: 'bg-[rgba(194,102,31,.10)]',
        pill: 'bg-[rgba(194,102,31,.10)] text-rz-ink',
    },
    archived: {
        icon: 'archive',
        tile: 'bg-[rgba(105,116,138,.10)]',
        pill: 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
    },
};

/**
 * Reports (MVP-BUSINESS-SCR-06, design L942–1002): the monthly reports the Audit Partner files,
 * by status. A published month that was not healthy keeps the design's amber tile. A report opens in a sheet over the list, addressed by URL so it survives a reload.
 */
export default function BusinessReports({
    reports,
    report,
    policy,
    links,
}: BusinessReportsProps) {
    const { t, locale } = useTranslation();
    const [tab, setTab] = useState<ReportStatus>(
        report?.archived ? 'archived' : 'verified',
    );

    const detail = (row: ReportRow): string => {
        if (row.status === 'in_audit') {
            return t('business.reports.row.in_audit', {
                auditor: row.auditor,
                date:
                    row.seal_by === null
                        ? '—'
                        : formatDayMonth(row.seal_by, locale),
            });
        }

        if (row.period.kind === 'annual') {
            return t('business.reports.row.annual');
        }

        return t('business.reports.row.filed', {
            inflow: row.inflow === null ? '—' : formatRwfShort(row.inflow),
            health:
                row.health === null
                    ? '—'
                    : t(`business.reports.health.${row.health}`),
            auditor: row.auditor,
        });
    };

    return (
        <BusinessShell
            title={t('business.reports.title')}
            tab="reports"
            links={links}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-[92px] lg:grid lg:h-full lg:min-h-0 lg:grid-cols-2 lg:grid-rows-[auto_minmax(0,1fr)] lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                <header className="lg:col-start-1 lg:row-start-1 lg:px-0.5 lg:pt-1">
                    <h1 className="text-2xl font-semibold text-rz-ink">
                        {t('business.reports.title')}
                    </h1>
                    <p className="mt-1 text-[13.5px] text-rz-secondary">
                        {t('business.reports.subtitle')}
                    </p>
                </header>
                <AuditGuide
                    sealDay={policy.seal_day}
                    cosignMinutes={policy.cosign_minutes}
                />
                <div
                    data-rzcol
                    className="relative lg:col-start-1 lg:row-start-2 lg:min-h-0 lg:overflow-hidden lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface"
                >
                    <div
                        className="rz-scroll lg:h-full lg:overflow-y-auto lg:px-4 lg:pt-0.5 lg:pb-4"
                        inert={report !== null || undefined}
                    >
                        <div
                            role="tablist"
                            aria-label={t('business.reports.tabs')}
                            className="rz-hscroll mt-4 flex gap-2 overflow-x-auto"
                        >
                            {TABS.map((key) => (
                                <button
                                    key={key}
                                    type="button"
                                    role="tab"
                                    aria-selected={tab === key}
                                    onClick={() => setTab(key)}
                                    className={cn(
                                        'shrink-0 rounded-[10px] border px-3.5 py-2 text-[13px] font-semibold whitespace-nowrap',
                                        tab === key
                                            ? 'border-[#cfe9d8] bg-rz-accent-fill text-white dark:border-transparent'
                                            : 'border-rz-border bg-rz-surface text-rz-slate',
                                    )}
                                >
                                    {t(`business.reports.tab.${key}`)}
                                </button>
                            ))}
                        </div>
                        <ul
                            role="tabpanel"
                            aria-label={t(`business.reports.tab.${tab}`)}
                            className="mt-4 flex flex-col gap-3"
                        >
                            {reports[tab].map((row) => {
                                const style = ROW_STYLE[row.status];
                                const tile =
                                    row.status === 'verified' &&
                                    row.health !== 'healthy'
                                        ? ROW_STYLE.in_audit.tile
                                        : style.tile;

                                return (
                                    <li key={row.id}>
                                        <Link
                                            href={row.link}
                                            className="flex w-full items-center gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[15px] text-left"
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-[42px] shrink-0 items-center justify-center rounded-xl text-lg',
                                                    tile,
                                                )}
                                            >
                                                <Icon name={style.icon} />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block text-sm font-semibold text-rz-ink">
                                                    {periodLabel(
                                                        row.period,
                                                        locale,
                                                        t,
                                                    )}
                                                </span>
                                                <span className="mt-0.5 block text-xs text-rz-secondary">
                                                    {detail(row)}
                                                </span>
                                            </span>
                                            <span
                                                className={cn(
                                                    'inline-flex shrink-0 items-center gap-[5px] rounded-[10px] px-[9px] py-1 text-[11px] font-semibold',
                                                    style.pill,
                                                )}
                                            >
                                                {t(
                                                    `business.reports.status.${row.status}`,
                                                )}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                        {reports[tab].length === 0 && (
                            <p className="mt-6 text-center text-[13px] text-rz-secondary">
                                {t('business.reports.empty')}
                            </p>
                        )}
                    </div>
                    {report !== null && (
                        <ReportSheet report={report} close={links.close} />
                    )}
                </div>
            </div>
        </BusinessShell>
    );
}
