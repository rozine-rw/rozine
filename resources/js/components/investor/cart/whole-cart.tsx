import { Link, router, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { HoldClock } from '@/components/investor/deals/checkout-sheet';
import { earningsFeeRate } from '@/components/investor/primary/earnings-fee';
import { POSITIVE_TEXT } from '@/components/investor/tokens';
import { C3Notice, useRefusalText } from '@/components/rozine/c3-notice';
import { useC3Command } from '@/hooks/use-c3-command';
import type { C3Resource } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { useServerNow } from '@/lib/investor/server-clock';
import { formatRwf } from '@/lib/rozine/format';
import { readErrorCode } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { Money } from '@/types';
import type {
    C3InvestorCartProps,
    CartCheckout,
    CartFeeBand,
    CartLineRefusal,
    CartQuote,
    CartQuoteLine,
    InvestorCartLine,
} from '@/types/investor';
import type { RouteAction, RouteLink } from '@/types/routing';

const MICRO =
    'text-[9.5px] leading-3 font-bold tracking-[.08em] whitespace-nowrap text-[#636e86] uppercase dark:text-rz-muted';
const PRIMARY =
    'flex h-12 w-full items-center justify-center gap-2 rounded-[14px] text-[14.5px] font-semibold';
const PRIMARY_ON = 'bg-rz-accent-fill text-white';
const PRIMARY_OFF = 'cursor-not-allowed bg-rz-disabled text-rz-secondary';

type CartCommand = 'cart.reserve' | 'cart.confirm' | 'cart.release';

/** How often a page whose hold has ended reads the cart again until the expiry has settled. */
export const SETTLE_POLL_MS = 10_000;

/** What the last quote said: the quote, or the code it was refused with. */
export type CartQuoteState = {
    quote: CartQuote | null;
    code: string | null;
    quoting: boolean;
    /** Asks for a fresh quote of the same cart, as after a refused hold. */
    refresh: () => void;
};

/** A confirmed whole-cart purchase, as its receipt names it (contract `CART_CONFIRMED`). */
type CartConfirmed = {
    checkout_id: string;
    fee_band: { tier: CartFeeBand['tier']; rate_bps: number };
    total_principal: Money;
    lines: { campaign_id: string; commitment_id: string; principal: Money }[];
};

const tierFee = (band: { tier: CartFeeBand['tier']; rate_bps: number }) => ({
    tier: band.tier,
    rate_bps: band.rate_bps,
    basis: 'return_only' as const,
    policy_version: '',
});

/**
 * Prices the whole saved cart on the server whenever its revision changes (#383, contract
 * `QuoteInvestorCart`): the header's band and the Check out all total come from this quote, never
 * from a client calculation. A quote holds nothing, so it is asked again freely: when it expires,
 * and whenever `refresh` asks (a refused hold may mean the quote or the Investor's capital moved
 * on at the same cart revision). One still out when the cart changes is ignored. A refusal names
 * its code; a 422 (units above the limit) has no body.
 */
export function useCartQuote({
    action,
    identityRevision,
    cartRevision,
    ready,
    paused,
}: {
    action: RouteAction | null;
    identityRevision: number;
    cartRevision: number;
    /** Whether the cart may be priced at all: open lines, none filled or above its cap. */
    ready: boolean;
    /** A quantity is still being saved: the last band stays, pulsing, until the cart reloads. */
    paused: boolean;
}): CartQuoteState {
    const http = useHttp<Record<string, never>, C3ResourceLike<CartQuote>>();
    /* Each fresh quote of the same cart is its own round, so an old answer never stands for it. */
    const [round, setRound] = useState(0);
    const key = `${identityRevision}:${cartRevision}:${round}`;
    /* The newest answer, and the cart it priced; any other cart is still being priced. */
    const [answered, setAnswered] = useState<{
        key: string;
        quote: CartQuote | null;
        code: string | null;
        /** How long the quote stands, by the server's own clock; null when it does not say. */
        lasts: number | null;
    } | null>(null);
    const url = action?.url ?? null;
    const refresh = () => setRound((current) => current + 1);
    const lasts = answered?.key === key ? answered.lasts : null;

    /* An expired quote is refused on hold, so a fresh one is asked as it expires. */
    useEffect(() => {
        if (lasts === null) {
            return;
        }

        const timer = window.setTimeout(
            () => setRound((current) => current + 1),
            Math.max(0, lasts),
        );

        return () => window.clearTimeout(timer);
    }, [lasts, key]);

    useEffect(() => {
        if (action === null || !ready || paused) {
            return;
        }

        let current = true;
        let failure: string | null = null;
        const settle = (
            quote: CartQuote | null,
            code: string | null,
            lasts: number | null = null,
        ) => {
            if (current) {
                setAnswered({ key, quote, code, lasts });
            }
        };

        http.transform(() => ({
            identity_context_revision: identityRevision,
            expected_cart_revision: cartRevision,
        }));
        http.submit(action, {
            onHttpException: (response) => {
                failure = readErrorCode(response.data);
            },
        }).then(
            (answer) => {
                if (answer === undefined) {
                    settle(null, 'CART_UNITS_ABOVE_LIMIT');
                } else if (answer.status === 'completed') {
                    const lasts =
                        Date.parse(answer.data.expires_at) -
                        Date.parse(answer.server_time ?? '');

                    settle(
                        answer.data,
                        null,
                        Number.isNaN(lasts) ? null : lasts,
                    );
                } else {
                    settle(null, answer.code);
                }
            },
            () => settle(null, failure ?? 'REQUEST_FAILED'),
        );

        return () => {
            current = false;
        };
    }, [url, ready, paused, key]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!ready) {
        return { quote: null, code: null, quoting: false, refresh };
    }

    return answered?.key === key && !paused
        ? {
              quote: answered.quote,
              code: answered.code,
              quoting: false,
              refresh,
          }
        : {
              quote: answered?.quote ?? null,
              code: null,
              quoting: true,
              refresh,
          };
}

type C3ResourceLike<D> = {
    status: string;
    code: string;
    data: D;
    server_time?: string;
};

/** The lines a refused whole-cart command names, read from its `receipt.line_refusals`. */
export const readLineRefusals = (data: unknown): CartLineRefusal[] => {
    /* A C3 refusal carries its facts as `data.receipt`, as the cart commands answer. */
    const facts =
        typeof data === 'object' && data !== null && 'receipt' in data
            ? data.receipt
            : data;
    const named =
        typeof facts === 'object' && facts !== null && 'line_refusals' in facts
            ? facts.line_refusals
            : null;

    return Array.isArray(named)
        ? named.filter(
              (refusal): refusal is CartLineRefusal =>
                  typeof refusal === 'object' &&
                  refusal !== null &&
                  typeof refusal.campaign_id === 'string' &&
                  typeof refusal.code === 'string',
          )
        : [];
};

/**
 * Why one line stopped the whole cart, under that line (#383): the refusal its `line_refusals`
 * names, such as a deal that filled while the cart was held.
 */
export function LineRefusal({
    refusals,
    campaignId,
}: {
    refusals: CartLineRefusal[];
    campaignId: string;
}) {
    const refusal = useRefusalText();
    const named = refusals.find((line) => line.campaign_id === campaignId);

    return named === undefined ? null : (
        <p
            role="alert"
            data-testid={`line-refusal-${campaignId}`}
            className="mt-1 text-[11px] leading-[1.4] font-semibold text-rz-danger-text"
        >
            {refusal(named.code)}
        </p>
    );
}

/**
 * The INV-R04 band the whole cart is priced at (design L6390–6475): one bar of the notes already
 * held plus this cart, toward the next, cheaper band, and what that band would bring. The band is
 * the server's, as the quote names it.
 */
export function CartBand({
    band,
    next,
    className,
}: {
    band: CartFeeBand;
    next: CartQuote['next_fee_band'];
    className?: string;
}) {
    const { t } = useTranslation();
    const held = Number(band.held_note_principal.amount);
    const cart = Number(band.checkout_total.amount);
    const goal =
        band.next_band_at === null
            ? held + cart
            : Number(band.next_band_at.amount);
    const share = (amount: number) =>
        `${goal > 0 ? Math.min(100, (amount / goal) * 100) : 100}%`;

    return (
        <div data-testid="cart-band" className={className}>
            <div className="flex items-baseline justify-between gap-2">
                <p className={MICRO}>{t('investor.cart.whole.band_title')}</p>
                <p className="text-[12px] font-bold whitespace-nowrap text-rz-accent-app-text">
                    {earningsFeeRate(t, tierFee(band))}
                </p>
            </div>
            <div
                aria-hidden
                className="mt-1.5 flex h-1.5 overflow-hidden rounded-full bg-rz-surface-sunken"
            >
                <span
                    className="h-full bg-rz-slate/60"
                    style={{ width: share(held) }}
                />
                <span
                    className="h-full bg-rz-accent-fill"
                    style={{ width: share(cart) }}
                />
            </div>
            <p className="mt-1.5 text-[11px] leading-[1.4] text-rz-secondary">
                {band.next_band_distance !== null && next
                    ? t('investor.cart.whole.band_next', {
                          amount: formatRwf(band.next_band_distance),
                          rate: earningsFeeRate(t, tierFee(next)),
                      })
                    : t(
                          band.next_band_at === null
                              ? 'investor.cart.whole.band_top'
                              : 'investor.cart.whole.band_basis',
                      )}
            </p>
        </div>
    );
}

/**
 * What stops the whole cart from checking out before it is priced: a deal that filled, or a line
 * above what the Investor may take there now. The server refuses such a cart as a whole, so the
 * page says so rather than sending it.
 */
export const cartBlock = (
    lines: InvestorCartLine[],
): 'remove_filled' | 'fix_units' | null => {
    if (lines.some((line) => line.filled)) {
        return 'remove_filled';
    }

    return lines.some((line) => Number(line.units) > Number(line.max_units))
        ? 'fix_units'
        : null;
};

/**
 * The summary's whole-cart checkout (#383): the band, then Check out all for the quoted total,
 * which holds every line at once for one shared deadline. A refused quote says why.
 */
export function CartCheckoutAction({
    lines,
    quote,
    reserve,
    checkoutLink,
    command,
}: {
    lines: InvestorCartLine[];
    quote: CartQuoteState;
    reserve: (quote: CartQuote) => void;
    /** A deal checkout to open when the risk statements come first. */
    checkoutLink: RouteLink | null;
    command: { busy: boolean; unresolved: boolean; settling?: boolean };
}) {
    const { t } = useTranslation();
    const refusal = useRefusalText();
    const block = cartBlock(lines);
    const priced = quote.quote;
    /* Busy until the held checkout has loaded, so the button never reads as idle in between. */
    const sending = command.busy || command.settling === true;
    const idle = !sending && !command.unresolved;
    const canCheckOut =
        block === null && priced !== null && !quote.quoting && idle;

    return (
        <div className="mt-[11px] border-t border-rz-divider pt-2.5">
            {priced !== null ? (
                <CartBand
                    band={priced.fee_band}
                    next={priced.next_fee_band}
                    className={cn(quote.quoting && 'animate-pulse')}
                />
            ) : (
                block === null &&
                quote.code === null && (
                    <div
                        data-testid="cart-band-loading"
                        aria-label={t('investor.cart.whole.quoting')}
                        className="h-[46px] animate-pulse rounded-[10px] bg-rz-surface-sunken"
                    />
                )
            )}
            {block !== null ? (
                <p
                    role="status"
                    className="text-[11.5px] font-semibold text-rz-warning-text"
                >
                    {t(`investor.cart.whole.${block}`)}
                </p>
            ) : (
                quote.code !== null && (
                    <p
                        role="status"
                        className="text-[11.5px] leading-[1.4] font-semibold text-rz-ink"
                    >
                        {quote.code === 'RISK_ACKNOWLEDGEMENT_REQUIRED'
                            ? t('investor.cart.whole.risks_first')
                            : refusal(quote.code)}{' '}
                        {quote.code === 'RISK_ACKNOWLEDGEMENT_REQUIRED' &&
                            checkoutLink !== null && (
                                <Link
                                    href={checkoutLink}
                                    className="text-rz-accent-text underline"
                                >
                                    {t('investor.cart.whole.open_checkout')}
                                </Link>
                            )}
                    </p>
                )
            )}
            <button
                type="button"
                disabled={!canCheckOut}
                aria-busy={sending || undefined}
                onClick={() => priced !== null && reserve(priced)}
                className={cn(
                    PRIMARY,
                    'mt-2.5',
                    canCheckOut ? PRIMARY_ON : PRIMARY_OFF,
                )}
            >
                {sending
                    ? t('investor.cart.whole.holding')
                    : quote.quoting
                      ? /* A total from before the cart changed is never offered (#531). */
                        t('investor.cart.whole.quoting')
                      : t('investor.cart.whole.checkout', {
                            amount: formatRwf(
                                priced?.principal ?? {
                                    currency: 'RWF',
                                    amount: '0',
                                },
                            ),
                        })}
            </button>
            <p className="mt-1.5 text-center text-[10px] leading-[1.4] text-rz-secondary">
                {t('investor.cart.whole.fine_print')}
            </p>
        </div>
    );
}

/**
 * The whole-cart command (#383) through the shared C3 command: reserve the quoted cart, then
 * confirm or release the held checkout as a whole. A confirmed checkout's receipt is kept for the
 * done view, since the page reloads without the lines it bought; a re-quote is remembered so the
 * held view asks for a fresh confirmation.
 */
export function useCartCommand({
    identity_context_revision: identityRevision,
    actions,
    checkout,
    operation,
    names,
    requote,
}: Pick<
    C3InvestorCartProps,
    'identity_context_revision' | 'actions' | 'checkout' | 'operation'
> & {
    /** The deal names on the page now, kept with a confirmed receipt for the lines it removes. */
    names: Record<string, string>;
    /** Asks for a fresh quote: a refused hold may mean the quote no longer stands. */
    requote: () => void;
}) {
    const [confirmed, setConfirmed] = useState<{
        receipt: CartConfirmed;
        names: Record<string, string>;
    } | null>(null);
    const [requoted, setRequoted] = useState(false);
    /* The lines the last refusal named, shown under each; a new command clears them. */
    const [lineRefusals, setLineRefusals] = useState<CartLineRefusal[]>([]);
    const routes: Record<CartCommand, RouteAction | null> = {
        'cart.reserve': actions.reserve,
        'cart.confirm': checkout?.actions.confirm ?? null,
        'cart.release': checkout?.actions.release ?? null,
    };
    const command = useC3Command<CartCommand>({
        actions: routes,
        lookup: operation,
        lookupQuery: {
            identity_context_revision: identityRevision,
            ...(checkout === null ? {} : { checkout: checkout.id }),
        },
        allowed: (Object.keys(routes) as CartCommand[]).filter(
            (name) => routes[name] !== null,
        ),
        onRefused: (_code, _status, data) => {
            setLineRefusals(readLineRefusals(data));
            requote();
        },
        onCompleted: (resource: C3Resource) => {
            setLineRefusals([]);
            setRequoted(resource.code === 'CART_REQUOTED');

            if (resource.code === 'CART_CONFIRMED') {
                setConfirmed({
                    receipt: resource.data?.receipt as CartConfirmed,
                    names,
                });
            }
        },
    });

    const send: typeof command.send = (name, payload) => {
        setLineRefusals([]);

        return command.send(name, payload);
    };
    const reserve = (quote: CartQuote) =>
        send('cart.reserve', {
            identity_context_revision: identityRevision,
            expected_cart_revision: quote.cart_revision,
            quote_id: quote.id,
            expected_quote_revision: quote.revision,
            quote_disclosure_sha256: quote.disclosure_sha256,
        });

    return {
        command: { ...command, send },
        reserve,
        confirmed,
        requoted,
        lineRefusals,
    };
}

/**
 * The held whole-cart checkout (#383): every line held at one band until one shared deadline, the
 * total held from Available, and Confirm all once the Investor acknowledges these exact terms — or
 * Release all. A re-quote changes the terms, so it asks for the acknowledgement again. Past its
 * deadline the checkout only waits for its expiry to settle.
 */
export function HeldCart({
    checkout,
    names,
    serverTime,
    identityRevision,
    cart,
    deals,
}: {
    checkout: CartCheckout;
    names: Record<string, string>;
    serverTime: string;
    identityRevision: number;
    cart: ReturnType<typeof useCartCommand>;
    /** Where an Investor may go while an ended hold settles. */
    deals: RouteLink | null;
}) {
    const { t } = useTranslation();
    const { command, requoted, lineRefusals } = cart;
    const now = useServerNow(serverTime, true);
    /*
     * Past its deadline the hold can no longer be confirmed or released, only expire (#531): the
     * page says so as the clock runs out, and reads the cart again until the expiry has settled
     * and the cart is back.
     */
    const ended =
        checkout.state !== 'held' || now >= Date.parse(checkout.expires_at);

    useEffect(() => {
        if (!ended) {
            return;
        }

        const timer = window.setInterval(() => router.reload(), SETTLE_POLL_MS);

        return () => window.clearInterval(timer);
    }, [ended]);
    /* Which command is on its way, so its own button says what it is doing. */
    const [sent, setSent] = useState<'cart.confirm' | 'cart.release' | null>(
        null,
    );
    const sendHeld = (
        name: 'cart.confirm' | 'cart.release',
        payload: Record<string, unknown>,
    ) => {
        setSent(name);
        command.send(name, payload);
    };
    const { quote } = checkout;
    /* The acknowledgement binds the exact reserved terms: each line's own disclosure too. */
    const key = [
        checkout.revision,
        quote.disclosure_sha256,
        ...checkout.lines.map((line) => line.disclosure_sha256),
    ].join(':');
    const quotedLines: Record<string, CartQuoteLine> = Object.fromEntries(
        quote.lines.map((line) => [line.cart_line_id, line]),
    );
    const [acknowledgedKey, setAcknowledgedKey] = useState<string | null>(null);
    const acknowledged = acknowledgedKey === key;
    const sending = command.busy || command.settling;
    const idle = !sending && !command.unresolved;
    const confirm = ended ? null : checkout.actions.confirm;
    const release = ended ? null : checkout.actions.release;
    const canConfirm = confirm !== null && acknowledged && idle;

    return (
        <section
            aria-labelledby="held-cart-title"
            className="rounded-[18px] border border-rz-border bg-rz-surface p-3.5 shadow-[0_12px_30px_-20px_rgba(20,45,95,.28)]"
        >
            <h2
                id="held-cart-title"
                className="text-[16px] font-semibold text-rz-ink"
            >
                {t('investor.cart.whole.held_title')}
            </h2>
            <p className="mt-1 text-[12px] leading-[1.45] text-rz-secondary">
                {t('investor.cart.whole.held_lead')}
            </p>
            <div className="mt-2.5">
                {ended ? (
                    <div
                        role="status"
                        className="rounded-[10px] bg-[#fdeaea] px-3 py-2 text-center text-[12px] text-rz-danger-text dark:bg-rz-danger-tint"
                    >
                        <p className="font-semibold">
                            {t('investor.cart.whole.settling')}
                        </p>
                        <p className="mt-1 leading-[1.45]">
                            {t('investor.cart.whole.settling_wait')}
                        </p>
                    </div>
                ) : (
                    <HoldClock
                        expiresAt={checkout.expires_at}
                        serverTime={serverTime}
                    />
                )}
            </div>
            {requoted && (
                <p
                    role="alert"
                    className="mt-2.5 rounded-[10px] border border-rz-border px-3 py-2 text-[12px] leading-[1.45] font-semibold text-rz-ink"
                >
                    {t('investor.cart.whole.requoted')}
                </p>
            )}
            <C3Notice command={command} className="mt-2.5" />
            <ul className="mt-1.5">
                {checkout.lines.map((line) => (
                    <li
                        key={line.cart_line_id}
                        data-testid={`held-line-${line.campaign_id}`}
                        className="flex items-start justify-between gap-3 border-b border-rz-divider py-2"
                    >
                        <div className="min-w-0">
                            <p className="truncate text-[13.5px] font-semibold text-rz-ink">
                                {names[line.campaign_id] ?? line.campaign_id}
                            </p>
                            <p className="mt-0.5 text-[11px] text-rz-secondary">
                                {t('investor.cart.whole.line', {
                                    count: Number(
                                        quotedLines[line.cart_line_id].units,
                                    ),
                                    rate: quotedLines[line.cart_line_id].terms
                                        .rate_pct,
                                })}
                            </p>
                            <p className="mt-0.5 text-[11px] text-rz-secondary">
                                {t('investor.cart.whole.line_return', {
                                    return: formatRwf(line.contractual_return),
                                    fee: formatRwf(line.earnings_fee),
                                })}
                            </p>
                            <LineRefusal
                                refusals={lineRefusals}
                                campaignId={line.campaign_id}
                            />
                        </div>
                        <p className="shrink-0 text-[13.5px] font-bold text-rz-ink tabular-nums">
                            {formatRwf(line.principal)}
                        </p>
                    </li>
                ))}
            </ul>
            <div className="mt-2 flex justify-between gap-3 text-[12.5px]">
                <span className="text-rz-secondary">
                    {t('investor.checkout.c3.held_amount')}
                </span>
                <span
                    data-testid="held-total"
                    className="font-bold text-rz-ink tabular-nums"
                >
                    {formatRwf(checkout.total_held)}
                </span>
            </div>
            <div className="mt-1 flex justify-between gap-3 text-[12.5px]">
                <span className="text-rz-secondary">
                    {t('investor.cart.whole.net_return')}
                </span>
                <span className={cn('font-bold tabular-nums', POSITIVE_TEXT)}>
                    {formatRwf(checkout.reserved.net_return)}
                </span>
            </div>
            <CartBand band={quote.fee_band} next={null} className="mt-3" />
            {confirm !== null && (
                <label className="mt-3 flex items-start gap-2.5 text-[12px] leading-[1.45] text-rz-ink">
                    <input
                        type="checkbox"
                        checked={acknowledged}
                        onChange={(event) =>
                            setAcknowledgedKey(
                                event.target.checked ? key : null,
                            )
                        }
                        className="mt-0.5 size-4 shrink-0 accent-rz-accent-fill"
                    />
                    <span>{t('investor.cart.whole.acknowledge')}</span>
                </label>
            )}
            {confirm !== null && (
                <button
                    type="button"
                    disabled={!canConfirm}
                    aria-busy={
                        (sending && sent === 'cart.confirm') || undefined
                    }
                    onClick={() =>
                        sendHeld('cart.confirm', {
                            identity_context_revision: identityRevision,
                            checkout_id: checkout.id,
                            expected_revision: checkout.revision,
                            quote_id: quote.id,
                            expected_quote_revision: quote.revision,
                            acknowledged_disclosure_sha256:
                                quote.disclosure_sha256,
                            acknowledged: true,
                        })
                    }
                    className={cn(
                        PRIMARY,
                        'mt-3',
                        canConfirm ? PRIMARY_ON : PRIMARY_OFF,
                    )}
                >
                    {sending && sent === 'cart.confirm'
                        ? t('investor.cart.whole.confirming')
                        : t('investor.cart.whole.confirm', {
                              amount: formatRwf(checkout.total_held),
                          })}
                </button>
            )}
            {release !== null && (
                <button
                    type="button"
                    disabled={!idle}
                    aria-busy={
                        (sending && sent === 'cart.release') || undefined
                    }
                    onClick={() =>
                        sendHeld('cart.release', {
                            identity_context_revision: identityRevision,
                            checkout_id: checkout.id,
                            expected_revision: checkout.revision,
                        })
                    }
                    className="mt-2 h-10 w-full rounded-xl border border-rz-border bg-rz-surface text-[13px] font-semibold text-rz-slate disabled:opacity-60"
                >
                    {sending && sent === 'cart.release'
                        ? t('investor.cart.whole.releasing')
                        : t('investor.cart.whole.release')}
                </button>
            )}
            {ended && deals !== null && (
                <Link
                    href={deals}
                    className="mt-3 flex h-10 w-full items-center justify-center rounded-xl border border-rz-border text-[13px] font-semibold text-rz-slate"
                >
                    {t('investor.cart.whole.go_deals')}
                </Link>
            )}
            <p className="mt-2 text-center text-[10px] leading-[1.4] text-rz-secondary">
                {t('investor.checkout.c3.confirm_fine_print')}
            </p>
        </section>
    );
}

/**
 * The whole cart is bought (design's done state): each deal with its amount and the one fee rate
 * it was bought at, then the way on to Portfolio or back to Deals. Notes are issued after each
 * business is paid, so they read as commitments.
 */
export function CartCheckedOut({
    receipt,
    names,
    links,
}: {
    receipt: CartConfirmed;
    names: Record<string, string>;
    links: { portfolio: RouteLink | null; deals: RouteLink | null };
}) {
    const { t } = useTranslation();
    const fee = earningsFeeRate(t, tierFee(receipt.fee_band));

    return (
        <section
            role="status"
            aria-labelledby="cart-done-title"
            className="rounded-[18px] border border-rz-border bg-rz-surface p-3.5"
        >
            <h2
                id="cart-done-title"
                className="text-[16px] font-semibold text-rz-ink"
            >
                {t('investor.cart.whole.done_title')}
            </h2>
            <p className="mt-1 text-[12px] leading-[1.45] text-rz-secondary">
                {t('investor.cart.whole.done_body', {
                    count: receipt.lines.length,
                    amount: formatRwf(receipt.total_principal),
                })}
            </p>
            <ul className="mt-2">
                {receipt.lines.map((line) => (
                    <li
                        key={line.commitment_id}
                        className="flex justify-between gap-3 border-b border-rz-divider py-2 text-[13px]"
                    >
                        <span className="min-w-0">
                            <span className="block truncate font-semibold text-rz-ink">
                                {names[line.campaign_id] ?? line.campaign_id}
                            </span>
                            <span className="block text-[11px] text-rz-secondary">
                                {t('investor.cart.whole.done_fee', {
                                    rate: fee,
                                })}
                            </span>
                        </span>
                        <span className="shrink-0 font-bold text-rz-ink tabular-nums">
                            {formatRwf(line.principal)}
                        </span>
                    </li>
                ))}
            </ul>
            <div className="mt-3 flex gap-2">
                {links.portfolio !== null && (
                    <Link
                        href={links.portfolio}
                        className={cn(
                            PRIMARY,
                            PRIMARY_ON,
                            'h-10 flex-1 text-[13px]',
                        )}
                    >
                        {t('investor.cart.whole.go_portfolio')}
                    </Link>
                )}
                {links.deals !== null && (
                    <Link
                        href={links.deals}
                        className="flex h-10 flex-1 items-center justify-center rounded-[14px] border border-rz-border text-[13px] font-semibold text-rz-slate"
                    >
                        {t('investor.cart.whole.go_deals')}
                    </Link>
                )}
            </div>
        </section>
    );
}
