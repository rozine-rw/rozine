import { Link } from '@inertiajs/react';
import { EmptyState, Eyebrow, StatusPill } from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatDate,
    formatDayMonth,
    formatMonthYearLong,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    AuditorPortfolioProps,
    FiledReport,
    FiledReportStatus,
} from '@/types/auditor';

const STATUS_TONE: Record<FiledReportStatus, PillTone> = {
    awaiting_cosign: 'blue',
    published: 'green',
    late: 'red',
    rejected: 'red',
};

/** The date tile's tint follows the report's state (design L563–566). */
const DATE_TILE: Record<FiledReportStatus, string> = {
    awaiting_cosign:
        'bg-[rgba(30,58,255,.10)] text-[#1e3aff] dark:text-rz-investor-text',
    published: 'bg-[rgba(29,158,117,.10)] text-rz-positive',
    late: 'bg-[rgba(192,57,43,.10)] text-rz-danger-text',
    rejected: 'bg-[rgba(192,57,43,.10)] text-rz-danger-text',
};

const MINI_TILE =
    'min-w-0 flex-1 rounded-[10px] border border-[#eef2f9] bg-[#f8fafc] px-2.5 py-2 dark:border-rz-divider dark:bg-rz-surface-sunken';

function ReportCard({ report }: { report: FiledReport }) {
    const { t, locale } = useTranslation();
    const filed = new Date(report.filed_on);
    const monthAbbr = new Intl.DateTimeFormat(
        locale === 'en' ? 'en-GB' : locale,
        {
            month: 'short',
            timeZone: 'Africa/Kigali',
        },
    )
        .format(filed)
        .replace('.', '')
        .slice(0, 3)
        .toUpperCase();
    const day = new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        timeZone: 'Africa/Kigali',
    }).format(filed);
    const title =
        report.month === null
            ? t('auditor.reports.flash')
            : t('auditor.reports.monthly', {
                  month: formatMonthYearLong(report.month, locale),
              });

    return (
        <li
            className={cn(
                'rounded-2xl border bg-rz-surface px-3.5 py-[13px]',
                report.status === 'rejected'
                    ? 'border-[#f5cfcb] dark:border-[rgba(255,107,111,.3)]'
                    : 'border-rz-border',
            )}
        >
            <Link href={report.link} className="flex items-center gap-[11px]">
                <span
                    className={cn(
                        'flex size-[38px] shrink-0 flex-col items-center justify-center rounded-[10px]',
                        DATE_TILE[report.status],
                    )}
                >
                    <span className="text-[13px] leading-none font-bold">
                        {day}
                    </span>
                    <span className="text-[10px] font-bold opacity-70">
                        {monthAbbr}
                    </span>
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-[13.5px] font-bold text-rz-ink">
                        {report.business}
                    </span>
                    <span className="mt-px block truncate text-[11px] text-rz-secondary">
                        {report.status === 'late' && report.late_days !== null
                            ? t('auditor.reports.late_by', {
                                  title,
                                  days: report.late_days,
                              })
                            : title}
                    </span>
                </span>
                <StatusPill tone={STATUS_TONE[report.status]}>
                    {t(`auditor.reports.status.${report.status}`)}
                </StatusPill>
            </Link>
            <div className="mt-2.5 flex gap-2">
                <div className={MINI_TILE}>
                    <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.reports.district')}
                    </p>
                    <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                        {report.district}
                    </p>
                </div>
                <div className={MINI_TILE}>
                    <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.reports.filed')}
                    </p>
                    <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                        {formatDate(report.filed_on, locale)}
                    </p>
                </div>
                <div className={MINI_TILE}>
                    <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.reports.due')}
                    </p>
                    <p className="mt-0.5 truncate text-[11.5px] font-bold text-rz-ink">
                        {formatDayMonth(report.due_on, locale)}
                    </p>
                </div>
            </div>
            {report.rejection !== null && (
                <div className="mt-2.5 rounded-[10px] border border-[#f5cfcb] px-3 py-2.5 dark:border-[rgba(255,107,111,.3)]">
                    <p className="text-[11px] leading-[1.5] text-[#8a5a55] dark:text-rz-danger-text">
                        {report.rejection.reason}
                    </p>
                    <Link
                        href={report.rejection.amend}
                        className="mt-1.5 inline-block text-[11.5px] font-bold text-rz-ink"
                    >
                        {t('auditor.reports.amend')}
                    </Link>
                </div>
            )}
        </li>
    );
}

/**
 * Everything filed (MVP-AUDITOR-SCR-07): each sealed report with its co-signature state, late and
 * rejected ones called out, and the server's filters. The original of a filed report is immutable;
 * a rejection's next step is a linked amendment.
 */
export function ReportsSection({
    reports,
    filter,
    filters,
}: Pick<AuditorPortfolioProps, 'reports' | 'filter' | 'filters'>) {
    const { t } = useTranslation();
    const all = filters.find((item) => item.key === 'all');
    const total = all?.count ?? reports.length;

    return (
        <section>
            <div className="mt-5 flex items-baseline justify-between lg:mt-0">
                <Eyebrow as="h2">{t('auditor.reports.title')}</Eyebrow>
                <span className="text-[11px] text-rz-secondary">
                    {t('auditor.reports.count', { count: total })}
                </span>
            </div>
            {total > 0 && (
                <nav
                    aria-label={t('auditor.reports.filters')}
                    className="rz-hscroll mt-[11px] flex gap-[7px] overflow-x-auto"
                >
                    {filters.map((item) => {
                        const on = item.key === filter;

                        return (
                            <Link
                                key={item.key}
                                href={item.link}
                                aria-current={on ? 'true' : undefined}
                                className={cn(
                                    'flex shrink-0 items-center gap-1.5 rounded-[20px] border px-[13px] py-[7px] text-[11.5px] font-bold',
                                    on
                                        ? 'border-[#0c1830] bg-[#0c1830] text-white dark:border-rz-accent-fill dark:bg-rz-accent-fill'
                                        : 'border-rz-border bg-rz-surface text-[#5b6a86] dark:text-rz-secondary',
                                )}
                            >
                                {t(`auditor.reports.filter.${item.key}`)}
                                <span className="text-[10px] opacity-65">
                                    {item.count}
                                </span>
                            </Link>
                        );
                    })}
                </nav>
            )}
            {reports.length > 0 ? (
                <ul className="mt-3 flex flex-col gap-[9px]">
                    {reports.map((report) => (
                        <ReportCard key={report.id} report={report} />
                    ))}
                </ul>
            ) : total === 0 ? (
                <EmptyState className="mt-2.5 py-[26px]">
                    {t('auditor.reports.empty')}
                </EmptyState>
            ) : (
                <EmptyState className="mt-3 py-[26px]">
                    {t('auditor.reports.empty_filter')}
                    {all && (
                        <Link
                            href={all.link}
                            className="mt-2 block text-[12px] font-bold text-rz-ink"
                        >
                            {t('auditor.reports.show_all')}
                        </Link>
                    )}
                </EmptyState>
            )}
        </section>
    );
}
