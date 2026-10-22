import { useState } from 'react';
import { Tile } from '@/components/business/note/progress-summary';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { CampaignInvestors as Investors } from '@/types/business';

/** How many holders show before "View all". */
const FIRST = 5;

const KIND_TONE = {
    person: { bar: 'bg-[#1e3aff]', avatar: 'bg-[#1e3aff]' },
    organization: { bar: 'bg-rz-ink', avatar: 'bg-[#0c1830]' },
} as const;

/**
 * A funded raise's holders (design L693–803): totals, the holder mix by capital and every holding,
 * largest first. The server names nobody, so a holder reads by kind and amount only; there is
 * nothing to search, so the design's search box is not drawn.
 */
export function CampaignInvestors({ investors }: { investors: Investors }) {
    const { t } = useTranslation();
    const [all, setAll] = useState(false);
    const shown = all ? investors.list : investors.list.slice(0, FIRST);

    return (
        <section aria-labelledby="campaign-investors" className="mt-5">
            <div className="flex items-center justify-between">
                <h2
                    id="campaign-investors"
                    className="text-base font-semibold text-rz-ink"
                >
                    {t('business.campaign.investors.title')}
                </h2>
                {investors.list.length > FIRST && (
                    <button
                        type="button"
                        onClick={() => setAll(!all)}
                        className="text-[12.5px] font-semibold text-rz-accent-app-text"
                    >
                        {all
                            ? t('business.campaign.investors.fewer')
                            : t('business.campaign.investors.all', {
                                  count: investors.holders,
                              })}
                    </button>
                )}
            </div>
            <div className="mt-[11px] grid grid-cols-3 gap-[9px]">
                <Tile
                    label={t('business.campaign.investors.raised')}
                    value={formatRwfShort(investors.raised)}
                />
                <Tile
                    label={t('business.campaign.investors.holders')}
                    value={String(investors.holders)}
                />
                <Tile
                    label={t('business.campaign.investors.average')}
                    value={formatRwfShort(investors.average)}
                />
            </div>
            <div className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface p-3.5">
                <p className="flex justify-between text-[11px] font-semibold text-rz-secondary uppercase">
                    <span>{t('business.campaign.investors.mix')}</span>
                    <span>{t('business.campaign.investors.by_capital')}</span>
                </p>
                <div className="mt-2 flex h-2 overflow-hidden rounded-[4px] bg-[#eef2f8] dark:bg-rz-page">
                    {investors.mix.map((part) => (
                        <div
                            key={part.kind}
                            className={KIND_TONE[part.kind].bar}
                            style={{ width: `${part.percent}%` }}
                        />
                    ))}
                </div>
                <ul className="mt-2 flex flex-col gap-1">
                    {investors.mix.map((part) => (
                        <li
                            key={part.kind}
                            className="flex items-center gap-2 text-xs text-rz-secondary"
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'size-2 rounded-full',
                                    KIND_TONE[part.kind].bar,
                                )}
                            />
                            <span className="flex-1 text-rz-ink">
                                {t(
                                    `business.campaign.investors.kinds.${part.kind}`,
                                )}
                            </span>
                            <span>{part.holders}</span>
                            <span className="w-10 text-right font-semibold text-rz-ink">
                                {t('business.reports.percent', {
                                    value: part.percent,
                                })}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
            <ul className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface px-3.5">
                {shown.map((holder, index) => (
                    <li
                        key={index}
                        className="flex items-center gap-3 border-b border-[#eef2f9] py-2.5 last:border-b-0 dark:border-rz-divider"
                    >
                        <span
                            aria-hidden
                            className={cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white',
                                KIND_TONE[holder.kind].avatar,
                            )}
                        >
                            {index + 1}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[13px] font-semibold text-rz-ink">
                                {t(
                                    `business.campaign.investors.kind.${holder.kind}`,
                                )}
                            </p>
                            <p className="text-[11.5px] text-rz-secondary">
                                {t('business.campaign.investors.notes', {
                                    count: Number(holder.units),
                                })}
                            </p>
                        </div>
                        <p className="shrink-0 text-[13px] font-semibold text-rz-ink">
                            {formatRwf(holder.principal)}
                        </p>
                    </li>
                ))}
            </ul>
            <p className="mt-2 text-[11px] text-rz-secondary">
                {t('business.campaign.investors.privacy')}
            </p>
        </section>
    );
}
