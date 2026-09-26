import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { DealStatusNotice } from '@/components/investor/deals/deal-status';
import { CapNote } from '@/components/investor/deals/invest-bar';
import { useTimeLeft } from '@/components/investor/deals/time-left';
import { useQuotedUnits } from '@/components/investor/deals/use-quote';
import { CommitmentCard } from '@/components/investor/primary/commitment';
import {
    formatOrdinals,
    RightsTable,
} from '@/components/investor/primary/rights';
import { ACCENT_FILL, POSITIVE_TEXT } from '@/components/investor/tokens';
import { C3Notice, useRefusalText } from '@/components/rozine/c3-notice';
import { Icon } from '@/components/rozine/icon';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { formatCompactBare } from '@/lib/investor/format';
import { useServerNow } from '@/lib/investor/server-clock';
import { formatAmount, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3InvestorCheckoutProps,
    InvestorAllowedAction,
} from '@/types/investor';

type CheckoutSheetProps = Omit<C3InvestorCheckoutProps, 'home'> & {
    variant: 'phone' | 'desk';
};

const ROW = 'flex justify-between gap-2.5 py-[7px]';
const ROW_LINE = 'border-b border-[#eef2f9] dark:border-rz-divider';
const LABEL = 'text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase';

function CloseLink({
    href,
    variant,
}: {
    href: C3InvestorCheckoutProps['links']['close'];
    variant: 'phone' | 'desk';
}) {
    const { t } = useTranslation();

    return (
        <Link
            href={href}
            aria-label={t('app.sheet.close')}
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[10px] bg-[#eef2f8] text-rz-secondary dark:bg-rz-surface-muted',
                variant === 'phone'
                    ? 'size-[34px] text-base'
                    : 'size-8 text-[15px]',
            )}
        >
            <span aria-hidden>✕</span>
        </Link>
    );
}

