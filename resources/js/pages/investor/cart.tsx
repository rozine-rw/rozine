import { Link, router, useHttp } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { RANGE_CLASS } from '@/components/investor/deals/invest-bar';
import { InvestorShell } from '@/components/investor/investor-shell';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { withQuery } from '@/lib/investor/links';
import { useWide } from '@/lib/investor/use-wide';
import {
    formatAmount,
    formatCount,
    formatRwf,
    formatRwfShort,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { Money } from '@/types';
import type { C3InvestorCartProps, InvestorCartLine } from '@/types/investor';
import type { RouteAction } from '@/types/routing';

/** How long a slider rests before its new quantity is saved to the cart. */
const SAVE_DELAY_MS = 400;

const MICRO =
    'text-[9.5px] leading-3 font-bold tracking-[.08em] whitespace-nowrap text-[#636e86] uppercase dark:text-rz-muted';
const FACT =
    'text-[10px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase';

const rwf = (amount: number): Money => ({
    currency: 'RWF',
    amount: String(amount),
});

/** Whether a line's notes may still be bought now: not filled, and at least one note allowed. */
const buyable = (line: InvestorCartLine) =>
    !line.filled && Number(line.max_units) >= 1;

/** The line's quantity before the Investor moves its slider: what the cart holds, within today's cap. */
const savedUnits = (line: InvestorCartLine) =>
    Math.max(1, Math.min(Number(line.units), Number(line.max_units)));

function CartIcon({ size }: { size: number }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
        >
            <path
                d="M2.8 4h2.3l2.2 10.3a1.6 1.6 0 0 0 1.6 1.3h8.3a1.6 1.6 0 0 0 1.56-1.22L20.6 8H6"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <circle cx="9.6" cy="19.6" r="1.35" fill="currentColor" />
            <circle cx="17" cy="19.6" r="1.35" fill="currentColor" />
        </svg>
    );
}

/**
 * One cart card (design L2879–2931): the business, its industry and district and Remove; raised of
 * target, average monthly revenue, what is left to fill and the notes taken; then invest +
 * interest = get back for the chosen notes, the slider and the line's own checkout. A filled deal
 * is dimmed with "Fully funded · Remove" in place of the slider; a deal the Investor may take no
 * more of names the cap instead.
 */
