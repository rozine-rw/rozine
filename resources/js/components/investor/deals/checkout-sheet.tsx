import { Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useTimeLeft } from '@/components/investor/deals/time-left';
import { useQuotedUnits } from '@/components/investor/deals/use-quote';
import { ACCENT_FILL, POSITIVE_TEXT } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatCompactBare } from '@/lib/investor/format';
import {
    formatAmount,
    formatDate,
    formatMonthYear,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { InvestorCheckoutProps } from '@/types/investor';

type CheckoutSheetProps = Omit<InvestorCheckoutProps, 'home'> & {
    variant: 'phone' | 'desk';
};

const ROW = 'flex justify-between gap-2.5 py-[7px]';
const ROW_LINE = 'border-b border-[#eef2f9] dark:border-rz-divider';

function CloseLink({
    href,
    variant,
}: {
    href: InvestorCheckoutProps['links']['close'];
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

/**
 * Checkout (MVP-INVESTOR-SCR-03; phone L4782–4939, desktop L247–324). The quantity is the
 * investor's; every amount is the server's quote for it. The cost and risk disclosure must be
 * acknowledged before Confirm, which carries the amount, sends one command and shows the receipt
 * the server returns. Primary checkout is paid from the wallet (CFG-03), so the design's MoMo,
 * Airtel and card tiles are not offered.
 */
export function CheckoutSheet({
    server_time: serverTime,
    deal,
    quote,
    limits,
    quick_picks: quickPicks,
    wallet,
    disclosure,
    refusal,
    receipt,
    links,
    actions,
    variant,
}: CheckoutSheetProps) {
    const { t, locale } = useTranslation();
    const clock = useTimeLeft(deal.closes_at, serverTime);
    const quoted = useQuotedUnits(quote.units, {}, [
        'quote',
        'wallet',
        'refusal',
    ]);
    const form = useForm({
        deal: deal.id,
        units: quote.units,
        revision: quote.revision,
        disclosure_version: disclosure.version,
        acknowledged: false,
    });
    const phone = variant === 'phone';
    const ready =
        form.data.acknowledged &&
        wallet.sufficient &&
        deal.status === 'open' &&
        !quoted.quoting;

    const pick = (units: number) => {
        form.setData('units', units);
        quoted.setUnits(units);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            units: quote.units,
            revision: quote.revision,
        }));
        form.post(actions.confirm.url, { preserveScroll: true });
    };

    const shell = (children: ReactNode) => (
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
                aria-label={
                    receipt === null
                        ? t('investor.checkout.title')
                        : t('investor.checkout.done')
                }
                className={cn(
                    'z-[63] flex flex-col bg-[#f6f8fc] dark:bg-rz-page',
                    phone
                        ? 'fixed inset-x-0 bottom-0 max-h-[94%] animate-[rz-sheetup_.34s_cubic-bezier(.22,1,.36,1)] rounded-[20px_26px_0_0] shadow-[0_-22px_60px_-18px_rgba(8,14,28,.5)]'
                        : 'absolute top-1/2 left-1/2 h-[min(548px,calc(100%-26px))] min-h-80 w-[376px] -translate-x-1/2 -translate-y-1/2 animate-[rz-copop_.34s_cubic-bezier(.16,1,.3,1)_both] overflow-hidden rounded-[20px] border border-[#e2e8f2] shadow-[0_26px_64px_-20px_rgba(8,14,28,.5)] dark:border-rz-border',
                )}
            >
                {children}
            </section>
        </>
    );

    if (receipt !== null) {
        return shell(
            <>
                <div
                    className={cn(
                        'flex shrink-0 items-center justify-between',
                        phone ? 'px-[18px] pt-[22px]' : 'px-4 pt-3.5',
                    )}
                >
                    <h2
                        className={cn(
                            'font-semibold text-rz-ink',
                            phone ? 'text-lg' : 'text-[17px]',
                        )}
                    >
                        {t('investor.checkout.done')}
                    </h2>
                    <CloseLink href={links.close} variant={variant} />
                </div>
                <div className="rz-scroll flex min-h-0 flex-1 flex-col items-center overflow-y-auto px-6 pt-2 pb-[30px] text-center">
                    <span className="mt-3.5 flex size-[84px] animate-[rz-pop_.5s_ease] items-center justify-center rounded-full bg-[rgba(29,158,117,.10)]">
                        <span className="flex size-[58px] items-center justify-center rounded-full bg-[#17795a]">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="size-[30px]"
                            >
                                <path
                                    d="M5 13l4 4L19 7"
                                    stroke="#fff"
                                    strokeWidth="3"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        </span>
                    </span>
                    <p className="mt-[18px] text-[21px] font-semibold text-rz-ink">
                        {t('investor.checkout.confirmed')}
                    </p>
                    <p className="mt-2 text-[13.5px] leading-[1.55] text-rz-secondary">
                        {t('investor.checkout.confirmed_body_before')}{' '}
                        <span className="font-semibold text-rz-ink">
                            {formatRwf(receipt.amount)}
                        </span>{' '}
                        {t('investor.checkout.confirmed_body_after', {
                            name: deal.name,
                        })}
                    </p>
                    <dl className="mt-5 w-full rounded-2xl border border-rz-border bg-rz-surface px-4 py-1.5 text-left">
                        {(
                            [
                                [
                                    'investor.checkout.expected_return_plain',
                                    `+${formatRwf(receipt.expected_return)}`,
                                    POSITIVE_TEXT,
                                ],
                                [
                                    'investor.checkout.maturity_value_plain',
                                    formatRwf(receipt.maturity_value),
                                    'text-rz-ink',
                                ],
                                [
                                    'investor.checkout.maturity_date',
                                    formatDate(receipt.maturity_date, locale),
                                    'text-rz-ink',
                                ],
                                [
                                    'investor.checkout.transaction_id',
                                    receipt.transaction_id,
                                    'text-rz-ink',
                                ],
                                [
                                    'investor.checkout.reference',
                                    receipt.reference,
                                    'text-rz-ink',
                                ],
                            ] as const
                        ).map(([label, value, tone], index) => (
                            <div
                                key={label}
                                className={cn(
                                    'flex justify-between gap-3 py-2.5',
                                    index < 4 && ROW_LINE,
                                )}
                            >
                                <dt className="text-[13px] text-rz-secondary">
                                    {t(label)}
                                </dt>
                                <dd
                                    className={cn(
                                        'text-right text-[13px] font-semibold',
                                        tone,
                                    )}
                                >
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    <Link
                        href={receipt.link}
                        className="mt-3 text-[12.5px] font-semibold text-rz-accent-app-text"
                    >
                        {t('investor.checkout.view_receipt')}
                    </Link>
                </div>
                <div className="shrink-0 px-[18px] pt-1.5 pb-[calc(env(safe-area-inset-bottom)+26px)] lg:pb-6">
                    <Link
                        href={links.portfolio}
                        className="flex h-[52px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white"
                    >
                        {t('investor.checkout.view_portfolio')}
                    </Link>
                    <Link
                        href={links.deals}
                        className="mt-2.5 flex h-12 w-full items-center justify-center rounded-2xl border border-rz-border text-sm font-semibold text-rz-slate"
                    >
                        {t('investor.checkout.explore')}
                    </Link>
                </div>
            </>,
        );
    }

    return shell(
        <form onSubmit={submit} className="flex min-h-0 flex-1 flex-col">
            <div
                className={cn(
                    'shrink-0',
                    phone ? 'px-[18px] pt-2.5' : 'px-4 pt-3.5',
                )}
            >
                {phone && (
                    <span className="mx-auto block h-[5px] w-10 rounded-[3px] bg-[#d3dae6] dark:bg-rz-border" />
                )}
                <div
                    className={cn(
                        'flex items-center justify-between',
                        phone && 'mt-3',
                    )}
                >
                    <h2
                        className={cn(
                            'font-semibold text-rz-ink',
                            phone ? 'text-lg' : 'text-[17px]',
                        )}
                    >
                        {t('investor.checkout.title')}
                    </h2>
                    <CloseLink href={links.close} variant={variant} />
                </div>
            </div>

            <div
                className={cn(
                    'rz-scroll min-h-0 flex-1 overflow-y-auto',
                    phone ? 'px-4 pt-2.5 pb-1' : 'px-4 pt-3 pb-1',
                )}
            >
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

                <div className="mt-[11px] flex items-center justify-between">
                    <span className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                        {t('investor.checkout.amount')}
                    </span>
                    <span className="text-[11px] font-semibold text-rz-secondary">
                        {t(
                            quote.units === 1
                                ? 'investor.checkout.units_each_one'
                                : 'investor.checkout.units_each_other',
                            {
                                count: quote.units,
                                price: formatRwf(quote.unit_price),
                            },
                        )}
                    </span>
                </div>
                <div className="mt-[7px] flex items-stretch gap-[7px]">
                    <button
                        type="button"
                        aria-label={t('investor.deal.fewer_notes')}
                        disabled={quoted.units <= limits.min_units}
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
                        disabled={quoted.units >= limits.max_units}
                        onClick={() => pick(quoted.units + 1)}
                        className="w-[46px] shrink-0 rounded-xl border border-rz-border bg-rz-surface text-[21px] text-rz-ink disabled:opacity-40"
                    >
                        <span aria-hidden>+</span>
                    </button>
                </div>
                <div className="mt-[7px] flex gap-1.5">
                    {quickPicks.map((option) => {
                        const on = option.units === quoted.units;

                        return (
                            <button
                                key={option.units}
                                type="button"
                                aria-pressed={on}
                                disabled={option.units > limits.max_units}
                                onClick={() => pick(option.units)}
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
                            className={cn(
                                'text-[12.5px] font-semibold',
                                POSITIVE_TEXT,
                            )}
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
                    <div className={ROW}>
                        <dt className="text-[12.5px] text-rz-secondary">
                            {t('investor.checkout.maturity_value', {
                                date: formatMonthYear(
                                    quote.maturity_date,
                                    locale,
                                ),
                            })}
                        </dt>
                        <dd className="text-[12.5px] font-semibold text-rz-ink">
                            {formatRwf(quote.maturity_value)}
                        </dd>
                    </div>
                </dl>

                <p className="mt-[11px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                    {t('investor.checkout.pay_with')}
                </p>
                <div
                    role="radiogroup"
                    aria-label={t('investor.checkout.pay_with')}
                    className="mt-[7px] flex gap-1.5"
                >
                    <span
                        role="radio"
                        aria-checked
                        className="relative flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-xl border-[1.5px] border-[#d6e4ff] bg-rz-page px-1 py-[9px] dark:border-rz-investor"
                    >
                        <span className="flex size-[30px] items-center justify-center rounded-[9px] bg-rz-accent-soft text-lg">
                            <Icon name="wallet" />
                        </span>
                        <span className="text-[10px] font-semibold whitespace-nowrap text-rz-accent-app-text">
                            {t('investor.checkout.wallet')}
                        </span>
                        <span
                            aria-hidden
                            className="absolute top-[5px] right-[5px] flex size-3.5 items-center justify-center rounded-full bg-rz-accent-fill text-[10px] text-white"
                        >
                            ✓
                        </span>
                    </span>
                </div>
                <p className="mt-[7px] text-center text-[11px] text-rz-secondary">
                    {t('investor.checkout.wallet_detail', {
                        amount: formatRwf(wallet.available),
                    })}
                </p>
                {!wallet.sufficient && (
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
                )}

                <p className="mt-[11px] text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
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

                {(refusal !== null || form.errors.units !== undefined) && (
                    <div
                        role="alert"
                        className="mt-2.5 flex items-center gap-2 rounded-[10px] border border-[#fdeaea] bg-[rgba(229,72,77,.08)] px-[13px] py-[11px] text-[12.5px] font-semibold text-rz-danger-text dark:border-[rgba(255,107,111,.25)]"
                    >
                        <Icon name="warning" tone="red" />
                        {refusal !== null
                            ? t(`investor.checkout.refusal.${refusal.code}`)
                            : form.errors.units}
                    </div>
                )}
            </div>

            <div
                className={cn(
                    'shrink-0',
                    phone
                        ? 'px-4 pt-2.5 pb-[calc(env(safe-area-inset-bottom)+24px)]'
                        : 'px-4 pt-2.5 pb-4',
                )}
            >
                <label className="mb-2.5 flex cursor-pointer items-start gap-2.5">
                    <input
                        type="checkbox"
                        checked={form.data.acknowledged}
                        onChange={(event) =>
                            form.setData('acknowledged', event.target.checked)
                        }
                        className="peer sr-only"
                    />
                    <span
                        aria-hidden
                        className="mt-px flex size-5 shrink-0 items-center justify-center rounded-[8px] border-2 border-[#d3dae6] bg-rz-surface text-xs text-white peer-checked:border-rz-accent-fill peer-checked:bg-rz-accent-fill peer-focus-visible:ring-2 peer-focus-visible:ring-rz-focus-border dark:border-rz-border"
                    >
                        {form.data.acknowledged ? '✓' : ''}
                    </span>
                    <span className="text-[11.5px] leading-[1.45] text-rz-slate">
                        {t('investor.checkout.acknowledge', {
                            version: disclosure.version,
                        })}
                    </span>
                </label>
                <button
                    type="submit"
                    disabled={!ready || form.processing}
                    aria-busy={form.processing || undefined}
                    className={cn(
                        'flex h-[52px] w-full items-center justify-center gap-2 rounded-2xl font-semibold',
                        'text-[15.5px]',
                        ready
                            ? 'bg-rz-accent-fill text-white'
                            : 'cursor-not-allowed bg-rz-disabled text-rz-secondary',
                    )}
                >
                    {form.processing && (
                        <span className="size-[17px] animate-spin rounded-full border-[2.5px] border-white/40 border-t-white" />
                    )}
                    {form.processing
                        ? t('investor.checkout.processing')
                        : t('investor.checkout.confirm', {
                              amount: formatRwf(quote.amount),
                          })}
                </button>
                <p className="mt-2 text-center text-[10px] leading-[1.4] text-rz-secondary">
                    {t('investor.checkout.fine_print')}
                </p>
            </div>
        </form>,
    );
}
