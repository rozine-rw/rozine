import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessMarketProps } from '@/types/business';

const TILES = ['demand', 'price', 'volume', 'liquidity'] as const;
const RANGES = ['7d', '14d', '30d'] as const;

const COLUMN =
    'lg:min-h-0 lg:min-w-0 lg:flex-[0_0_calc(50%-8px)] lg:overflow-y-auto lg:rounded-2xl lg:border lg:border-rz-border lg:bg-rz-surface';

/**
 * Market (design L1263–1308): how the business's notes trade on the secondary market, with the
 * design's four figures, the secondary price chart and the notes on the market. Secondary trading
 * has no read yet, so every figure is a dash, the chart and the list show their empty state, and
 * the range switch only changes the view.
 */
export default function BusinessMarket({ links }: BusinessMarketProps) {
    const { t } = useTranslation();
    const [range, setRange] = useState<(typeof RANGES)[number]>('7d');

    return (
        <BusinessShell
            title={t('business.market.title')}
            tab="market"
            links={links}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+4px)] pb-[92px] lg:flex lg:h-full lg:gap-4 lg:px-5 lg:pt-4 lg:pb-5">
                <section
                    data-rzcol
                    aria-labelledby="market-title"
                    className={cn(COLUMN, 'rz-scroll lg:p-[18px]')}
                >
                    <h1
                        id="market-title"
                        className="text-2xl font-semibold text-rz-ink"
                    >
                        {t('business.market.title')}
                    </h1>
                    <p className="mt-1 text-[13.5px] text-rz-secondary">
                        {t('business.market.subtitle')}
                    </p>
                    <dl className="mt-4 grid grid-cols-2 gap-[11px]">
                        {TILES.map((key) => (
                            <div
                                key={key}
                                className="rounded-2xl border border-rz-border bg-rz-surface p-3.5"
                            >
                                <dt className="text-[11px] font-semibold text-rz-secondary">
                                    {t(`business.market.tile.${key}`)}
                                </dt>
                                <dd className="mt-[3px] text-[17px] font-semibold text-rz-ink">
                                    —
                                </dd>
                            </div>
                        ))}
                    </dl>
                    <div className="mt-[18px] flex items-center justify-between gap-2">
                        <h2 className="text-base font-semibold text-rz-ink">
                            {t('business.market.chart')}
                        </h2>
                        <div
                            role="radiogroup"
                            aria-label={t('business.market.range')}
                            className="flex gap-1 rounded-[10px] border border-rz-border bg-rz-page p-[3px]"
                        >
                            {RANGES.map((key) => (
                                <button
                                    key={key}
                                    type="button"
                                    role="radio"
                                    aria-checked={range === key}
                                    onClick={() => setRange(key)}
                                    className={cn(
                                        'rounded-[10px] px-2.5 py-1 text-[11px] font-semibold',
                                        range === key
                                            ? 'bg-rz-ink text-white dark:bg-rz-accent-fill'
                                            : 'text-rz-slate',
                                    )}
                                >
                                    {t(`business.market.range_${key}`)}
                                </button>
                            ))}
                        </div>
                    </div>
                    <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-4">
                        <div className="flex h-[120px] flex-col items-center justify-center text-center">
                            <span
                                aria-hidden
                                className="flex size-10 items-center justify-center rounded-xl bg-rz-accent-soft text-lg"
                            >
                                <Icon name="trend-up" />
                            </span>
                            <p className="mt-2.5 text-[13px] text-rz-secondary">
                                {t('business.market.chart_empty')}
                            </p>
                        </div>
                        <dl className="mt-[11px] flex gap-3.5 border-t border-[#eef2f9] pt-[11px] dark:border-rz-divider">
                            {(['high', 'low'] as const).map((key) => (
                                <div key={key} className="flex-1">
                                    <dt className="text-[10px] font-semibold text-rz-secondary">
                                        {t(`business.market.${key}`)}
                                    </dt>
                                    <dd className="mt-0.5 text-[13px] font-semibold text-rz-ink">
                                        —
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>
                <section
                    data-rzcol
                    aria-labelledby="market-notes-title"
                    className={cn(COLUMN, 'rz-scroll mt-6 lg:mt-0 lg:p-[18px]')}
                >
                    <h2
                        id="market-notes-title"
                        className="text-base font-semibold text-rz-ink lg:mt-0.5"
                    >
                        {t('business.market.notes')}
                    </h2>
                    <div className="mt-3 flex flex-col items-center rounded-2xl border border-dashed border-[#dbe3f0] px-4 py-10 text-center dark:border-rz-border">
                        <span
                            aria-hidden
                            className="flex size-[72px] items-center justify-center rounded-[20px] bg-rz-accent-soft text-[26px]"
                        >
                            <Icon name="folders" />
                        </span>
                        <p className="mt-4 text-[15px] font-semibold text-rz-ink">
                            {t('business.market.notes_empty_title')}
                        </p>
                        <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-rz-secondary">
                            {t('business.market.notes_empty_body')}
                        </p>
                    </div>
                </section>
            </div>
        </BusinessShell>
    );
}
