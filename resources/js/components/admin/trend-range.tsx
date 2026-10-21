import { router } from '@inertiajs/react';
import { useState } from 'react';
import { CardTitle } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import type { TrendWindow } from '@/types/admin';

const DATE_INPUT =
    'rounded-lg border border-rz-hairline bg-rz-surface px-[7px] py-[5px] text-[11px] text-rz-ink uppercase outline-none';

/**
 * A chart panel's title row: its caption names the window and how the bars are grouped, and the
 * From and To dates reload only `only` with the chosen window (Clear returns to the last fourteen
 * days).
 */
export function TrendRange({
    title,
    trend,
    only,
}: {
    title: string;
    trend: TrendWindow;
    only: string[];
}) {
    const { t, locale } = useTranslation();
    const chosen = trend.to !== null;
    const [range, setRange] = useState({
        from: chosen ? trend.from.slice(0, 10) : '',
        to: trend.to?.slice(0, 10) ?? '',
    });

    const reload = (next: typeof range) => {
        setRange(next);
        router.reload({
            data: { from: next.from || null, to: next.to || null },
            only,
        });
    };

    const label =
        range.from === '' && range.to === ''
            ? t('admin.primary.trend.last_14')
            : t('admin.today.capital.range', {
                  from: formatDate(trend.from, locale),
                  to: trend.to === null ? '…' : formatDate(trend.to, locale),
              });

    return (
        <div className="flex flex-wrap items-center justify-between gap-2.5">
            <div>
                <CardTitle>{title}</CardTitle>
                <p className="mt-[3px] text-[12px] text-rz-muted">
                    {t('admin.today.capital.caption', {
                        range: label,
                        grain: t(`admin.today.capital.grain.${trend.grain}`),
                    })}
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
                {(['from', 'to'] as const).map((end) => (
                    <label key={end} className="flex items-center gap-[5px]">
                        <span className="text-[10px] font-semibold text-[#636e86] uppercase dark:text-rz-muted">
                            {t(`admin.today.capital.${end}`)}
                        </span>
                        <input
                            type="date"
                            aria-label={t(`admin.today.capital.${end}_date`)}
                            value={range[end]}
                            onChange={(event) =>
                                reload({ ...range, [end]: event.target.value })
                            }
                            className={DATE_INPUT}
                        />
                    </label>
                ))}
                {(range.from !== '' || range.to !== '') && (
                    <button
                        type="button"
                        onClick={() => reload({ from: '', to: '' })}
                        className="rounded-lg border border-[#d6e4ff] bg-[#eef3fb] px-2.5 py-[5px] text-[11px] font-semibold text-rz-accent-app-text dark:border-rz-border dark:bg-rz-surface-muted"
                    >
                        {t('admin.today.capital.clear')}
                    </button>
                )}
            </div>
        </div>
    );
}
