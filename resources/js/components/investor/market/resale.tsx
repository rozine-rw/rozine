import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { LocalSheet, SheetClose } from '@/components/investor/local-sheet';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
import { readErrorCode } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type {
    MarketClosedListing,
    MarketListing,
    MarketSellableLot,
    MarketTrade,
} from '@/types/investor';
import type { Money } from '@/types/money';
import type { RouteAction } from '@/types/routing';

const PRIMARY_BUTTON =
    'h-[42px] w-full rounded-xl bg-rz-accent-fill text-[13.5px] font-semibold text-white disabled:opacity-60';
const SECONDARY_BUTTON =
    'h-9 rounded-[10px] border border-rz-border bg-rz-surface px-3.5 text-[12.5px] font-semibold text-rz-ink disabled:opacity-60';
const FIELD =
    'mt-1 h-[42px] w-full rounded-xl border border-rz-border bg-rz-surface px-3 text-[14px] text-rz-ink';

/** A purchase that has just settled, for the page to confirm. */
export type SettledPurchase = { name: string; units: string; amount: Money };

/** A listing the Investor has just withdrawn, for the page to confirm. */
export type WithdrawnListing = { name: string; units: string };

const money = (amount: number | string) => ({
    currency: 'RWF' as const,
    amount: String(amount),
});

/** Whole units typed into a field: digits only, or null. */
const wholeUnits = (value: string): number | null =>
    /^[1-9][0-9]{0,8}$/u.test(value) ? Number(value) : null;

/**
 * One resale command from the Market (`investor.market_*`). The server decides; on success the page
 * reloads its board, and a refusal is shown by its code until the next try.
 */
export function useResaleCommand(identityRevision: number) {
    const http = useHttp<Record<string, never>, { code: string }>();
    const [busy, setBusy] = useState(false);
    const [refusal, setRefusal] = useState<string | null>(null);

    const send = async (
        action: RouteAction,
        input: Record<string, string>,
        onDone?: () => void,
    ): Promise<boolean> => {
        let failure: string | null = null;

        setBusy(true);
        setRefusal(null);
        http.transform(() => ({
            ...input,
            request_id: crypto.randomUUID(),
            identity_context_revision: identityRevision,
        }));

        try {
            const answer = await http.submit(action, {
                onHttpException: (response) => {
                    failure = readErrorCode(response.data);
                },
            });

            if (answer === undefined) {
                setRefusal(failure ?? 'REQUEST_FAILED');
                setBusy(false);

                return false;
            }

            router.reload({
                onFinish: () => {
                    setBusy(false);
                    onDone?.();
                },
            });

            return true;
        } catch {
            setRefusal(failure ?? 'REQUEST_FAILED');
            setBusy(false);

            return false;
        }
    };

    return { send, busy, refusal };
}

function Refusal({ code }: { code: string | null }) {
    const { t } = useTranslation();

    if (code === null) {
        return null;
    }

    return (
        <p role="alert" className="mt-2.5 text-[12.5px] text-rz-danger-text">
            {t('investor.market.resale.refused', { code })}
        </p>
    );
}

/**
 * Withdraw with a confirm step: the first press asks, the second sends; the page is told once the
 * board has reloaded so it can say the listing was withdrawn (QA #663).
 */
function WithdrawControl({
    action,
    listing,
    identityRevision,
    onWithdrawn,
    wide = false,
}: {
    action: RouteAction;
    listing: MarketListing;
    identityRevision: number;
    onWithdrawn?: (withdrawn: WithdrawnListing) => void;
    wide?: boolean;
}) {
    const { t } = useTranslation();
    const command = useResaleCommand(identityRevision);
    const [confirming, setConfirming] = useState(false);

    if (!confirming) {
        return (
            <button
                type="button"
                className={cn(SECONDARY_BUTTON, wide && 'mt-3 w-full')}
                onClick={(event) => {
                    event.stopPropagation();
                    setConfirming(true);
                }}
            >
                {t('investor.market.resale.withdraw')}
            </button>
        );
    }

    return (
        <div
            className={cn('rounded-xl bg-rz-warning-tint p-3', wide && 'mt-3')}
            onClick={(event) => event.stopPropagation()}
        >
            <p className="text-[12.5px] text-rz-ink">
                {t('investor.market.resale.withdraw_confirm', {
                    count: Number(listing.remaining_units),
                    units: listing.remaining_units,
                })}
            </p>
            <div className="mt-2.5 flex gap-2">
                <button
                    type="button"
                    className={cn(SECONDARY_BUTTON, 'flex-1')}
                    disabled={command.busy}
                    onClick={() =>
                        void command.send(action, {}, () =>
                            onWithdrawn?.({
                                name: listing.deal_name,
                                units: listing.remaining_units,
                            }),
                        )
                    }
                >
                    {command.busy
                        ? t('investor.market.resale.working')
                        : t('investor.market.resale.withdraw_yes')}
                </button>
                <button
                    type="button"
                    className={cn(SECONDARY_BUTTON, 'flex-1')}
                    disabled={command.busy}
                    onClick={() => setConfirming(false)}
                >
                    {t('investor.market.resale.withdraw_keep')}
                </button>
            </div>
            <Refusal code={command.refusal} />
        </div>
    );
}