function CartCard({
    line,
    units,
    onUnits,
    onRemove,
    removing,
}: {
    line: InvestorCartLine;
    units: number;
    onUnits: (units: number) => void;
    onRemove: () => void;
    removing: boolean;
}) {
    const { t } = useTranslation();
    const { deal } = line;
    const price = Number(deal.unit_price.amount);
    const max = Number(line.max_units);
    const taken = Number(deal.units.total) - Number(deal.units.available);
    const leftToFill = Number(deal.left_to_fill.amount);
    const reason = t(`investor.deal.cap.reason.${line.binding}`);

    return (
        <article
            aria-label={deal.name}
            data-testid={`cart-line-${line.campaign_id}`}
            className={cn(
                'rounded-[18px] border border-rz-border bg-rz-surface p-3.5 shadow-[0_10px_26px_-20px_rgba(20,45,95,.3)]',
                line.filled && 'opacity-[.62]',
            )}
        >
            <div className="flex items-center gap-[11px]">
                <span
                    aria-hidden
                    className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#0b1c3a] text-base font-bold text-white"
                >
                    {deal.name.charAt(0)}
                </span>
                <div className="min-w-0 flex-1">
                    <Link
                        href={deal.links.detail}
                        className="block truncate text-[15px] font-semibold text-rz-ink"
                    >
                        {deal.name}
                    </Link>
                    <p className="mt-0.5 truncate text-[11.5px] text-rz-secondary">
                        {deal.industry} · {deal.district}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onRemove}
                    disabled={removing}
                    aria-label={t('investor.cart.remove', { name: deal.name })}
                    title={t('investor.cart.remove', { name: deal.name })}
                    className="flex size-8 shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-rz-secondary hover:bg-rz-surface-sunken disabled:opacity-60"
                >
                    <svg
                        width="13"
                        height="13"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                    >
                        <path
                            d="M6 6l12 12M18 6 6 18"
                            stroke="currentColor"
                            strokeWidth="2.2"
                            strokeLinecap="round"
                        />
                    </svg>
                </button>
            </div>

            <dl className="mt-3 grid grid-cols-2 gap-x-3 gap-y-2.5 rounded-xl bg-rz-surface-sunken px-3 py-[11px]">
                <div className="min-w-0">
                    <dt className={FACT}>{t('investor.deals.raised')}</dt>
                    <dd className="mt-[3px] text-xs font-bold whitespace-nowrap text-rz-ink">
                        {formatRwf(deal.raised)}
                    </dd>
                    <dd className="mt-px text-[11px] whitespace-nowrap text-rz-secondary">
                        {t('investor.cart.target_of', {
                            amount: formatRwfShort(deal.target),
                        })}
                    </dd>
                </div>
                <div className="min-w-0 text-right">
                    <dt className={FACT}>
                        {t('investor.deals.avg_revenue_short')}
                    </dt>
                    <dd className="mt-[3px] text-xs font-bold whitespace-nowrap text-rz-ink">
                        {formatRwf(deal.avg_monthly_revenue)}
                    </dd>
                </div>
                <div className="min-w-0">
                    <dt className={FACT}>{t('investor.deals.left_to_fill')}</dt>
                    <dd className="mt-[3px] text-xs font-bold whitespace-nowrap text-rz-ink">
                        {leftToFill <= 0 || line.filled
                            ? t('investor.deals.fully_funded')
                            : formatRwf(deal.left_to_fill)}
                    </dd>
                </div>
                <div className="min-w-0 text-right">
                    <dt className={FACT}>{t('investor.deals.notes')}</dt>
                    <dd className="mt-[3px] text-xs font-semibold whitespace-nowrap text-rz-ink">
                        {formatCount(taken)}{' '}
                        <span className="font-medium text-rz-secondary">
                            {t('investor.deals.taken')}
                        </span>
                    </dd>
                </div>
            </dl>

            <div className="mt-3 grid grid-cols-[1fr_auto_auto_auto_1fr] items-start gap-x-0.5">
                <div className="min-w-0">
                    <p className={MICRO}>{t('investor.deals.bar_invest')}</p>
                    <p className="mt-1 text-sm font-bold whitespace-nowrap text-rz-ink tabular-nums">
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        <span data-testid="line-invest">
                            {formatAmount(rwf(units * price))}
                        </span>
                    </p>
                </div>
                <span
                    aria-hidden
                    className="self-end pl-1.5 text-sm font-bold text-rz-faint"
                >
                    +
                </span>
                <div className="text-center">
                    <p className={MICRO}>{t('investor.deals.bar_interest')}</p>
                    <p className="mt-1 text-sm font-bold whitespace-nowrap text-rz-accent-app-text">
                        {deal.rate_pct}%
                    </p>
                </div>
                <span
                    aria-hidden
                    className="self-end pr-1.5 text-sm font-bold text-rz-faint"
                >
                    =
                </span>
                <div className="min-w-0 text-right">
                    <p className={MICRO}>{t('investor.deals.bar_get_back')}</p>
                    <p
                        className={cn(
                            'mt-1 text-sm font-bold whitespace-nowrap tabular-nums',
                            POSITIVE_TEXT,
                        )}
                    >
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        <span data-testid="line-get-back">
                            {formatAmount(
                                rwf(
                                    units *
                                        (price +
                                            Number(line.unit_return.amount)),
                                ),
                            )}
                        </span>
                    </p>
                </div>
            </div>

            {line.filled ? (
                <button
                    type="button"
                    onClick={onRemove}
                    disabled={removing}
                    className="mt-[11px] h-[38px] w-full rounded-[11px] border border-rz-border bg-rz-surface-sunken text-[12.5px] font-semibold text-rz-slate"
                >
                    {t('investor.cart.filled_remove')}
                </button>
            ) : max < 1 ? (
                <p
                    role="status"
                    className="mt-[11px] text-center text-[11px] leading-[1.4] text-rz-secondary"
                >
                    {t('investor.deal.cap.none', { reason })}
                </p>
            ) : (
                <>
                    <div className="mt-[11px] flex items-center gap-3">
                        <input
                            type="range"
                            min={1}
                            max={max}
                            step={1}
                            value={units}
                            disabled={line.actions.units === null}
                            aria-label={t('investor.cart.quantity', {
                                name: deal.name,
                            })}
                            onChange={(event) =>
                                onUnits(Number(event.target.value))
                            }
                            className={RANGE_CLASS}
                        />
                        <span className="min-w-[72px] shrink-0 text-right text-[12.5px] font-bold text-rz-ink tabular-nums">
                            {formatCount(units)}{' '}
                            <span className="font-medium text-rz-secondary">
                                {t('investor.cart.units', { count: units })}
                            </span>
                        </span>
                    </div>
                    {units >= max && (
                        <p
                            role="status"
                            className="mt-1.5 text-center text-[11px] leading-[1.4] text-rz-secondary"
                        >
                            {t('investor.deal.cap.max', { count: max, reason })}
                        </p>
                    )}
                    {line.checkout !== null && (
                        <Link
                            href={withQuery(line.checkout, { units })}
                            className="mt-[11px] flex h-10 w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[13.5px] font-semibold text-white"
                        >
                            {t('investor.cart.checkout')}
                        </Link>
                    )}
                </>
            )}
        </article>
    );
}

