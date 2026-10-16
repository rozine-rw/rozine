import { Link } from '@inertiajs/react';
import { ColumnSheet } from '@/components/rozine/column-sheet';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatCount,
    formatDate,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { ReportDetail, ReportFigure } from '@/types/business';
import { periodLabel } from './period-label';

const HEALTH_TONE = {
    healthy: 'text-rz-accent-app-text',
    watch: 'text-rz-ink',
    at_risk: 'text-rz-danger-text',
} as const;

/**
 * The month's report exactly as investors see it (design L1897–1939): it opens over the report
 * list, 88% of that column on a wide screen.
 */
export function ReportSheet({
    report,
    close,
}: {
    report: ReportDetail;
    close: RouteLink;
}) {
    const { t, locale } = useTranslation();
    const month = periodLabel(report.period, locale, t);
    const figure = (line: ReportFigure): string => {
        switch (line.key) {
            case 'net_margin':
                return t('business.reports.percent', { value: line.percent });
            case 'days_cash_on_hand':
                return formatCount(line.count);
            case 'quarters_above_floor':
                return t('business.reports.count_of', {
                    count: line.count,
                    of: line.of,
                });
            default:
                return formatRwf(line.value);
        }
    };

    return (
        <ColumnSheet
            label={month}
            close={close}
            fraction={0.88}
            phoneFraction={0.88}
        >
            <div className="flex shrink-0 items-start justify-between gap-3 border-b border-[#eef2f9] px-[18px] pt-1 pb-3 dark:border-rz-divider">
                <div className="min-w-0">
                    <p
                        className={cn(
                            'text-[10px] font-bold tracking-[.06em] uppercase',
                            report.archived
                                ? 'text-rz-secondary'
                                : 'text-rz-accent-app-text',
                        )}
                    >
                        {t(
                            report.archived
                                ? 'business.reports.sheet.archived'
                                : 'business.reports.sheet.live',
                        )}
                    </p>
                    <h2 className="mt-[3px] text-lg font-bold tracking-[-.3px] text-rz-ink">
                        {month}
                    </h2>
                    <p className="mt-0.5 text-xs text-rz-secondary">
                        {report.seen_by === null
                            ? t('business.reports.sheet.filed', {
                                  date: formatDate(report.published_on, locale),
                              })
                            : t('business.reports.sheet.published', {
                                  date: formatDate(report.published_on, locale),
                                  count: formatCount(report.seen_by),
                              })}
                    </p>
                </div>
                <Link
                    href={close}
                    aria-label={t('app.sheet.close')}
                    className="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#f2f4f8] text-base leading-none text-rz-secondary dark:bg-rz-page"
                >
                    <span aria-hidden>×</span>
                </Link>
            </div>
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-3.5 pb-[18px]">
                <dl className="grid grid-cols-3 gap-2">
                    {(
                        [
                            [
                                'inflow',
                                formatRwfShort(report.inflow),
                                'text-rz-ink',
                            ],
                            [
                                'outflow',
                                formatRwfShort(report.outflow),
                                'text-rz-ink',
                            ],
                            [
                                'health',
                                t(`business.reports.health.${report.health}`),
                                HEALTH_TONE[report.health],
                            ],
                        ] as const
                    ).map(([key, value, tone]) => (
                        <div
                            key={key}
                            className="min-w-0 rounded-xl border border-[#eef2f9] bg-[#f8fafd] px-[11px] py-2.5 dark:border-rz-border dark:bg-rz-page"
                        >
                            <dt className="text-[10.5px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase">
                                {t(`business.reports.sheet.${key}`)}
                            </dt>
                            <dd
                                className={cn(
                                    'mt-[3px] text-[15px] font-bold tracking-[-.2px]',
                                    tone,
                                )}
                            >
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>
                <h3 className="mt-3.5 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('business.reports.sheet.recap')}
                </h3>
                <p className="mt-[7px] text-[13px] leading-[1.6] text-rz-slate">
                    {report.recap}
                </p>
                <h3 className="mt-3.5 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('business.reports.sheet.figures')}
                </h3>
                <dl className="mt-[7px] rounded-xl border border-rz-border bg-rz-surface px-[13px] py-0.5">
                    {report.figures.map((line) => (
                        <div
                            key={line.key}
                            className="flex items-baseline justify-between gap-3 border-b border-[#f1f4f9] py-[9px] last:border-b-0 dark:border-rz-divider"
                        >
                            <dt className="text-[12.5px] text-rz-secondary">
                                {line.key === 'quarters_above_floor'
                                    ? t(
                                          'business.reports.figure.quarters_above_floor',
                                          { floor: line.floor_percent },
                                      )
                                    : t(`business.reports.figure.${line.key}`)}
                            </dt>
                            <dd className="text-[13px] font-semibold whitespace-nowrap text-rz-ink">
                                {figure(line)}
                            </dd>
                        </div>
                    ))}
                </dl>
                <div className="mt-3.5 flex items-start gap-2.5 rounded-xl border border-[#cfe9d8] bg-[#f0f9f3] px-[13px] py-3 dark:border-transparent dark:bg-rz-accent-soft">
                    <span className="flex size-[30px] shrink-0 items-center justify-center rounded-full bg-rz-accent-soft text-xs font-bold text-rz-accent-app-text">
                        {report.auditor.initials}
                    </span>
                    <div className="min-w-0">
                        <p className="text-[12.5px] font-semibold text-rz-ink">
                            {t('business.reports.sheet.audited_by', {
                                name: report.auditor.name,
                            })}
                        </p>
                        <p className="mt-0.5 text-[11.5px] leading-normal text-[#3a7a55] dark:text-rz-accent-app-text">
                            {report.auditor.note}
                        </p>
                    </div>
                </div>
                <p className="mt-3 text-[11.5px] leading-[1.55] text-rz-secondary">
                    {t(
                        report.archived
                            ? 'business.reports.sheet.disclosure_archived'
                            : 'business.reports.sheet.disclosure_live',
                    )}
                </p>
            </div>
        </ColumnSheet>
    );
}