/** The reservation's hold, counted down against the server clock (5 minutes at most, §11.3). */
function HoldClock({
    expiresAt,
    serverTime,
}: {
    expiresAt: string;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const now = useServerNow(serverTime, true);
    const seconds = Math.max(
        0,
        Math.floor((Date.parse(expiresAt) - now) / 1000),
    );
    const pad = (value: number) => String(value).padStart(2, '0');

    return (
        <p
            role="timer"
            aria-live="off"
            className={cn(
                'rounded-[10px] px-3 py-2 text-center text-[12px] font-semibold tabular-nums',
                seconds === 0
                    ? 'bg-[#fdeaea] text-rz-danger-text dark:bg-rz-danger-tint'
                    : 'bg-rz-accent-soft text-rz-accent-app-text',
            )}
        >
            {seconds === 0
                ? t('investor.checkout.c3.hold_ended')
                : t('investor.checkout.c3.hold_left', {
                      time: `${pad(Math.floor(seconds / 60))}:${pad(seconds % 60)}`,
                  })}
        </p>
    );
}

/**
 * Checkout (MVP-INVESTOR-SCR-03; phone L4782–4939, desktop L247–324), as C3's two steps (v2 §2c).
 * First the quantity and the server's indicative quote, then Reserve, which holds exact unit
 * ordinals for five minutes. Then the reserved units' exact rights, the disclosure to acknowledge,
 * and Confirm — or Release. A confirmed commitment is shown as such, "issued after disbursement",
 * never as a Holding. There is no maturity date before issue. Every command goes through the
 * operation lookup; nothing is resent on its own and nothing is drawn before the server says so.
 */
export function CheckoutSheet({
    server_time: serverTime,
    identity_context_revision: identityRevision,
    allowed_actions: allowed,
    deal,
    quote,
    quick_picks: quickPicks,
    wallet,
    disclosure,
    reservation,
    commitment,
    refusal,
    links,
    actions,
    preview_outcome: preview,
    variant,
}: CheckoutSheetProps) {
    const { t } = useTranslation();
    const refusalText = useRefusalText();
    const clock = useTimeLeft(deal.clock.expires_at, serverTime);
    const quoted = useQuotedUnits(Number(quote.units), {}, [
        'quote',
        'wallet',
        'refusal',
    ]);
    const [acknowledged, setAcknowledged] = useState(false);
    const command = useC3Command<InvestorAllowedAction>({
        actions: {
            'primary.reserve': actions.reserve,
            'primary.confirm': actions.confirm,
            'primary.release': actions.release,
        },
        lookup: links.operation,
        lookupQuery: { identity_context_revision: identityRevision },
        allowed,
        preview,
    });
    const phone = variant === 'phone';
    const held =
        reservation !== null && reservation.state === 'held'
            ? reservation
            : null;
    const max = Number(quote.capacity.max_units);
    const idle = !command.busy && !command.unresolved;
    const offers = (action: InvestorAllowedAction) => allowed.includes(action);

    const pick = (units: number) => quoted.setUnits(units);

    const reserve = () =>
        command.send('primary.reserve', {
            identity_context_revision: identityRevision,
            campaign_id: deal.campaign_id,
            units: String(quoted.units),
            expected_campaign_revision: deal.revision,
            quote_revision: quote.revision,
        });

    const shell = (label: string, children: ReactNode) => (
        <>
            <Link
                href={links.close}
                aria-hidden
                tabIndex={-1}
                className={cn(
                    'z-[62] animate-[rz-scrim_.2s_ease_both]',
                    phone
                        ? 'fixed inset-0 bg-[rgba(8,14,28,.5)] backdrop-blur-[3px]'
                        : 'absolute inset-0 rounded-2xl bg-[rgba(8,14,28,.28)] backdrop-blur-[6px]',
                )}
            />
            <section
                role="dialog"
                aria-modal="true"
                aria-label={label}
                className={cn(
                    'z-[63] flex flex-col bg-[#f6f8fc] dark:bg-rz-page',
                    phone
                        ? 'fixed inset-x-0 bottom-0 max-h-[94%] animate-[rz-sheetup_.34s_cubic-bezier(.22,1,.36,1)] rounded-[20px_26px_0_0] shadow-[0_-22px_60px_-18px_rgba(8,14,28,.5)]'
                        : 'absolute top-1/2 left-1/2 h-[min(548px,calc(100%-26px))] min-h-80 w-[376px] -translate-x-1/2 -translate-y-1/2 animate-[rz-copop_.34s_cubic-bezier(.16,1,.3,1)_both] overflow-hidden rounded-[20px] border border-[#e2e8f2] shadow-[0_26px_64px_-20px_rgba(8,14,28,.5)] dark:border-rz-border',
                )}
            >
                <div
                    className={cn(
                        'flex shrink-0 items-center justify-between',
                        phone ? 'px-[18px] pt-[18px]' : 'px-4 pt-3.5',
                    )}
                >
                    <h2
                        className={cn(
                            'font-semibold text-rz-ink',
                            phone ? 'text-lg' : 'text-[17px]',
                        )}
                    >
                        {label}
                    </h2>
                    <CloseLink href={links.close} variant={variant} />
                </div>
                {children}
            </section>
        </>
    );

    if (commitment !== null) {
        return shell(
            t('investor.checkout.c3.committed'),
            <>
                <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-4 pt-3 pb-4">
                    <p
                        role="status"
                        className="mb-3 rounded-xl bg-rz-accent-soft px-3 py-2.5 text-[12.5px] leading-[1.5] text-rz-accent-app-text"
                    >
                        {t('investor.checkout.c3.committed_body')}
                    </p>
                    <CommitmentCard commitment={commitment} />
                </div>
                <div className="shrink-0 px-4 pt-1.5 pb-[calc(env(safe-area-inset-bottom)+20px)] lg:pb-4">
                    <Link
                        href={links.portfolio}
                        className="flex h-[50px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                    >
                        {t('investor.checkout.c3.view_awaiting')}
                    </Link>
                    <Link
                        href={links.deals}
                        className="mt-2.5 flex h-11 w-full items-center justify-center rounded-2xl border border-rz-border text-sm font-semibold text-rz-slate"
                    >
                        {t('investor.checkout.explore')}
                    </Link>
                </div>
            </>,
        );
    }

    const dealLine = (
        <div className="flex items-center gap-[11px] rounded-2xl border border-rz-border bg-rz-surface px-[11px] py-[9px]">
            <span
                className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] text-base font-semibold text-white"
                style={{ background: ACCENT_FILL[deal.accent] }}
            >
                {deal.name.charAt(0)}
            </span>
            <div className="min-w-0 flex-1">
                <p className="truncate text-[14.5px] font-semibold text-rz-ink">
                    {deal.name}
                </p>
                <p className="mt-px text-[11.5px] text-rz-secondary">
                    {t('investor.checkout.deal_line', {
                        rate: deal.rate_pct,
                        count: deal.term_months,
                    })}
                </p>
            </div>
            {clock.clock && (
                <div className="shrink-0 text-right">
                    <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('investor.checkout.closes_in')}
                    </p>
                    <p className="mt-px text-[13px] font-bold text-rz-danger-text tabular-nums">
                        {clock.label}
                    </p>
                </div>
            )}
        </div>
    );

    const quoteRows = (
        <dl
            aria-busy={quoted.quoting || undefined}
            className={cn(
                'mt-[11px] rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-0.5 transition-opacity',
                quoted.quoting && 'opacity-60',
            )}
        >
            <div className={cn(ROW, ROW_LINE)}>
                <dt className="text-[12.5px] text-rz-secondary">
                    {t('investor.checkout.expected_return', {
                        rate: quote.rate_pct,
                    })}
                </dt>
                <dd
                    className={cn('text-[12.5px] font-semibold', POSITIVE_TEXT)}
                >
                    +{formatRwf(quote.expected_return)}
                </dd>
            </div>
            <div className={cn(ROW, ROW_LINE)}>
                <dt className="text-[12.5px] text-rz-secondary">
                    {t('investor.checkout.payout_fee')}
                </dt>
                <dd className="text-[12.5px] font-semibold text-rz-ink">
                    {formatRwf(quote.payout_fee)}
                </dd>
            </div>
            <div className={cn(ROW, ROW_LINE)}>
                <dt className="text-[12.5px] text-rz-secondary">
                    {t('investor.checkout.c3.at_maturity', {
                        count: quote.term_months,
                    })}
                </dt>
                <dd className="text-[12.5px] font-semibold text-rz-ink">
                    {formatRwf(quote.maturity_value)}
                </dd>
            </div>
            <div className={ROW}>
                <dt className="text-[12.5px] text-rz-secondary">
                    {t('investor.checkout.maturity_date')}
                </dt>
                <dd className="text-[12.5px] font-semibold text-rz-secondary">
                    {t('investor.primary.maturity_at_issue')}
                </dd>
            </div>
        </dl>
    );

    const shortWallet = !wallet.sufficient && (
        <div
            role="alert"
            className="mt-[7px] flex items-center gap-[7px] rounded-[10px] border border-[#f6d6d7] bg-[#fdeaea] px-[11px] py-2 dark:border-[rgba(255,107,111,.25)] dark:bg-rz-danger-tint"
        >
            <span className="text-xs">
                <Icon name="warning" tone="red" />
            </span>
            <span className="flex-1 text-[11.5px] leading-[1.4] text-[#c0464b] dark:text-rz-danger-text">
                {t('investor.checkout.insufficient')}
            </span>
            <Link
                href={links.deposit}
                className="shrink-0 text-[11.5px] font-bold text-rz-accent-app-text"
            >
                {t('investor.checkout.deposit')}
            </Link>
        </div>
    );

    const pageRefusal = refusal !== null && (
        <div
            role="alert"
            className="mt-2.5 flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-[13px] py-[11px] text-[12.5px] font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
        >
            <Icon name="warning" tone="red" />
            {refusalText(refusal.code)}
        </div>
    );

    const footer = (children: ReactNode) => (
        <div
            className={cn(
                'shrink-0',
                phone
                    ? 'px-4 pt-2.5 pb-[calc(env(safe-area-inset-bottom)+24px)]'
                    : 'px-4 pt-2.5 pb-4',
            )}
        >
            {children}
        </div>
    );

    const busyLabel = (label: string) => (
        <>
            {command.busy && (
                <span className="size-[17px] animate-spin rounded-full border-[2.5px] border-white/40 border-t-white" />
            )}
            {command.busy ? t('investor.checkout.c3.processing') : label}
        </>
    );

    if (held !== null) {
        const confirm = (event: FormEvent) => {
            event.preventDefault();
            command.send('primary.confirm', {
                identity_context_revision: identityRevision,
                reservation_id: held.id,
                expected_reservation_revision: held.revision,
                disclosure_version: disclosure.version,
                disclosure_sha256: disclosure.sha256,
                acknowledged: true,
            });
        };
        const release = () =>
            command.send('primary.release', {
                identity_context_revision: identityRevision,
                reservation_id: held.id,
                expected_reservation_revision: held.revision,
            });
        const canConfirm =
            offers('primary.confirm') &&
            actions.confirm !== null &&
            acknowledged &&
            idle;
        const canRelease =
            offers('primary.release') && actions.release !== null;

        return shell(
            t('investor.checkout.c3.reserved_title'),
            <form onSubmit={confirm} className="flex min-h-0 flex-1 flex-col">
                <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-4 pt-2.5 pb-1">
                    <C3Notice command={command} />
                    {dealLine}
                    <div className="mt-[11px]">
                        <HoldClock
                            expiresAt={held.clock.expires_at}
                            serverTime={serverTime}
                        />
                    </div>
                    <dl className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-0.5">
                        <div className={cn(ROW, ROW_LINE)}>
                            <dt className="text-[12.5px] text-rz-secondary">
                                {t('investor.primary.units')}
                            </dt>
                            <dd className="text-right text-[12.5px] font-semibold text-rz-ink">
                                {t('investor.primary.units_value', {
                                    count: Number(held.units),
                                    ordinals: formatOrdinals(held.ordinals),
                                })}
                            </dd>
                        </div>
                        <div className={ROW}>
                            <dt className="text-[12.5px] text-rz-secondary">
                                {t('investor.checkout.c3.held_amount')}
                            </dt>
                            <dd className="text-[12.5px] font-semibold text-rz-ink">
                                {formatRwf(held.amount)}
                            </dd>
                        </div>
                    </dl>
                    {quoteRows}
                    <RightsTable rights={held.rights} className="mt-[11px]" />
                    <p className={cn(LABEL, 'mt-[11px]')}>
                        {t('investor.checkout.disclosure')}
                    </p>
                    <ul className="mt-[7px] flex flex-col gap-1.5 rounded-xl border border-[rgba(30,58,255,.10)] bg-[rgba(30,58,255,.06)] px-[13px] py-[11px] dark:border-rz-border dark:bg-rz-accent-soft">
                        {disclosure.points.map((point) => (
                            <li
                                key={point}
                                className="flex gap-2 text-[11.5px] leading-normal text-rz-slate"
                            >
                                <span
                                    aria-hidden
                                    className="mt-[7px] size-1 shrink-0 rounded-full bg-rz-slate"
                                />
                                {point}
                            </li>
                        ))}
                    </ul>
                    {pageRefusal}
                </div>
                {footer(
                    <>
                        <label className="mb-2.5 flex cursor-pointer items-start gap-2.5">
                            <input
                                type="checkbox"
                                checked={acknowledged}
                                onChange={(event) =>
                                    setAcknowledged(event.target.checked)
                                }
                                className="peer sr-only"
                            />
                            <span
                                aria-hidden
                                className="mt-px flex size-5 shrink-0 items-center justify-center rounded-[8px] border-2 border-[#d3dae6] bg-rz-surface text-xs text-white peer-checked:border-rz-accent-fill peer-checked:bg-rz-accent-fill peer-focus-visible:ring-2 peer-focus-visible:ring-rz-focus-border dark:border-rz-border"
                            >
                                {acknowledged ? '✓' : ''}
                            </span>
                            <span className="text-[11.5px] leading-[1.45] text-rz-slate">
                                {t('investor.checkout.acknowledge', {
                                    version: disclosure.version,
                                })}
                            </span>
                        </label>
                        {offers('primary.confirm') &&
                            actions.confirm !== null && (
                                <button
                                    type="submit"
                                    disabled={!canConfirm}
                                    aria-busy={command.busy || undefined}
                                    className={cn(
                                        'flex h-[52px] w-full items-center justify-center gap-2 rounded-2xl text-[15.5px] font-semibold',
                                        canConfirm
                                            ? 'bg-rz-accent-fill text-white'
                                            : 'cursor-not-allowed bg-rz-disabled text-rz-secondary',
                                    )}
                                >
                                    {busyLabel(
                                        t('investor.checkout.confirm', {
                                            amount: formatRwf(held.amount),
                                        }),
                                    )}
                                </button>
                            )}
                        {canRelease && (
                            <button
                                type="button"
                                onClick={release}
                                disabled={!idle}
                                className="mt-2 flex h-11 w-full items-center justify-center rounded-2xl border border-rz-border text-sm font-semibold text-rz-slate disabled:opacity-60"
                            >
                                {t('investor.checkout.c3.release')}
                            </button>
                        )}
                        <p className="mt-2 text-center text-[10px] leading-[1.4] text-rz-secondary">
                            {t('investor.checkout.c3.confirm_fine_print')}
                        </p>
                    </>,
                )}
            </form>,
        );
    }

    const canReserve =
        offers('primary.reserve') &&
        deal.lifecycle === 'live' &&
        deal.restriction === null &&
        max >= 1 &&
        wallet.sufficient &&
        !quoted.quoting &&
        idle;

    return shell(
        t('investor.checkout.title'),
        <div className="flex min-h-0 flex-1 flex-col">
            <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-4 pt-2.5 pb-1">
                <C3Notice command={command} />
                <DealStatusNotice deal={deal} className="mb-[11px]" />
                {dealLine}
                <div className="mt-[11px] flex items-center justify-between">
                    <span className={LABEL}>
                        {t('investor.checkout.amount')}
                    </span>
                    <span className="text-[11px] font-semibold text-rz-secondary">
                        {t(
                            quote.units === '1'
                                ? 'investor.checkout.units_each_one'
                                : 'investor.checkout.units_each_other',
                            {
                                count: Number(quote.units),
                                price: formatRwf(quote.unit_price),
                            },
                        )}
                    </span>
                </div>
                <div className="mt-[7px] flex items-stretch gap-[7px]">
                    <button
                        type="button"
                        aria-label={t('investor.deal.fewer_notes')}
                        disabled={quoted.units <= 1}
                        onClick={() => pick(quoted.units - 1)}
                        className="w-[46px] shrink-0 rounded-xl border border-rz-border bg-rz-surface text-[21px] text-rz-ink disabled:opacity-40"
                    >
                        <span aria-hidden>−</span>
                    </button>
                    <div className="flex min-w-0 flex-1 items-center gap-2 rounded-xl border-[1.5px] border-rz-accent-fill bg-rz-surface px-[13px] py-2">
                        <span className="text-[12.5px] font-semibold text-rz-secondary">
                            {t('investor.money.rwf')}
                        </span>
                        <output
                            aria-live="polite"
                            aria-label={t('investor.checkout.amount')}
                            className={cn(
                                'min-w-0 flex-1 text-right text-[21px] font-bold tracking-[-.3px] text-rz-ink tabular-nums transition-opacity',
                                quoted.quoting && 'opacity-60',
                            )}
                        >
                            {formatAmount(quote.amount)}
                        </output>
                    </div>
                    <button
                        type="button"
                        aria-label={t('investor.deal.more_notes')}
                        disabled={quoted.units >= max}
                        onClick={() => pick(quoted.units + 1)}
                        className="w-[46px] shrink-0 rounded-xl border border-rz-border bg-rz-surface text-[21px] text-rz-ink disabled:opacity-40"
                    >
                        <span aria-hidden>+</span>
                    </button>
                </div>
                <div className="mt-[7px] flex gap-1.5">
                    {quickPicks.map((option) => {
                        const units = Number(option.units);
                        const on = units === quoted.units;

                        return (
                            <button
                                key={option.units}
                                type="button"
                                aria-pressed={on}
                                disabled={units > max}
                                onClick={() => pick(units)}
                                className={cn(
                                    'flex-1 rounded-[10px] border py-2 text-xs font-semibold disabled:opacity-40',
                                    on
                                        ? 'border-rz-accent-fill bg-rz-accent-fill text-white'
                                        : 'border-rz-border bg-rz-surface text-rz-slate',
                                )}
                            >
                                {formatCompactBare(option.amount)}
                            </button>
                        );
                    })}
                </div>
                <CapNote quote={quote} units={quoted.units} className="mt-2" />
                <p className="mt-[11px] text-[11px] leading-[1.45] text-rz-secondary">
                    {t('investor.checkout.c3.indicative')}
                </p>
                {quoteRows}
                <p className="mt-[7px] text-center text-[11px] text-rz-secondary">
                    {t('investor.checkout.wallet_detail', {
                        amount: formatRwf(wallet.available),
                    })}
                </p>
                {shortWallet}
                {pageRefusal}
            </div>
            {footer(
                <>
                    {offers('primary.reserve') ? (
                        <button
                            type="button"
                            onClick={reserve}
                            disabled={!canReserve}
                            aria-busy={command.busy || undefined}
                            className={cn(
                                'flex h-[52px] w-full items-center justify-center gap-2 rounded-2xl text-[15.5px] font-semibold',
                                canReserve
                                    ? 'bg-rz-accent-fill text-white'
                                    : 'cursor-not-allowed bg-rz-disabled text-rz-secondary',
                            )}
                        >
                            {busyLabel(
                                t('investor.checkout.c3.reserve', {
                                    amount: formatRwf(quote.amount),
                                }),
                            )}
                        </button>
                    ) : (
                        <p
                            role="status"
                            className="text-center text-[12px] text-rz-secondary"
                        >
                            {t('investor.checkout.c3.reserve_unavailable')}
                        </p>
                    )}
                    <p className="mt-2 text-center text-[10px] leading-[1.4] text-rz-secondary">
                        {t('investor.checkout.c3.reserve_fine_print')}
                    </p>
                </>,
            )}
        </div>,
    );
}
