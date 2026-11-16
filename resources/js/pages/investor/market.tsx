import { useState } from 'react';
import { InvestorShell } from '@/components/investor/investor-shell';
import { FilterPill } from '@/components/investor/market/filter-pill';
import {
    BuySheet,
    ListingCard,
    SellSheet,
    TradeRow,
} from '@/components/investor/market/resale';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { useWide } from '@/lib/investor/use-wide';
import { cn } from '@/lib/utils';
import type { InvestorMarketProps, MarketListing } from '@/types/investor';

const VIEWS = ['browse', 'orders', 'saved'] as const;
const PERFORMANCE = [
    'all',
    'most_active',
    'highest_yield',
    'lowest_risk',
    'most_liquid',
] as const;
const STATUS = ['all', 'active', 'sold', 'complete'] as const;
const INDUSTRY = [
    'all',
    'agriculture',
    'manufacturing',
    'energy',
    'technology',
    'logistics',
    'finance',
] as const;
const ORDERS = ['buy', 'sell', 'completed', 'cancelled'] as const;

type View = (typeof VIEWS)[number];

/**
 * Market (MVP-INVESTOR-SCR-06, design L1694–1830): trading active Rozine Notes before maturity, with
 * the design's Browse, Orders and Saved views. Browse lists the live resale listings (BRS v3.0 §12),
 * each bought by reserving units and then confirming the purchase; Sell lists units of a held lot
 * at a price no higher than the note's cap. Orders shows the Investor's own reservations, listings
 * and settled trades. With nothing listed, or trading closed, each view shows the design's empty
 * state (SCR-06-ST-01 "empty book"). Saved has no watchlist read yet.
 */
