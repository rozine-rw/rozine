import { Link, router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { raiseOpen } from '@/components/investor/deals/time-left';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatCount } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    C3DealCard,
    InvestGate,
    InvestorAllowedAction,
    PrimaryQuote,
} from '@/types/investor';
import type { RouteAction } from '@/types/routing';

/** The design's `.rz-qty` range: a 4px track and a 22px accent thumb ringed in white. */
export const RANGE_CLASS =
    'h-1 min-w-0 flex-1 cursor-pointer appearance-none rounded-[3px] bg-[#e2e8f2] outline-none dark:bg-rz-surface-muted [&::-moz-range-thumb]:size-[18px] [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-4 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:bg-rz-accent-fill [&::-webkit-slider-thumb]:size-[22px] [&::-webkit-slider-thumb]:cursor-grab [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-4 [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:bg-rz-accent-fill [&::-webkit-slider-thumb]:shadow-[0_2px_8px_rgba(30,58,255,.4)] active:[&::-webkit-slider-thumb]:shadow-[0_0_0_7px_rgba(30,58,255,.10)]';

/**
 * Whether a deal may be reserved now (C3 v2 §2b): the campaign is live with no restriction and the
 * Investor is eligible. Where the server quotes the deck, it must also list `primary.reserve` and
 * allow at least one unit. The live Deals pages send no quote, only the checkout link (offered
 * when the Investor has a wallet and no restriction); the checkout quotes and admits from there.
 */
export function canReserve({
    deal,
    gate,
    allowed,
    quote,
}: {
    deal: Pick<C3DealCard, 'lifecycle' | 'restriction'>;
    gate: InvestGate;
    allowed: InvestorAllowedAction[];
    quote: PrimaryQuote | null;
}): boolean {
    return (
        deal.lifecycle === 'live' &&
        deal.restriction === null &&
        gate.status === 'eligible' &&
        (quote === null ||
            (allowed.includes('primary.reserve') &&
                Number(quote.capacity.max_units) >= 1))
    );
}

/** Names the cap that binds once the chosen quantity reaches `max_units` (H9 `capacity.binding`). */
export function CapNote({
    quote,
    units,
    className,
}: {
    quote: PrimaryQuote | null;
    units: number;
    className?: string;
}) {
    const { t } = useTranslation();

    if (quote === null) {
        return null;
    }

    const max = Number(quote.capacity.max_units);

    if (units < max) {
        return null;
    }

    const reason = t(`investor.deal.cap.reason.${quote.capacity.binding}`);

    return (
        <p
            role="status"
            className={cn(
                'text-center text-[11px] leading-[1.4] text-rz-secondary',
                className,
            )}
        >
            {max === 0
                ? t('investor.deal.cap.none', { reason })
                : t('investor.deal.cap.max', { count: max, reason })}
        </p>
    );
}

/** The focused deal's place in the Investor's cart, as Deals hands it to the toggle. */
export type CartToggleState = {
    /** The deal is already in the cart. */
    inCart: boolean;
    /** Add (with the chosen units) or remove; null while the deal in front is not the focused one. */
    action: RouteAction | null;
    identityRevision: number;
};

/** A deal is fully funded for the cart once its raise is no longer live or no note is left. */
const fullyFunded = (deal: Pick<C3DealCard, 'lifecycle' | 'units'>) =>
    deal.lifecycle !== 'live' || Number(deal.units.available) <= 0;

/**
 * Add to cart (BRS v2.0 INV-R02, design desktop L393–398): adds the deal at the chosen number of
 * notes, or, once it is in the cart, reads "In cart · Remove" and takes it out. A fully funded
 * deal cannot be added, so the button is disabled and says so. On a phone it is the swipe card's
 * square cart button (SwipeCard.dc.html L133), named by its label, with a plus, tick or stroke for
 * its state. The server decides; the cart is reloaded for the deal in front after each change.
 */
