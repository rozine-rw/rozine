import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatMonthShort,
    formatMonthYear,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { NotePerformanceRange } from '@/types/business';

type Range = 'six_months' | 'twelve_months';

/**
 * "Performance trend" (design L675–694): audited monthly revenue, green when that month's payment
 * was on time and ink when it was late. Bars span the period's low to high, as the design's do.
 */
export function PerformanceTrend({
    ranges,
}: {
    ranges: Record<Range, NotePerformanceRange>;
}) {
    const { t, locale } = useTranslation();
    const [range, setRange] = useState<Range>('six_months');
    const [selected, setSelected] = useState<number | null>(null);
    const { months, high, low } = ranges[range];
    const floor = Number(low.amount);
    const spread = Number(high.amount) - floor || 1;
    const chosen = selected === null ? null : months[selected];

    return (
        <>
            <h2 className="mt-[18px] text-base font-semibold text-rz-ink">
                {t('business.note.trend.title')}
            </h2>
            <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <span className="text-[11px] font-semibold text-rz-secondary">
                        {t('business.note.trend.caption')}
                    </span>
                    <div
                        role="group"
                        aria-label={t('business.note.trend.range')}
                        className="flex gap-1 rounded-[10px] border border-rz-border bg-[#f3f6fc] p-[3px] dark:bg-rz-page"
                    >
                        {(['six_months', 'twelve_months'] as const).map(
                            (key) => (
                                <button
                                    key={key}
                                    type="button"
                                    aria-pressed={range === key}
                                    onClick={() => {
                                        setRange(key);
                                        setSelected(null);
                                    }}
                                    className={cn(
                                        'rounded-[10px] px-[11px] py-1 text-[11px] font-semibold',
                                        range === key
                                            ? 'bg-rz-accent-fill text-white'
                                            : 'bg-rz-surface text-rz-slate',
                                    )}
                                >
                                    {t(`business.note.trend.${key}`)}
                                </button>
                            ),
                        )}
                    </div>
                </div>
                <div className="flex h-[110px] items-end gap-[7px]">
                    {months.map((month, index) => {
                        const on = selected === index;

                        return (
                            <button
                                key={month.month}
                                type="button"
                                aria-pressed={on}
                                aria-label={formatMonthYear(
                                    month.month,
                                    locale,
                                )}
                                onClick={() => setSelected(on ? null : index)}
                                className="flex h-full flex-1 flex-col items-center justify-end gap-1.5"
                            >
                                <span
                                    className={cn(
                                        'w-full rounded-[5px_5px_0_0]',
                                        selected === null &&
                                            (month.paid_on_time
                                                ? 'bg-rz-accent-fill'
                                                : 'bg-rz-ink'),
                                        selected !== null &&
                                            (on
                                                ? 'bg-rz-ink'
                                                : 'bg-rz-secondary opacity-50'),
                                    )}
                                    style={{
                                        height: `${Math.round(30 + ((Number(month.revenue.amount) - floor) / spread) * 70)}%`,
                                    }}
                                />
                                <span
                                    aria-hidden
                                    className="text-[10.5px] text-rz-secondary"
                                >
                                    {formatMonthShort(month.month, locale)}
                                </span>
                            </button>
                        );
                    })}
                </div>
                <p
                    aria-live="polite"
                    className="mt-2.5 text-center text-[11.5px] text-rz-secondary"
                >
                    {chosen === null
                        ? t('business.note.trend.hint')
                        : t(
                              chosen.paid_on_time
                                  ? 'business.note.trend.month_on_time'
                                  : 'business.note.trend.month_late',
                              {
                                  month: formatMonthYear(chosen.month, locale),
                                  revenue: formatRwfShort(chosen.revenue),
                              },
                          )}
                </p>
                <div className="mt-[11px] flex gap-3.5 border-t border-[#eef2f9] pt-[11px] dark:border-rz-divider">
                    <div className="flex-1">
                        <p className="text-[10px] font-semibold text-rz-secondary uppercase">
                            {t('business.note.trend.high')}
                        </p>
                        <p className="mt-0.5 text-[13px] font-semibold text-rz-accent-app-text">
                            {formatRwfShort(high)}
                        </p>
                    </div>
                    <div className="flex-1">
                        <p className="text-[10px] font-semibold text-rz-secondary uppercase">
                            {t('business.note.trend.low')}
                        </p>
                        <p className="mt-0.5 text-[13px] font-semibold text-rz-danger-text">
                            {formatRwfShort(low)}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
