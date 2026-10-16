import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { compactRwf } from '@/components/auditor/money';
import {
    AMBER_TEXT,
    EmptyState,
    SectorTile,
    StatTile,
    StatusPill,
    initials,
} from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth, formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    MonthlyReportCard,
    MonthlyReportStatus,
    MonthlyWindow,
} from '@/types/auditor';

/** The design shows three waiting windows before "Show all" (L1973, L3690). */
const WINDOWS_SHOWN = 3;

const STATUS_TONE: Record<MonthlyReportStatus, PillTone> = {
    in_progress: 'amber',
    overdue: 'red',
    changes_requested: 'neutral',
    awaiting_cosign: 'blue',
};

function WindowCard({ window }: { window: MonthlyWindow }) {
    const { t, locale } = useTranslation();
    const [opening, setOpening] = useState(false);

    return (
        <div className="rounded-2xl border border-[#f2dcb0] bg-rz-surface p-3.5 dark:border-[rgba(240,160,96,.3)]">
            <div className="flex items-center gap-[11px]">
                <span
                    aria-hidden
                    className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[14px] font-bold text-rz-ink"
                >
                    {initials(window.business)}
                </span>
                <div className="min-w-0 flex-1">
                    <p className="truncate text-[14px] font-bold text-rz-ink">
                        {window.note_title}
                    </p>
                    <p className="mt-px truncate text-[11.5px] text-rz-secondary">
                        {window.business} · {window.note_id}
                    </p>
                    <p className={cn('mt-px text-[11.5px]', AMBER_TEXT)}>
                        {t('auditor.monthly.not_opened', {
                            month: formatMonthYearLong(window.month, locale),
                        })}
                    </p>
                </div>
            </div>
            <button
                type="button"
                disabled={opening}
                onClick={() =>
                    router.post(
                        window.open.url,
                        {},
                        {
                            onStart: () => setOpening(true),
                            onFinish: () => setOpening(false),
                        },
                    )
                }
                className="mt-[11px] h-10 w-full cursor-pointer rounded-[10px] bg-rz-accent-fill text-[13px] font-bold text-white disabled:cursor-wait disabled:opacity-80"
            >
                {t('auditor.monthly.open')}
            </button>
        </div>
    );
}

function ReportCard({ report }: { report: MonthlyReportCard }) {
    const { t, locale } = useTranslation();

    return (
        <Link
            href={report.link}
            className="block w-full rounded-2xl border border-rz-border bg-rz-surface p-[15px] text-left shadow-[0_8px_22px_-16px_rgba(16,40,90,.3)]"
        >
            <span className="flex items-center gap-[11px]">
                <SectorTile name={report.business} sector={report.sector} />
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-[15px] font-bold text-rz-ink">
                        {report.business}
                    </span>
                    <span className="mt-0.5 block truncate text-[11.5px] text-rz-secondary">
                        {t('auditor.monthly.due_line', {
                            note: report.note_title,
                            date: formatDayMonth(report.due_on, locale),
                        })}
                    </span>
                </span>
                <StatusPill
                    tone={STATUS_TONE[report.status]}
                    className="inline-flex items-center gap-[5px] px-2.5 py-[5px] text-[10.5px]"
                >
                    {t(`auditor.monthly.status.${report.status}`)}
                </StatusPill>
            </span>
            <div className="mt-3 flex gap-2">
                <StatTile
                    label={t('auditor.monthly.inflow')}
                    value={compactRwf(report.inflow)}
                />
                <StatTile
                    label={t('auditor.monthly.outflow')}
                    value={compactRwf(report.outflow)}
                />
                <StatTile
                    label={t('auditor.monthly.cover')}
                    value={report.cover === null ? '—' : `${report.cover}×`}
                    className="border border-[#f2d69a] bg-rz-surface dark:border-[rgba(240,160,96,.3)]"
                    labelClassName={AMBER_TEXT}
                />
            </div>
            <span className="mt-2.5 block text-[11.5px] font-bold text-rz-ink">
                {t('auditor.monthly.review')}
            </span>
        </Link>
    );
}

/**
 * Monthly reports (design L275–318): windows to open for the notes the partner stewards, then
 * reports on the clock. Sealing is due by the 7th; the business co-signs after.
 */
export function MonthlySection({
    windows,
    reports,
}: {
    windows: MonthlyWindow[];
    reports: MonthlyReportCard[];
}) {
    const { t } = useTranslation();
    const [all, setAll] = useState(false);
    const shown = all ? windows : windows.slice(0, WINDOWS_SHOWN);

    return (
        <section>
            <h2 className="mt-[26px] text-[17px] font-bold text-rz-ink lg:mt-0">
                {t('auditor.monthly.title')}
            </h2>
            <p className="mt-[3px] text-[12.5px] leading-[1.45] text-rz-secondary">
                {t('auditor.monthly.lead')}
            </p>
            {windows.length > 0 && (
                <>
                    <div className="mt-3.5 flex items-baseline justify-between gap-2.5">
                        <span className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('auditor.monthly.to_open')}
                        </span>
                        <span className="text-[11px] text-rz-secondary">
                            {t('auditor.monthly.waiting', {
                                count: windows.length,
                            })}
                        </span>
                    </div>
                    <div className="mt-[9px] flex flex-col gap-2.5">
                        {shown.map((window) => (
                            <WindowCard key={window.id} window={window} />
                        ))}
                    </div>
                    {windows.length > WINDOWS_SHOWN && (
                        <button
                            type="button"
                            onClick={() => setAll(!all)}
                            className="mt-2.5 h-[38px] w-full rounded-[10px] border border-rz-border bg-rz-surface text-[12.5px] font-bold text-[#5b6a86] dark:text-rz-secondary"
                        >
                            {all
                                ? t('auditor.monthly.show_fewer', {
                                      shown: WINDOWS_SHOWN,
                                      count: windows.length,
                                  })
                                : t('auditor.monthly.show_all', {
                                      count: windows.length,
                                  })}
                        </button>
                    )}
                </>
            )}
            {reports.length > 0 && (
                <div className="mt-3.5 flex flex-col gap-3">
                    {reports.map((report) => (
                        <ReportCard key={report.id} report={report} />
                    ))}
                </div>
            )}
            {windows.length === 0 && reports.length === 0 && (
                <EmptyState className="py-[26px]">
                    {t('auditor.monthly.empty')}
                </EmptyState>
            )}
        </section>
    );
}
