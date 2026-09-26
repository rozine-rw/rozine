import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { HoldingCard } from '@/components/investor/portfolio/holding-card';
import {
    Exposure,
    PayoutChart,
    TotalCard,
} from '@/components/investor/portfolio/portfolio-insights';
import { AwaitingIssue } from '@/components/investor/primary/commitment';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import { cn } from '@/lib/utils';
import type { C3InvestorPortfolioProps } from '@/types/investor';

/** The design lists three holdings on a phone and four on a wide screen before "See all". */
const FIRST = { phone: 3, desk: 4 } as const;

/**
 * Portfolio (MVP-INVESTOR-SCR-04, design L1062–1303): what the investor holds, its value and gain,
 * scheduled payouts, and concentration by business, industry and rating. Commitments still awaiting
 * issue are listed apart, above the holdings, and never counted among them (C3 v2 §2d). The
 * design's Secondary and Saved tabs (resale and the watchlist) and its Rozine Plus charge card are
 * outside the MVP.
 */
export default function InvestorPortfolio(props: C3InvestorPortfolioProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const [all, setAll] = useState(false);
    const first = wide ? FIRST.desk : FIRST.phone;
    const shown = all ? props.holdings : props.holdings.slice(0, first);
    const more = props.holdings.length - first;

    const list = (
        <>
            <div
                className={cn(
                    wide
                        ? 'px-[18px] pt-4 pb-1.5'
                        : 'px-5 pt-[calc(env(safe-area-inset-top)+14px)]',
                )}
            >
                <h1 className="text-2xl font-semibold text-rz-ink">
                    {t('investor.portfolio.title')}
                </h1>
            </div>
            {!wide && (
                <div className="mt-4 px-5">
                    <TotalCard totals={props.totals} />
                </div>
            )}
            <nav
                aria-label={t('investor.portfolio.tabs')}
                className={cn(
                    'flex gap-[7px]',
                    wide ? 'px-4 pb-2.5' : 'mt-4 px-5',
                )}
            >
                {props.tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={tab.link}
                        preserveScroll
                        aria-current={tab.active ? 'page' : undefined}
                        className={cn(
                            'min-w-0 flex-1 rounded-[10px] border px-1 py-2 text-center text-xs font-semibold whitespace-nowrap',
                            tab.active
                                ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                : 'border-rz-border bg-rz-surface text-rz-slate',
                        )}
                    >
                        {t(`investor.portfolio.tab.${tab.key}`)}
                    </Link>
                ))}
            </nav>
            <div
                className={cn(
                    'flex flex-col gap-2.5',
                    wide ? 'px-4 pb-4' : 'px-5 pt-4',
                )}
            >
                {props.tab === 'active' && (
                    <AwaitingIssue commitments={props.commitments} />
                )}
                {shown.map((holding) => (
                    <HoldingCard key={holding.id} holding={holding} />
                ))}
                {more > 0 && !all && (
                    <div className="flex justify-end">
                        <button
                            type="button"
                            onClick={() => setAll(true)}
                            className="inline-flex items-center gap-[3px] px-0.5 py-0.5 text-[12.5px] font-bold text-rz-accent-app-text"
                        >
                            {t('investor.portfolio.see_all', { count: more })}
                        </button>
                    </div>
                )}
                {props.holdings.length === 0 && (
                    <div className="px-5 py-[46px] text-center">
                        <span className="text-[31px]">
                            <Icon name="inbox-empty" />
                        </span>
                        <p className="mt-3 text-[15px] font-semibold text-rz-ink">
                            {t(`investor.portfolio.empty.${props.tab}.title`)}
                        </p>
                        <p className="mt-1.5 text-[13px] leading-normal text-rz-secondary">
                            {t(`investor.portfolio.empty.${props.tab}.body`)}
                        </p>
                        {props.tab === 'active' && (
                            <Link
                                href={props.links.deals}
                                className="mt-4 inline-block rounded-xl bg-rz-accent-fill px-5 py-[11px] text-[13.5px] font-semibold text-white"
                            >
                                {t('investor.portfolio.browse_deals')}
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </>
    );

    const insights = (
        <div className={wide ? 'px-4 pt-3.5 pb-[18px]' : 'px-5 pt-6'}>
            <PayoutChart payouts={props.payouts} />
            <Exposure
                industries={props.industries}
                risk={props.risk}
                concentration={props.concentration}
                idle={props.idle}
                deals={props.links.deals}
            />
            <div className="h-5" />
        </div>
    );

    return (
        <InvestorShell
            title={t('investor.portfolio.title')}
            tab="portfolio"
            links={props.links}
        >
            {wide ? (
                <div className="h-full px-[30px] pt-3 pb-3.5">
                    <div className="flex h-full min-w-0 flex-row-reverse items-stretch gap-4 px-5 py-4">
                        <div className="rz-scroll flex min-h-0 min-w-[300px] flex-auto flex-col overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface">
                            {list}
                        </div>
                        <div className="rz-scroll flex min-h-0 min-w-[296px] flex-[0_1_384px] flex-col overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface">
                            <div className="px-4 pt-4 pb-1.5">
                                <TotalCard totals={props.totals} />
                            </div>
                            {insights}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="pb-[calc(63px+env(safe-area-inset-bottom)+16px)]">
                    {list}
                    {insights}
                </div>
            )}
        </InvestorShell>
    );
}