export default function InvestorMarket({
    links,
    market,
    actions,
    identity_context_revision: identityRevision,
}: InvestorMarketProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const [view, setView] = useState<View>('browse');
    const [performance, setPerformance] =
        useState<(typeof PERFORMANCE)[number]>('all');
    const [status, setStatus] = useState<(typeof STATUS)[number]>('all');
    const [industry, setIndustry] = useState<(typeof INDUSTRY)[number]>('all');
    const [orders, setOrders] = useState<(typeof ORDERS)[number]>('buy');
    const [buying, setBuying] = useState<MarketListing | null>(null);
    const [selling, setSelling] = useState(false);
    /** Every live listing is active; the board carries no performance or industry facts to narrow by. */
    const filtered =
        performance !== 'all' ||
        (status !== 'all' && status !== 'active') ||
        industry !== 'all';
    const browsing = filtered ? [] : market.listings;
    const ordered =
        orders === 'buy'
            ? market.listings.filter((listing) => listing.reservation !== null)
            : orders === 'sell'
              ? market.listings.filter((listing) => listing.mine)
              : [];
    const card = (listing: MarketListing) => (
        <ListingCard
            key={listing.listing_id}
            listing={listing}
            identityRevision={identityRevision}
            onBuy={setBuying}
        />
    );
    const empty =
        view === 'saved' ? 'saved' : filtered ? 'browse' : 'browse_none';
    const performanceOptions = PERFORMANCE.map((value) => ({
        value,
        label: t(`investor.market.performance.${value}`),
    }));
    const statusOptions = STATUS.map((value) => ({
        value,
        label: t(`investor.market.status.${value}`),
    }));
    const industryOptions = INDUSTRY.map((value) => ({
        value,
        label: t(`investor.market.industry.${value}`),
    }));
    const orderOptions = ORDERS.map((value) => ({
        value,
        label: t(`investor.market.orders.${value}`),
    }));

    const tabs = (
        <div
            role="tablist"
            aria-label={t('investor.market.title')}
            className={cn(
                'flex gap-1.5 rounded-xl border border-rz-hairline bg-[#f3f6fc] p-1',
                wide ? 'mt-2.5' : 'mt-4',
            )}
        >
            {VIEWS.map((key) => (
                <button
                    key={key}
                    type="button"
                    role="tab"
                    aria-selected={view === key}
                    onClick={() => setView(key)}
                    className={cn(
                        'flex-1 rounded-[10px] font-semibold',
                        wide ? 'py-1.5 text-xs' : 'py-2 text-[13px]',
                        view === key
                            ? 'bg-rz-accent-fill text-white'
                            : 'text-rz-secondary',
                    )}
                >
                    {t(`investor.market.view.${key}`)}
                </button>
            ))}
        </div>
    );
    const header = (
        <div
            className={
                wide ? '' : 'px-5 pt-[calc(env(safe-area-inset-top)+56px)]'
            }
        >
            <h1
                className={cn(
                    'text-rz-ink',
                    wide ? 'text-[19px] font-bold' : 'text-2xl font-semibold',
                )}
            >
                {t('investor.market.title')}
            </h1>
            {!wide && (
                <p className="mt-[3px] text-[13.5px] text-rz-secondary">
                    {t('investor.market.subtitle')}
                </p>
            )}
            {actions.list !== null && (
                <button
                    type="button"
                    onClick={() => setSelling(true)}
                    className="mt-2.5 h-9 rounded-[10px] border border-rz-border bg-rz-surface px-3.5 text-[12.5px] font-semibold text-rz-ink"
                >
                    {t('investor.market.resale.sell')}
                </button>
            )}
            {tabs}
        </div>
    );
    const noOrders = (
        <div className="py-10 text-center">
            <span className="text-[28px]">
                <Icon name="folder" tone="red" />
            </span>
            <p className="mt-2.5 text-sm text-rz-secondary">
                {t('investor.market.orders.empty')}
            </p>
        </div>
    );
    const orderList =
        orders === 'completed' && market.trades.length > 0 ? (
            <ul aria-label={t('investor.market.orders.completed')}>
                {market.trades.map((trade) => (
                    <TradeRow key={trade.transfer_id} trade={trade} />
                ))}
            </ul>
        ) : ordered.length > 0 ? (
            <ul
                aria-label={t(`investor.market.orders.${orders}`)}
                className="mt-3 flex flex-col gap-2.5"
            >
                {ordered.map(card)}
            </ul>
        ) : (
            noOrders
        );
    const body =
        view === 'orders' ? (
            <>
                <div className={wide ? 'px-1 pt-3' : 'px-5 pt-3.5'}>
                    <FilterPill
                        label={t('investor.market.orders.label')}
                        value={orders}
                        options={orderOptions}
                        onChange={setOrders}
                        className="w-[220px] max-w-[70%]"
                    />
                </div>
                {wide ? (
                    <div className="mx-1 mt-3 mb-1 min-h-0 flex-1 overflow-y-auto rounded-2xl border border-rz-hairline bg-rz-surface p-3.5 shadow-[0_10px_30px_-22px_rgba(20,45,95,.28)]">
                        {orderList}
                    </div>
                ) : (
                    <div className="px-5 pb-6">{orderList}</div>
                )}
            </>
        ) : (
            <>
                <div
                    className={cn(
                        'flex gap-2',
                        wide ? 'pt-2.5' : 'px-5 pt-3.5',
                    )}
                >
                    <FilterPill
                        label={t('investor.market.filter.performance')}
                        value={performance}
                        options={performanceOptions}
                        onChange={setPerformance}
                        neutral="all"
                        className="flex-1"
                    />
                    <FilterPill
                        label={t('investor.market.filter.status')}
                        value={status}
                        options={statusOptions}
                        onChange={setStatus}
                        neutral="all"
                        className="flex-1"
                    />
                    <FilterPill
                        label={t('investor.market.filter.industry')}
                        value={industry}
                        options={industryOptions}
                        onChange={setIndustry}
                        neutral="all"
                        className="flex-1"
                    />
                </div>
                {view === 'browse' && browsing.length > 0 ? (
                    <ul
                        aria-label={t('investor.market.resale.listings')}
                        className={cn(
                            'mt-3 flex flex-col gap-2.5 overflow-y-auto',
                            wide ? 'min-h-0 flex-1 px-1 pb-2' : 'px-5 pb-6',
                        )}
                    >
                        {browsing.map(card)}
                    </ul>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center px-[22px] py-16 text-center">
                        <span
                            aria-hidden
                            className="flex size-[72px] items-center justify-center rounded-[20px] bg-[rgba(194,102,31,.10)] text-[26px]"
                        >
                            {view === 'saved' ? '🔖' : '🗂'}
                        </span>
                        <p className="mt-4 text-[17px] font-semibold text-rz-ink">
                            {t(`investor.market.${empty}.empty_title`)}
                        </p>
                        <p className="mt-1.5 max-w-[240px] text-[13px] leading-normal text-rz-secondary">
                            {t(`investor.market.${empty}.empty_body`)}
                        </p>
                    </div>
                )}
            </>
        );
    const sheets = (
        <>
            {buying !== null && (
                <BuySheet
                    listing={buying}
                    identityRevision={identityRevision}
                    onClose={() => setBuying(null)}
                />
            )}
            {selling && actions.list !== null && (
                <SellSheet
                    lots={market.sellable}
                    action={actions.list}
                    identityRevision={identityRevision}
                    onClose={() => setSelling(false)}
                />
            )}
        </>
    );

    return (
        <InvestorShell
            title={t('investor.market.title')}
            tab="market"
            links={links}
        >
            {wide ? (
                <div className="grid h-full grid-cols-[minmax(0,1fr)_300px] gap-[18px] px-[18px] py-4">
                    <div className="relative flex min-h-0 min-w-0 flex-col">
                        {header}
                        {body}
                        {sheets}
                    </div>
                    <aside
                        aria-label={t('investor.market.order_detail.title')}
                        className={cn(
                            view === 'orders' &&
                                'rounded-2xl border border-rz-hairline bg-rz-surface p-4',
                        )}
                    >
                        {view === 'orders' && (
                            <>
                                <p className="text-[10px] font-bold tracking-[.05em] text-rz-body uppercase">
                                    {t('investor.market.order_detail.title')}
                                </p>
                                <div className="mt-[60px] px-[18px] text-center">
                                    <p className="text-[13.5px] font-semibold text-rz-ink">
                                        {t(
                                            'investor.market.order_detail.empty_title',
                                        )}
                                    </p>
                                    <p className="mt-1.5 text-xs leading-[1.55] text-rz-secondary">
                                        {t(
                                            'investor.market.order_detail.empty_body',
                                        )}
                                    </p>
                                </div>
                            </>
                        )}
                    </aside>
                </div>
            ) : (
                <div className="flex min-h-full flex-col">
                    {header}
                    {body}
                    {sheets}
                </div>
            )}
        </InvestorShell>
    );
}