/**
 * Cart (BRS v2.0 INV-R02, design L2832–2992): the notes an Investor picked from Deals, each with its
 * own amount. The cart total sits fixed at the top — pinned above the list on a phone, in the side
 * column on a wide screen — and moves with every slider at once; a slider that rests is saved to
 * the cart. A deal that filled after it was added stays visible, marked fully funded, and is left
 * out of the total. Totals are display only: each line's checkout quotes, caps and admits its own
 * purchase.
 *
 * Checking the whole cart out in one atomic purchase (INV-R03) has no server command yet, so there
 * is no cart-wide Check out button and no fee band here: each line opens its deal's existing
 * checkout with the chosen notes. Before verification there is no cart, so the empty cart shows.
 */
export default function InvestorCart({
    identity_context_revision,
    links,
    cart,
}: C3InvestorCartProps) {
    const { t } = useTranslation();
    const wide = useWide();
    const http = useHttp<Record<string, never>, unknown>();
    const [chosen, setChosen] = useState<Record<string, number>>({});
    const [removing, setRemoving] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);
    const timers = useRef<Record<string, number>>({});
    const lines = cart?.lines ?? [];
    const units = (line: InvestorCartLine) =>
        chosen[line.campaign_id] ?? savedUnits(line);
    const live = lines.filter(buyable);
    const filled = lines.filter((line) => line.filled).length;
    const total = live.reduce(
        (sum, line) => sum + units(line) * Number(line.deal.unit_price.amount),
        0,
    );
    const back = live.reduce(
        (sum, line) =>
            sum +
            units(line) *
                (Number(line.deal.unit_price.amount) +
                    Number(line.unit_return.amount)),
        0,
    );

    const send = async (action: RouteAction, body: Record<string, unknown>) => {
        http.transform(() => ({
            identity_context_revision,
            ...body,
        }));

        try {
            const answer = await http.submit(action, {});

            setFailed(answer === undefined);

            return answer !== undefined;
        } catch {
            setFailed(true);

            return false;
        }
    };

    const change = (line: InvestorCartLine, next: number) => {
        const action = line.actions.units;

        setChosen((current) => ({ ...current, [line.campaign_id]: next }));
        window.clearTimeout(timers.current[line.campaign_id]);

        if (action !== null) {
            timers.current[line.campaign_id] = window.setTimeout(() => {
                void send(action, { units: next });
            }, SAVE_DELAY_MS);
        }
    };

    const remove = async (line: InvestorCartLine) => {
        window.clearTimeout(timers.current[line.campaign_id]);
        setRemoving(line.campaign_id);

        if (await send(line.actions.remove, {})) {
            router.reload({ only: ['cart'] });
        }

        setRemoving(null);
    };

    const header = (
        <div
            className={
                wide ? 'pb-3' : 'px-5 pt-[calc(env(safe-area-inset-top)+56px)]'
            }
        >
            <div className="flex items-baseline justify-between gap-2.5">
                <h1
                    className={cn(
                        'text-rz-ink',
                        wide
                            ? 'text-[19px] font-bold'
                            : 'text-2xl font-semibold',
                    )}
                >
                    {t('investor.cart.title')}
                </h1>
                {lines.length > 0 && (
                    <span className="text-[12.5px] font-semibold text-rz-secondary">
                        {t('investor.cart.count', { count: live.length })}
                    </span>
                )}
            </div>
        </div>
    );
    const empty = (
        <div className="flex flex-col items-center px-6 py-[70px] text-center">
            <span className="flex size-14 items-center justify-center rounded-[18px] bg-rz-accent-soft text-rz-accent-text">
                <CartIcon size={26} />
            </span>
            <p className="mt-3.5 text-base font-semibold text-rz-ink">
                {t('investor.cart.empty_title')}
            </p>
            <p className="mt-1.5 max-w-[260px] text-[13px] leading-normal text-pretty text-rz-secondary">
                {t('investor.cart.empty_body')}
            </p>
            {links.deals !== null && (
                <Link
                    href={links.deals}
                    className="mt-[18px] rounded-xl bg-rz-accent-fill px-[22px] py-3 text-sm font-semibold text-white"
                >
                    {t('investor.cart.browse_deals')}
                </Link>
            )}
        </div>
    );
    const summary = (
        <section
            aria-label={t('investor.cart.total')}
            className="rounded-[18px] border border-rz-border bg-rz-surface px-3.5 pt-[13px] pb-3 shadow-[0_12px_30px_-20px_rgba(20,45,95,.28)]"
        >
            <div className="flex items-end justify-between gap-3">
                <div className="min-w-0">
                    <p className={MICRO}>{t('investor.cart.total')}</p>
                    <p className="mt-1 flex items-baseline gap-[5px] whitespace-nowrap tabular-nums">
                        <span className="text-[13px] font-bold text-rz-ink">
                            {t('investor.money.rwf')}
                        </span>
                        <span
                            data-testid="cart-total"
                            aria-live="polite"
                            className="text-2xl leading-none font-bold tracking-[-.6px] text-rz-ink"
                        >
                            {formatAmount(rwf(total))}
                        </span>
                    </p>
                </div>
                <div className="shrink-0 text-right">
                    <p className={MICRO}>{t('investor.deals.bar_get_back')}</p>
                    <p
                        data-testid="cart-get-back"
                        className={cn(
                            'mt-1 text-sm font-bold whitespace-nowrap tabular-nums',
                            POSITIVE_TEXT,
                        )}
                    >
                        {formatRwf(rwf(back))}
                    </p>
                </div>
            </div>
            <div className="mt-[11px] border-t border-rz-divider pt-2.5 text-[11.5px] leading-[1.4] text-rz-secondary">
                {filled > 0 && (
                    <p className="mb-1.5 font-semibold text-rz-warning-text">
                        {t('investor.cart.filled_note', { count: filled })}
                    </p>
                )}
                <p>{t('investor.cart.checkout_note')}</p>
                <p className="mt-1">{t('investor.cart.before_charge')}</p>
            </div>
            {failed && (
                <p
                    role="alert"
                    className="mt-2 text-[11.5px] font-semibold text-rz-ink"
                >
                    {t('investor.cart.update_failed')}
                </p>
            )}
        </section>
    );
    const list = (
        <div className="flex flex-col gap-3">
            {lines.map((line) => (
                <CartCard
                    key={line.campaign_id}
                    line={line}
                    units={units(line)}
                    onUnits={(next) => change(line, next)}
                    onRemove={() => void remove(line)}
                    removing={removing === line.campaign_id}
                />
            ))}
        </div>
    );

    return (
        <InvestorShell
            title={t('investor.cart.title')}
            tab="cart"
            links={links}
        >
            {wide ? (
                <div className="grid h-full grid-cols-[minmax(0,1fr)_300px] gap-[18px] px-[18px] py-4">
                    <div className="min-w-0">
                        {header}
                        {lines.length === 0 ? empty : list}
                    </div>
                    {lines.length > 0 && (
                        <div className="sticky top-4 self-start">{summary}</div>
                    )}
                </div>
            ) : (
                <div className="flex min-h-full flex-col pb-[calc(63px+env(safe-area-inset-bottom)+16px)]">
                    {header}
                    {lines.length === 0 ? (
                        empty
                    ) : (
                        <>
                            <div className="px-4 pt-3 pb-2">{summary}</div>
                            <div className="px-4 pt-1">{list}</div>
                        </>
                    )}
                </div>
            )}
        </InvestorShell>
    );
}