export function CartToggle({
    deal,
    cart,
    units,
    size,
}: {
    deal: C3DealCard;
    cart: CartToggleState;
    units: number;
    size: 'card' | 'desk';
}) {
    const { t } = useTranslation();
    const http = useHttp<Record<string, never>, unknown>();
    const [busy, setBusy] = useState(false);
    const [failed, setFailed] = useState(false);
    const full = !cart.inCart && fullyFunded(deal);
    const action = cart.action;
    const label = cart.inCart
        ? t('investor.cart.in_cart_remove')
        : full
          ? t('investor.deals.fully_funded')
          : t('investor.cart.add');
    const card = size === 'card';

    const toggle = async (target: RouteAction) => {
        setBusy(true);
        setFailed(false);
        http.transform(() =>
            cart.inCart
                ? { identity_context_revision: cart.identityRevision }
                : { identity_context_revision: cart.identityRevision, units },
        );

        /* The toggle stays busy until the reload brings the new cart state, so a second tap cannot repeat the change. */
        try {
            const answer = await http.submit(target, {});

            if (answer === undefined) {
                setFailed(true);
                setBusy(false);
            } else {
                router.reload({
                    // Refresh nested home and the shared badge too. A removed card may no longer
                    // belong to the filtered deck, so let its server read choose the next focus.
                    data: { deal: cart.inCart ? undefined : deal.campaign_id },
                    onFinish: () => setBusy(false),
                });
            }
        } catch {
            setFailed(true);
            setBusy(false);
        }
    };

    return (
        <div className={cn(card && 'relative -mt-1 -mb-1 shrink-0')}>
            <button
                type="button"
                /* On the card, a press on the button is not the start of a swipe. */
                onPointerDown={
                    card ? (event) => event.stopPropagation() : undefined
                }
                onClick={() => action !== null && toggle(action)}
                disabled={full || action === null || busy}
                aria-pressed={cart.inCart}
                aria-label={card ? label : undefined}
                title={card ? label : undefined}
                className={cn(
                    'flex items-center justify-center gap-2 border font-semibold transition-colors',
                    card
                        ? 'size-[34px] rounded-[10px]'
                        : 'h-[42px] w-full rounded-xl text-[13.5px]',
                    cart.inCart
                        ? 'border-rz-accent-fill/30 bg-rz-accent-soft text-rz-accent-text'
                        : full
                          ? 'cursor-not-allowed border-rz-border bg-rz-surface-sunken text-rz-faint'
                          : 'border-rz-border bg-rz-surface text-rz-ink disabled:opacity-60',
                )}
            >
                <svg
                    width={card ? 16 : 17}
                    height={card ? 16 : 17}
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
                    {card && (
                        <path
                            d={
                                cart.inCart
                                    ? 'M10.4 10.9l1.7 1.7 3.3-3.4'
                                    : full
                                      ? 'M11 9.4l3.8 3.2'
                                      : 'M12.9 9v3.6M11.1 10.8h3.6'
                            }
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    )}
                </svg>
                {!card && <span>{label}</span>}
            </button>
            {failed && (
                <p
                    role="alert"
                    className={cn(
                        'text-[11px] text-rz-secondary',
                        card
                            ? 'absolute right-0 bottom-full mb-1.5 w-max max-w-56 rounded-lg bg-rz-surface px-2 py-1 text-right shadow-sm'
                            : 'mt-1.5 text-center',
                    )}
                >
                    {t('investor.cart.update_failed')}
                </p>
            )}
        </div>
    );
}

/**
 * What one note returns at maturity before the earnings fee, while no quote is in hand: its price
 * plus the flat return, rounded half up per note as the cart and checkout compute it
 * (`GetInvestorCheckout::flatReturn`, `rate_pct` in tenths of a percent).
 */
export function unquotedGetBack(
    deal: Pick<C3DealCard, 'unit_price' | 'rate_pct'>,
): number {
    const price = Number(deal.unit_price.amount);

    return (
        price +
        Math.round((price * Number(deal.rate_pct.replace('.', ''))) / 1000)
    );
}

type InvestBarProps = {
    deal: C3DealCard;
    /** The server's quote for this deal, or null while it is not the focused one. */
    quote: PrimaryQuote | null;
    /** The quantity the investor has chosen; a whole number of notes. */
    units: number;
    onUnits: (units: number) => void;
    /** True while the server re-quotes a new quantity. */
    quoting: boolean;
    gate: InvestGate;
    /** Checkout for this deal and quantity, or null while the deal cannot be bought. */
    checkout: RouteLink | null;
    size: 'phone' | 'desk';
};

/**
 * The persistent invest bar (phone L650–690, desk L335–372): how many notes, of how many left,
 * the term; then what the ticket costs, its flat return and what comes back — all from the
 * server's quote for exactly that quantity — then the slider and Invest. Add to cart is on the
 * phone's swipe card and in the desk's detail panel.
 */