/**
 * A live listing: the deal, the units left and the price per unit, with its expiry. Someone
 * else's can be bought; the Investor's own reservation can be confirmed; their own listing can be
 * withdrawn.
 */
export function ListingCard({
    listing,
    identityRevision,
    onBuy,
    onSelect,
    selected = false,
    onSettled,
    onWithdrawn,
}: {
    listing: MarketListing;
    identityRevision: number;
    onBuy: (listing: MarketListing) => void;
    /** In Orders: opens this listing's detail. */
    onSelect?: () => void;
    selected?: boolean;
    /** Told once a confirmed purchase has settled and the board reloaded. */
    onSettled?: (bought: SettledPurchase) => void;
    /** Told once the Investor's own listing is withdrawn and the board reloaded. */
    onWithdrawn?: (withdrawn: WithdrawnListing) => void;
}) {
    const { t, locale } = useTranslation();
    const command = useResaleCommand(identityRevision);
    const total = money(
        Number(listing.remaining_units) * Number(listing.price.amount),
    );
    /** Units another buyer holds in a live reservation; the Investor's own reservation is shown below. */
    const heldByOthers =
        listing.reservation === null
            ? Number(listing.reserved_units ?? '0')
            : 0;
    const { reserve, settle, withdraw } = listing.actions;

    return (
        <li
            onClick={onSelect}
            className={cn(
                'rounded-2xl border bg-rz-surface p-3.5 shadow-[0_10px_30px_-24px_rgba(20,45,95,.28)]',
                selected ? 'border-rz-accent-fill' : 'border-rz-hairline',
                onSelect !== undefined && 'cursor-pointer',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    {onSelect !== undefined ? (
                        <button
                            type="button"
                            aria-pressed={selected}
                            onClick={(event) => {
                                event.stopPropagation();
                                onSelect();
                            }}
                            className="block max-w-full truncate text-left text-[14px] font-semibold text-rz-ink"
                        >
                            {listing.deal_name}
                        </button>
                    ) : (
                        <p className="truncate text-[14px] font-semibold text-rz-ink">
                            {listing.deal_name}
                        </p>
                    )}
                    <p className="mt-0.5 text-[12px] text-rz-secondary">
                        {t('investor.market.resale.units_at', {
                            units: listing.remaining_units,
                            count: Number(listing.remaining_units),
                            price: formatRwf(listing.price),
                        })}
                    </p>
                    {heldByOthers > 0 && (
                        <p className="mt-0.5 text-[12px] text-rz-warning-text">
                            {t('investor.market.resale.units_reserved', {
                                available: String(
                                    Math.max(
                                        0,
                                        Number(listing.remaining_units) -
                                            heldByOthers,
                                    ),
                                ),
                                reserved: String(heldByOthers),
                            })}
                        </p>
                    )}
                </div>
                {listing.mine ? (
                    <span className="shrink-0 rounded-full bg-rz-accent-soft px-2 py-0.5 text-[11px] font-semibold text-rz-accent-text">
                        {t('investor.market.resale.yours')}
                    </span>
                ) : listing.reserved ? (
                    <span className="shrink-0 rounded-full bg-rz-warning-tint px-2 py-0.5 text-[11px] font-semibold text-rz-warning-text">
                        {t('investor.market.resale.reserved')}
                    </span>
                ) : null}
            </div>
            <div className="mt-2.5 flex items-end justify-between gap-3">
                <div>
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('investor.market.resale.total')}
                    </p>
                    <p className="text-[15px] font-bold text-rz-ink">
                        {formatRwf(total)}
                    </p>
                    <p className="mt-0.5 text-[11px] text-rz-secondary">
                        {t('investor.market.resale.expires', {
                            when: formatDateTime(listing.expires_at, locale),
                        })}
                    </p>
                </div>
                {reserve !== null && (
                    <button
                        type="button"
                        className={SECONDARY_BUTTON}
                        onClick={() => onBuy(listing)}
                    >
                        {t('investor.market.resale.buy')}
                    </button>
                )}
                {withdraw !== null && (
                    <WithdrawControl
                        action={withdraw}
                        listing={listing}
                        identityRevision={identityRevision}
                        onWithdrawn={onWithdrawn}
                    />
                )}
            </div>
            {listing.reservation !== null && settle !== null && (
                <div className="mt-3 rounded-xl bg-rz-accent-soft p-3">
                    <p className="text-[12.5px] text-rz-ink">
                        {t('investor.market.resale.reserved_for_you', {
                            units: listing.reservation.units,
                            count: Number(listing.reservation.units),
                            amount: formatRwf(listing.reservation.buyer_pays),
                            when: formatDateTime(
                                listing.reservation.expires_at,
                                locale,
                            ),
                        })}
                    </p>
                    <button
                        type="button"
                        className={cn(PRIMARY_BUTTON, 'mt-2.5')}
                        disabled={command.busy}
                        onClick={() => {
                            const reservation = listing.reservation!;

                            void command.send(
                                settle,
                                {
                                    reservation_id: reservation.reservation_id,
                                },
                                () =>
                                    onSettled?.({
                                        name: listing.deal_name,
                                        units: reservation.units,
                                        amount: reservation.buyer_pays,
                                    }),
                            );
                        }}
                    >
                        {command.busy
                            ? t('investor.market.resale.working')
                            : t('investor.market.resale.confirm')}
                    </button>
                </div>
            )}
            <Refusal code={command.refusal} />
        </li>
    );
}

/** Reserving units of a listing: how many, and what they cost at its price. */
export function BuySheet({
    listing,
    identityRevision,
    onClose,
}: {
    listing: MarketListing;
    identityRevision: number;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const command = useResaleCommand(identityRevision);
    const [units, setUnits] = useState(listing.remaining_units);
    const count = wholeUnits(units);
    const tooMany = count !== null && count > Number(listing.remaining_units);
    const valid = count !== null && !tooMany;
    const title = t('investor.market.resale.buy_title', {
        name: listing.deal_name,
    });

    return (
        <LocalSheet label={title} onClose={onClose}>
            <form
                className="overflow-y-auto px-4 pt-2 pb-5"
                onSubmit={(event) => {
                    event.preventDefault();

                    if (valid && listing.actions.reserve !== null) {
                        void command.send(
                            listing.actions.reserve,
                            { units },
                            onClose,
                        );
                    }
                }}
            >
                <div className="flex items-center justify-between gap-3">
                    <h2 className="text-[15px] font-semibold text-rz-ink">
                        {title}
                    </h2>
                    <SheetClose onClose={onClose} />
                </div>
                <p className="mt-1 text-[12.5px] text-rz-secondary">
                    {t('investor.market.resale.units_at', {
                        units: listing.remaining_units,
                        count: Number(listing.remaining_units),
                        price: formatRwf(listing.price),
                    })}
                </p>
                <label className="mt-4 block text-[12px] font-semibold text-rz-ink">
                    {t('investor.market.resale.units')}
                    <input
                        inputMode="numeric"
                        value={units}
                        onChange={(event) => setUnits(event.target.value)}
                        aria-invalid={tooMany || undefined}
                        className={FIELD}
                    />
                </label>
                {tooMany && (
                    <p
                        role="alert"
                        className="mt-1.5 text-[12px] text-rz-danger-text"
                    >
                        {t('investor.market.resale.too_many', {
                            count: Number(listing.remaining_units),
                        })}
                    </p>
                )}
                {valid && (
                    <p className="mt-3 text-[13px] text-rz-ink">
                        {t('investor.market.resale.you_pay', {
                            amount: formatRwf(
                                money(count * Number(listing.price.amount)),
                            ),
                        })}
                    </p>
                )}
                <p className="mt-1 text-[11.5px] text-rz-secondary">
                    {t('investor.market.resale.reserve_note')}
                </p>
                <button
                    type="submit"
                    className={cn(PRIMARY_BUTTON, 'mt-4')}
                    disabled={!valid || command.busy}
                >
                    {command.busy
                        ? t('investor.market.resale.working')
                        : t('investor.market.resale.reserve')}
                </button>
                <Refusal code={command.refusal} />
            </form>
        </LocalSheet>
    );
}

/** Listing units of a held lot at a price per unit, no higher than the note's cap. */
export function SellSheet({
    lots,
    action,
    identityRevision,
    onClose,
}: {
    lots: MarketSellableLot[];
    action: RouteAction;
    identityRevision: number;
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const command = useResaleCommand(identityRevision);
    const open = lots.filter((lot) => lot.tradable && lot.free_units !== '0');
    const [lotId, setLotId] = useState(open[0]?.lot_id ?? '');
    const lot = open.find((candidate) => candidate.lot_id === lotId) ?? null;
    const [units, setUnits] = useState('');
    const [price, setPrice] = useState(lot?.cap.amount ?? '');
    const count = wholeUnits(units);
    const perUnit = wholeUnits(price);
    const tooMany =
        lot !== null && count !== null && count > Number(lot.free_units);
    const overCap =
        lot !== null && perUnit !== null && perUnit > Number(lot.cap.amount);
    const valid =
        lot !== null &&
        count !== null &&
        !tooMany &&
        perUnit !== null &&
        !overCap;
    const title = t('investor.market.resale.sell_title');

    return (
        <LocalSheet label={title} onClose={onClose}>
            <form
                className="overflow-y-auto px-4 pt-2 pb-5"
                onSubmit={(event) => {
                    event.preventDefault();

                    if (valid) {
                        void command.send(
                            action,
                            { lot_id: lotId, units, price },
                            onClose,
                        );
                    }
                }}
            >
                <div className="flex items-center justify-between gap-3">
                    <h2 className="text-[15px] font-semibold text-rz-ink">
                        {title}
                    </h2>
                    <SheetClose onClose={onClose} />
                </div>
                <label className="mt-3 block text-[12px] font-semibold text-rz-ink">
                    {t('investor.market.resale.holding')}
                    <select
                        value={lotId}
                        onChange={(event) => {
                            const next = open.find(
                                (candidate) =>
                                    candidate.lot_id === event.target.value,
                            );

                            setLotId(event.target.value);
                            setPrice(next?.cap.amount ?? '');
                        }}
                        className={FIELD}
                    >
                        {open.map((candidate) => (
                            <option
                                key={candidate.lot_id}
                                value={candidate.lot_id}
                            >
                                {t('investor.market.resale.lot_option', {
                                    name: candidate.deal_name,
                                    units: candidate.free_units,
                                    count: Number(candidate.free_units),
                                })}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="mt-3 block text-[12px] font-semibold text-rz-ink">
                    {t('investor.market.resale.units')}
                    <input
                        inputMode="numeric"
                        value={units}
                        onChange={(event) => setUnits(event.target.value)}
                        placeholder={lot?.free_units}
                        aria-invalid={tooMany || undefined}
                        className={FIELD}
                    />
                </label>
                {tooMany && lot !== null && (
                    <p
                        role="alert"
                        className="mt-1.5 text-[12px] text-rz-danger-text"
                    >
                        {t('investor.market.resale.free_only', {
                            count: Number(lot.free_units),
                        })}
                    </p>
                )}
                <label className="mt-3 block text-[12px] font-semibold text-rz-ink">
                    {t('investor.market.resale.price')}
                    <input
                        inputMode="numeric"
                        value={price}
                        onChange={(event) => setPrice(event.target.value)}
                        aria-invalid={overCap || undefined}
                        className={FIELD}
                    />
                </label>
                {lot !== null && (
                    <p
                        role={overCap ? 'alert' : undefined}
                        className={cn(
                            'mt-1.5',
                            overCap
                                ? 'text-[12px] text-rz-danger-text'
                                : 'text-[11.5px] text-rz-secondary',
                        )}
                    >
                        {t('investor.market.resale.cap', {
                            cap: formatRwf(lot.cap),
                        })}
                    </p>
                )}
                {valid && (
                    <p className="mt-3 text-[13px] text-rz-ink">
                        {t('investor.market.resale.listing_total', {
                            amount: formatRwf(money(count * perUnit)),
                        })}
                    </p>
                )}
                <button
                    type="submit"
                    className={cn(PRIMARY_BUTTON, 'mt-4')}
                    disabled={!valid || command.busy}
                >
                    {command.busy
                        ? t('investor.market.resale.working')
                        : t('investor.market.resale.list')}
                </button>
                <Refusal code={command.refusal} />
            </form>
        </LocalSheet>
    );
}

/** A settled resale: bought or sold, units at price, and what was paid or received. */
export function TradeRow({
    trade,
    onSelect,
    selected = false,
}: {
    trade: MarketTrade;
    /** In Orders: opens this trade's detail. */
    onSelect?: () => void;
    selected?: boolean;
}) {
    const { t, locale } = useTranslation();
    const label = t(`investor.market.resale.trade_${trade.side}`, {
        name: trade.deal_name,
    });

    return (
        <li
            onClick={onSelect}
            className={cn(
                'flex items-center justify-between gap-3 border-b border-[#eef2f9] py-3 last:border-b-0 dark:border-rz-divider',
                onSelect !== undefined && 'cursor-pointer',
                selected && 'bg-rz-accent-soft',
            )}
        >
            <div className="min-w-0">
                {onSelect !== undefined ? (
                    <button
                        type="button"
                        aria-pressed={selected}
                        onClick={(event) => {
                            event.stopPropagation();
                            onSelect();
                        }}
                        className="block max-w-full truncate text-left text-[13.5px] font-semibold text-rz-ink"
                    >
                        {label}
                    </button>
                ) : (
                    <p className="truncate text-[13.5px] font-semibold text-rz-ink">
                        {label}
                    </p>
                )}
                <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                    {t('investor.market.resale.units_at', {
                        units: trade.units,
                        count: Number(trade.units),
                        price: formatRwf(trade.price),
                    })}{' '}
                    · {formatDateTime(trade.settled_at, locale)}
                </p>
            </div>
            <p className="shrink-0 text-[13.5px] font-semibold text-rz-ink">
                {trade.side === 'buy' ? '−' : '+'}
                {formatRwf(trade.amount)}
            </p>
        </li>
    );
}

/** One labelled fact in the order detail. */
function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline justify-between gap-3 border-b border-[#eef2f9] py-2 last:border-b-0 dark:border-rz-divider">
            <dt className="text-[12px] text-rz-secondary">{label}</dt>
            <dd className="text-right text-[12.5px] font-semibold text-rz-ink">
                {value}
            </dd>
        </div>
    );
}

/**
 * The detail of one order in Orders: a live listing (the seller's own, or the Investor's reservation)
 * with its units, price, total, status and expiry and the command open on it, or a settled trade
 * with what was paid or received and the fee.
 */
export function OrderDetail({
    order,
    identityRevision,
    onWithdrawn,
}: {
    order:
        | { type: 'listing'; listing: MarketListing }
        | { type: 'trade'; trade: MarketTrade }
        | { type: 'closed'; listing: MarketClosedListing };
    identityRevision: number;
    onWithdrawn?: (withdrawn: WithdrawnListing) => void;
}) {
    const { t, locale } = useTranslation();

    if (order.type === 'closed') {
        const { listing } = order;

        return (
            <div>
                <p className="text-[14px] font-semibold text-rz-ink">
                    {listing.deal_name}
                </p>
                <dl className="mt-2">
                    <DetailRow
                        label={t('investor.market.order_detail.status')}
                        value={t(closedStatus(listing))}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.side')}
                        value={t('investor.market.order_detail.side_sell')}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.listed')}
                        value={listing.listed_units}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.price')}
                        value={formatRwf(listing.price)}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.ended')}
                        value={formatDateTime(listing.ended_at, locale)}
                    />
                </dl>
            </div>
        );
    }

    if (order.type === 'trade') {
        const { trade } = order;

        return (
            <div>
                <p className="text-[14px] font-semibold text-rz-ink">
                    {trade.deal_name}
                </p>
                <dl className="mt-2">
                    <DetailRow
                        label={t('investor.market.order_detail.status')}
                        value={t('investor.market.order_detail.filled')}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.side')}
                        value={t(
                            `investor.market.order_detail.side_${trade.side}`,
                        )}
                    />
                    <DetailRow
                        label={t('investor.market.resale.units')}
                        value={trade.units}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.price')}
                        value={formatRwf(trade.price)}
                    />
                    <DetailRow
                        label={t(
                            `investor.market.order_detail.amount_${trade.side}`,
                        )}
                        value={formatRwf(trade.amount)}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.fee')}
                        value={formatRwf(trade.fee)}
                    />
                    <DetailRow
                        label={t('investor.market.order_detail.settled')}
                        value={formatDateTime(trade.settled_at, locale)}
                    />
                </dl>
            </div>
        );
    }

    const { listing } = order;
    const { withdraw } = listing.actions;
    const status = listing.mine
        ? listing.reserved
            ? 'reserved'
            : 'open'
        : listing.reservation !== null
          ? 'reserved_for_you'
          : 'open';

    return (
        <div>
            <p className="text-[14px] font-semibold text-rz-ink">
                {listing.deal_name}
            </p>
            <dl className="mt-2">
                <DetailRow
                    label={t('investor.market.order_detail.status')}
                    value={t(`investor.market.order_detail.status_${status}`)}
                />
                <DetailRow
                    label={t('investor.market.order_detail.side')}
                    value={t(
                        `investor.market.order_detail.side_${listing.mine ? 'sell' : 'buy'}`,
                    )}
                />
                <DetailRow
                    label={t('investor.market.order_detail.remaining')}
                    value={
                        listing.reservation !== null && !listing.mine
                            ? listing.reservation.units
                            : listing.remaining_units
                    }
                />
                <DetailRow
                    label={t('investor.market.order_detail.price')}
                    value={formatRwf(listing.price)}
                />
                <DetailRow
                    label={t('investor.market.resale.total')}
                    value={formatRwf(
                        listing.reservation !== null && !listing.mine
                            ? listing.reservation.buyer_pays
                            : money(
                                  Number(listing.remaining_units) *
                                      Number(listing.price.amount),
                              ),
                    )}
                />
                <DetailRow
                    label={t('investor.market.order_detail.expires')}
                    value={formatDateTime(
                        listing.reservation !== null && !listing.mine
                            ? listing.reservation.expires_at
                            : listing.expires_at,
                        locale,
                    )}
                />
            </dl>
            {withdraw !== null && (
                <WithdrawControl
                    action={withdraw}
                    listing={listing}
                    identityRevision={identityRevision}
                    onWithdrawn={onWithdrawn}
                    wide
                />
            )}
        </div>
    );
}

/** The label key for why a listing ended unsold. */
function closedStatus(
    listing: MarketClosedListing,
):
    | 'investor.market.order_detail.status_expired'
    | 'investor.market.order_detail.status_withdrawn'
    | 'investor.market.order_detail.status_cancelled' {
    if (listing.state === 'expired') {
        return 'investor.market.order_detail.status_expired';
    }

    return listing.cancelled_by === 'seller'
        ? 'investor.market.order_detail.status_withdrawn'
        : 'investor.market.order_detail.status_cancelled';
}

/** One of the Investor's listings that ended unsold, under Orders → Cancelled. */
export function ClosedRow({
    listing,
    onSelect,
    selected = false,
}: {
    listing: MarketClosedListing;
    onSelect: () => void;
    selected?: boolean;
}) {
    const { t, locale } = useTranslation();

    return (
        <li>
            <button
                type="button"
                aria-pressed={selected}
                onClick={onSelect}
                className={cn(
                    'flex w-full items-start justify-between gap-3 border-b border-rz-hairline py-3 text-left',
                    selected && 'bg-rz-accent-soft',
                )}
            >
                <span className="min-w-0">
                    <span className="block truncate text-[13.5px] font-semibold text-rz-ink">
                        {t('investor.market.resale.closed_title', {
                            name: listing.deal_name,
                        })}
                    </span>
                    <span className="mt-0.5 block text-[12px] text-rz-secondary">
                        {t('investor.market.resale.units_at', {
                            units: listing.listed_units,
                            count: Number(listing.listed_units),
                            price: formatRwf(listing.price),
                        })}
                    </span>
                </span>
                <span className="shrink-0 text-right">
                    <span className="block text-[12px] font-semibold text-rz-secondary">
                        {t(closedStatus(listing))}
                    </span>
                    <span className="mt-0.5 block text-[11px] text-rz-secondary">
                        {formatDateTime(listing.ended_at, locale)}
                    </span>
                </span>
            </button>
        </li>
    );
}