export function InvestBar({
    deal,
    quote,
    units,
    onUnits,
    quoting,
    gate,
    checkout,
    size,
}: InvestBarProps) {
    const { t } = useTranslation();
    const [draft, setDraft] = useState<string | null>(null);
    const current = quote;
    /* Unquoted, the notes still open bound the choice; the checkout applies the Investor's caps. */
    const max = Math.max(
        Number(current?.capacity.max_units ?? deal.units.available),
        1,
    );
    const soldOut = deal.lifecycle !== 'live' || deal.restriction !== null;
    const phone = size === 'phone';
    const micro = cn(
        'font-bold tracking-[.08em] whitespace-nowrap text-[#636e86] uppercase dark:text-rz-muted',
        phone ? 'text-[9.5px] leading-3' : 'text-[10px] leading-[13px]',
    );
    const value = cn(
        'mt-1 font-bold whitespace-nowrap tabular-nums',
        phone ? 'text-sm' : 'text-[15px]',
    );

    const commit = (raw: string) => {
        const parsed = Number.parseInt(raw.replace(/[^0-9]/gu, ''), 10);

        setDraft(null);

        if (Number.isNaN(parsed) || parsed < 1) {
            return;
        }

        onUnits(Math.min(parsed, max));
    };

    const investLabel =
        deal.lifecycle !== 'live'
            ? t(`investor.deal.lifecycle.${deal.lifecycle}`)
            : deal.restriction !== null
              ? t('investor.deals.paused')
              : t('investor.deals.invest');
    const investClass = cn(
        'flex shrink-0 items-center justify-center whitespace-nowrap',
        phone
            ? 'h-12 min-w-28 rounded-[14px] px-[18px] text-[15px] font-semibold'
            : 'h-12 min-w-[132px] rounded-[14px] px-5 text-[15.5px] font-bold',
        soldOut || checkout === null
            ? 'cursor-not-allowed bg-[#eef2f9] text-rz-secondary dark:bg-rz-surface-muted'
            : 'bg-rz-accent-fill text-white',
    );

    return (
        <section
            aria-label={t('investor.deals.invest_bar', { name: deal.name })}
            className={cn(
                'border border-rz-border bg-rz-surface',
                phone
                    ? 'mx-2 mt-0.5 rounded-[20px] px-3.5 pt-3 pb-[11px] shadow-[0_14px_34px_-16px_rgba(20,45,95,.26)]'
                    : 'mt-3.5 shrink-0 rounded-[20px] px-3.5 pt-[13px] pb-3 shadow-[0_6px_18px_-10px_rgba(20,45,95,.22)]',
            )}
        >
            <div
                className={cn(
                    'flex items-center justify-between',
                    phone ? 'gap-2.5' : 'gap-3',
                )}
            >
                <div
                    className={cn(
                        'flex min-w-0 items-center',
                        phone ? 'gap-2' : 'gap-[9px]',
                    )}
                >
                    <label
                        className={cn(
                            'flex shrink-0 items-center overflow-hidden rounded-[10px] border border-[#dfe6f2] bg-rz-surface dark:border-rz-border',
                            soldOut && 'opacity-50',
                        )}
                    >
                        <input
                            value={draft ?? String(units)}
                            onChange={(event) => setDraft(event.target.value)}
                            onBlur={(event) => commit(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    commit(event.currentTarget.value);
                                }
                            }}
                            onFocus={(event) => event.target.select()}
                            inputMode="numeric"
                            disabled={soldOut}
                            aria-label={t('investor.deals.notes_quantity')}
                            className={cn(
                                'border-0 bg-transparent p-0 text-center font-bold text-rz-ink tabular-nums outline-none',
                                phone
                                    ? 'h-7 w-[52px] text-[15px]'
                                    : 'h-[30px] w-[58px] text-base',
                            )}
                        />
                        <span
                            aria-hidden
                            className={cn(
                                'flex items-center border-l border-[#eef2f8] bg-rz-surface-sunken font-bold tracking-[.08em] whitespace-nowrap text-[#636e86] dark:border-rz-divider dark:text-rz-muted',
                                phone
                                    ? 'h-7 px-2 text-[9.5px]'
                                    : 'h-[30px] px-[9px] text-[10px]',
                            )}
                        >
                            {t('investor.deals.notes_suffix')}
                        </span>
                    </label>
                    {raiseOpen(deal.lifecycle) && (
                        <span
                            className={cn(
                                'truncate whitespace-nowrap text-[#636e86] dark:text-rz-faint',
                                phone ? 'text-[11px]' : 'text-[11.5px]',
                            )}
                        >
                            {t('investor.deals.of_left', {
                                count: formatCount(
                                    Number(deal.units.available),
                                ),
                            })}
                        </span>
                    )}
                </div>
                <div
                    className={cn(
                        'flex shrink-0 items-center',
                        phone ? 'gap-[5px]' : 'gap-1.5',
                    )}
                >
                    <span
                        className={cn(
                            'font-bold tracking-[.08em] text-[#636e86] dark:text-rz-muted',
                            phone ? 'text-[9.5px]' : 'text-[10px]',
                        )}
                    >
                        {t('investor.deals.term')}
                    </span>
                    <span
                        className={cn(
                            'font-bold whitespace-nowrap text-rz-ink',
                            phone ? 'text-[12.5px]' : 'text-[13.5px]',
                        )}
                    >
                        {t('investor.deals.months_short', {
                            count: deal.term_months,
                        })}
                    </span>
                </div>
            </div>

            <div
                aria-busy={quoting || undefined}
                className={cn(
                    'grid grid-cols-[1fr_auto_auto_auto_1fr] items-start border-t border-[#eef2f8] transition-opacity dark:border-rz-divider',
                    phone
                        ? 'mt-[11px] gap-x-0.5 pt-[11px]'
                        : 'mt-3 gap-x-[3px] pt-3',
                    quoting && 'opacity-60',
                )}
            >
                <div className="min-w-0">
                    <p className={micro}>{t('investor.deals.bar_invest')}</p>
                    <p className={cn(value, 'text-rz-ink')}>
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        {current !== null
                            ? formatAmount(current.amount)
                            : checkout !== null
                              ? formatAmount({
                                    ...deal.unit_price,
                                    amount: String(
                                        Number(deal.unit_price.amount) * units,
                                    ),
                                })
                              : '—'}
                    </p>
                </div>
                <span
                    aria-hidden
                    className={cn(
                        'self-end font-bold text-[#636e86]',
                        phone ? 'pl-1.5 text-sm' : 'pl-2 text-[15px]',
                    )}
                >
                    +
                </span>
                <div className="text-center">
                    <p className={micro}>{t('investor.deals.bar_interest')}</p>
                    <p className={cn(value, 'text-rz-accent-app-text')}>
                        {deal.rate_pct}%
                    </p>
                </div>
                <span
                    aria-hidden
                    className={cn(
                        'self-end font-bold text-[#636e86]',
                        phone ? 'pr-1.5 text-sm' : 'pr-2 text-[15px]',
                    )}
                >
                    =
                </span>
                <div className="min-w-0 text-right">
                    <p className={micro}>{t('investor.deals.bar_get_back')}</p>
                    <p className={cn(value, POSITIVE_TEXT)}>
                        <span className="mr-[3px]">
                            {t('investor.money.rwf')}
                        </span>
                        {current !== null
                            ? formatAmount(current.maturity_value)
                            : checkout !== null
                              ? formatAmount({
                                    ...deal.unit_price,
                                    amount: String(
                                        unquotedGetBack(deal) * units,
                                    ),
                                })
                              : '—'}
                    </p>
                </div>
            </div>

            <div
                className={cn(
                    'flex items-center',
                    phone ? 'mt-2.5 gap-[11px]' : 'mt-3.5 gap-3.5',
                )}
            >
                <input
                    type="range"
                    min={1}
                    max={max}
                    step={1}
                    value={Math.min(units, max)}
                    disabled={soldOut}
                    aria-label={t('investor.deals.notes_quantity')}
                    onChange={(event) => onUnits(Number(event.target.value))}
                    className={cn(RANGE_CLASS, soldOut && 'opacity-40')}
                />
                {gate.status === 'verification_required' ? (
                    <Link href={gate.link} className={investClass}>
                        {t('investor.deals.verify_to_invest')}
                    </Link>
                ) : soldOut ||
                  checkout === null ||
                  gate.status !== 'eligible' ? (
                    <button type="button" disabled className={investClass}>
                        {investLabel}
                    </button>
                ) : (
                    <Link href={checkout} className={investClass}>
                        {investLabel}
                    </Link>
                )}
            </div>
            {gate.status !== 'eligible' ? (
                <p
                    role="status"
                    className="mt-2 text-center text-[11px] leading-[1.4] text-rz-secondary"
                >
                    {t(`investor.deals.gate.${gate.status}`)}
                </p>
            ) : (
                !soldOut && (
                    <CapNote quote={current} units={units} className="mt-2" />
                )
            )}
        </section>
    );
}
