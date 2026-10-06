import { Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ACCENT_FILL,
    POSITIVE_TEXT,
    RATING_STYLE,
} from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatCompact,
    formatCompactBare,
    formatSignedCompact,
    intlTag,
} from '@/lib/investor/format';
import { formatMonthYearLong, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type {
    InvestorPortfolioProps,
    InvestorRatingBand,
} from '@/types/investor';

const HEADING =
    'text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase';

const monthShort = (iso: string, locale: string): string =>
    new Intl.DateTimeFormat(intlTag(locale), {
        month: 'short',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso));

const monthYearShort = (iso: string, locale: string): string =>
    `${monthShort(iso, locale)} ’${new Intl.DateTimeFormat('en-GB', {
        year: '2-digit',
        timeZone: 'Africa/Kigali',
    }).format(new Date(iso))}`;

/**
 * The portfolio's total card (design L1188–1216): value, business count, invested, gain and this
 * month, then the scheduled payouts for the next three months — labelled a projection.
 */
export function TotalCard({
    totals,
}: {
    totals: InvestorPortfolioProps['totals'];
}) {
    const { t, locale } = useTranslation();
    const tile = 'min-w-0 flex-1 rounded-xl bg-white/[.14] px-[11px] py-[9px]';
    const tileLabel =
        'text-[10px] font-bold tracking-[.03em] whitespace-nowrap text-white/[.76] uppercase';
    const tileValue = 'mt-[3px] text-xs font-bold whitespace-nowrap text-white';

    return (
        <section
            aria-label={t('investor.portfolio.total_value')}
            className="relative overflow-hidden rounded-[20px] bg-[#1428a4] px-[18px] py-[17px] shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)]"
        >
            <div className="flex items-center justify-between gap-2">
                <span className="text-[11px] font-bold tracking-[.05em] text-white/[.78] uppercase">
                    {t('investor.portfolio.total_value')}
                </span>
                <span className="inline-flex shrink-0 items-center rounded-[10px] bg-white/[.16] px-2.5 py-[5px] text-[11.5px] font-semibold whitespace-nowrap text-white">
                    {t('investor.portfolio.businesses', {
                        count: totals.businesses,
                    })}
                </span>
            </div>
            <p className="mt-[5px] text-[26px] font-bold tracking-[-.5px] whitespace-nowrap text-white">
                {formatRwf(totals.value)}
            </p>
            <div className="mt-[13px] flex gap-2">
                <div className={tile}>
                    <p className={tileLabel}>
                        {t('investor.portfolio.invested')}
                    </p>
                    <p className={tileValue}>
                        {formatCompact(totals.invested, true)}
                    </p>
                </div>
                <div className={tile}>
                    <p className={tileLabel}>
                        {t('investor.portfolio.total_gain')}
                    </p>
                    <p className={tileValue}>
                        {formatSignedCompact(totals.gain)}
                    </p>
                </div>
                <div className={tile}>
                    <p className={tileLabel}>
                        {t('investor.portfolio.this_month')}
                    </p>
                    <p className={tileValue}>
                        {formatSignedCompact(totals.this_month)}
                    </p>
                </div>
            </div>
            <div className="mt-3.5 flex items-end justify-between gap-3 border-t border-white/20 pt-3.5">
                <div className="min-w-0">
                    <p className="text-[10px] font-bold tracking-[.04em] whitespace-nowrap text-white/[.76] uppercase">
                        {t('investor.portfolio.projected')}
                    </p>
                    <p className="mt-[3px] text-xl font-bold tracking-[-.3px] text-white">
                        {formatCompact(totals.projected_3m, true)}
                    </p>
                    {totals.next_payout !== null && (
                        <p className="mt-0.5 text-[10.5px] text-white/[.82]">
                            {t('investor.portfolio.next_payout', {
                                amount: formatCompact(
                                    totals.next_payout.amount,
                                    true,
                                ),
                                month: monthYearShort(
                                    totals.next_payout.month,
                                    locale,
                                ),
                            })}
                        </p>
                    )}
                </div>
                <div className="shrink-0 text-right">
                    <p className="text-[10px] font-bold tracking-[.04em] text-white/[.76] uppercase">
                        {t('investor.portfolio.avg_month')}
                    </p>
                    <p className="mt-[3px] text-[15px] font-bold text-white">
                        {formatCompact(totals.avg_monthly)}
                    </p>
                </div>
            </div>
        </section>
    );
}

/**
 * Upcoming payouts (design L1203–1246): six months of scheduled payouts; picking a month lists
 * which businesses pay. Bar heights are the server's share of the tallest month.
 */
export function PayoutChart({
    payouts,
}: {
    payouts: InvestorPortfolioProps['payouts'];
}) {
    const { t, locale } = useTranslation();
    const [selected, setSelected] = useState(0);
    const month = payouts[selected];

    if (month === undefined) {
        return null;
    }

    return (
        <section aria-label={t('investor.portfolio.upcoming')}>
            <h2 className={HEADING}>{t('investor.portfolio.upcoming')}</h2>
            <div className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface px-3.5 pt-4 pb-3.5">
                <div
                    role="radiogroup"
                    aria-label={t('investor.portfolio.upcoming')}
                    className="flex h-[118px] items-end justify-between gap-2"
                >
                    {payouts.map((entry, index) => {
                        const on = index === selected;

                        return (
                            <button
                                key={entry.month}
                                type="button"
                                role="radio"
                                aria-checked={on}
                                aria-label={formatMonthYearLong(
                                    entry.month,
                                    locale,
                                )}
                                onClick={() => setSelected(index)}
                                className="flex h-full flex-1 flex-col items-center justify-end gap-1.5"
                            >
                                <span
                                    className={cn(
                                        'shrink-0 text-[10.5px] font-bold whitespace-nowrap',
                                        on
                                            ? 'text-rz-accent-app-text'
                                            : 'text-rz-secondary opacity-55',
                                    )}
                                >
                                    {formatCompactBare(entry.amount)}
                                </span>
                                <span className="flex min-h-0 w-full flex-[1_1_0] items-end justify-center">
                                    <span
                                        className={cn(
                                            'w-full max-w-[26px] shrink-0 rounded-[10px_7px_3px_3px] bg-rz-accent-fill transition-opacity duration-200',
                                            !on && 'opacity-[.28]',
                                        )}
                                        style={{
                                            height: `${Math.max(6, entry.bar_pct)}%`,
                                        }}
                                    />
                                </span>
                                <span
                                    className={cn(
                                        'shrink-0 text-[10px] font-semibold',
                                        on
                                            ? 'text-rz-ink'
                                            : 'text-rz-secondary opacity-55',
                                    )}
                                >
                                    {monthShort(entry.month, locale)}
                                </span>
                            </button>
                        );
                    })}
                </div>
                <div className="mt-[15px] border-t border-[#eef2f9] pt-[13px] dark:border-rz-divider">
                    <div className="flex items-baseline justify-between gap-2.5">
                        <span className="text-[12.5px] font-bold text-rz-ink">
                            {formatMonthYearLong(month.month, locale)}
                        </span>
                        <span className="text-sm font-extrabold text-rz-accent-app-text">
                            {formatCompact(month.amount, true)}
                        </span>
                    </div>
                    <p className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {t(
                            month.payers.length === 1
                                ? 'investor.portfolio.pays_one'
                                : 'investor.portfolio.pays_other',
                            { count: month.payers.length },
                        )}
                    </p>
                    <ul className="mt-[11px] flex flex-col gap-[9px]">
                        {month.payers.map((payer) => (
                            <li
                                key={payer.name}
                                className="flex items-center gap-[9px]"
                            >
                                <span
                                    className="flex size-[22px] shrink-0 items-center justify-center rounded-[10px] text-[10px] font-bold text-white"
                                    style={{
                                        background: ACCENT_FILL[payer.accent],
                                    }}
                                >
                                    {payer.name.charAt(0)}
                                </span>
                                <span className="min-w-0 flex-1 truncate text-xs text-rz-slate">
                                    {payer.name}
                                </span>
                                <span className="text-xs font-bold text-rz-ink tabular-nums">
                                    {formatCompact(payer.amount)}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
                <p className="mt-3 flex items-start gap-[7px] border-t border-[#eef2f9] pt-[11px] text-[11px] leading-normal text-rz-secondary dark:border-rz-divider">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="mt-px size-[13px] shrink-0 text-[#a9b4c6]"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke="currentColor"
                            strokeWidth="2"
                        />
                        <path
                            d="M12 8v5M12 16h.01"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                        />
                    </svg>
                    <span>{t('investor.portfolio.upcoming_note')}</span>
                </p>
            </div>
        </section>
    );
}

const RISK_BAR: Record<InvestorRatingBand, string> = {
    strong: 'bg-[#17795a] dark:bg-[#3fcda0]',
    stable: 'bg-[#1e3aff] dark:bg-[#6378ff]',
    weak: 'bg-[#c2661f] dark:bg-[#f0a060]',
    distressed: 'bg-[#b3383c] dark:bg-[#ff8285]',
};

/**
 * Concentration (crosswalk AC-05): industry mix, rating mix and the concentration call-out, all
 * from the server's exposure figures (design L1247–1300).
 */
export function Exposure({
    industries,
    risk,
    concentration,
    idle,
    deals,
}: Pick<
    InvestorPortfolioProps,
    'industries' | 'risk' | 'concentration' | 'idle'
> & { deals: RouteLink }) {
    const { t } = useTranslation();

    return (
        <>
            <section
                aria-label={t('investor.portfolio.diversification')}
                className="mt-5"
            >
                <h2 className={HEADING}>
                    {t('investor.portfolio.diversification')}
                </h2>
                <div className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface p-4">
                    <div className="flex h-3 gap-0.5 overflow-hidden rounded-[10px]">
                        {industries.map((entry) => (
                            <span
                                key={entry.name}
                                style={{
                                    width: `${entry.share_pct}%`,
                                    background: ACCENT_FILL[entry.accent],
                                }}
                            />
                        ))}
                    </div>
                    <ul className="mt-3.5 flex flex-col gap-[11px]">
                        {industries.map((entry) => (
                            <li
                                key={entry.name}
                                className="flex items-center gap-2.5"
                            >
                                <span
                                    className="size-2.5 shrink-0 rounded-[3px]"
                                    style={{
                                        background: ACCENT_FILL[entry.accent],
                                    }}
                                />
                                <span className="flex-1 text-[13px] font-semibold text-rz-ink">
                                    {entry.name}
                                </span>
                                <span className="text-xs font-semibold text-rz-secondary">
                                    {formatCompact(entry.amount)}
                                </span>
                                <span className="w-[38px] text-right text-[13px] font-bold text-rz-ink">
                                    {entry.share_pct}%
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section
                aria-label={t('investor.portfolio.risk_balance')}
                className="mt-5"
            >
                <h2 className={HEADING}>
                    {t('investor.portfolio.risk_balance')}
                </h2>
                <div className="mt-[11px] flex gap-2.5 rounded-2xl border border-rz-border bg-rz-surface p-4">
                    {risk.map((entry) => (
                        <div key={entry.band} className="flex-1 text-center">
                            <p
                                className={cn(
                                    'text-[22px] font-bold',
                                    RATING_STYLE[entry.band].text,
                                )}
                            >
                                {entry.share_pct}%
                            </p>
                            <div className="mt-[5px] h-[5px] overflow-hidden rounded-[3px] bg-rz-page">
                                <div
                                    className={cn(
                                        'h-full',
                                        RISK_BAR[entry.band],
                                    )}
                                    style={{ width: `${entry.share_pct}%` }}
                                />
                            </div>
                            <p className="mt-[7px] text-[11px] font-semibold text-rz-secondary">
                                {t(`investor.rating.${entry.band}`)}
                            </p>
                        </div>
                    ))}
                </div>
            </section>

            {concentration?.status === 'concentrated' && (
                <div className="mt-4 flex gap-3 rounded-2xl border border-[#f3dfbf] bg-[#fff8ee] p-3.5 dark:border-[rgba(240,160,96,.3)] dark:bg-[rgba(240,160,96,.08)]">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-[rgba(194,102,31,.10)] text-lg">
                        <Icon name="scales" tone="amber" />
                    </span>
                    <div className="flex-1">
                        <p className="text-[13px] font-semibold text-rz-ink">
                            {t('investor.portfolio.concentrated')}
                        </p>
                        <p className="mt-[3px] text-xs leading-normal text-[#8a6d3b] dark:text-[#f0a060]">
                            {t('investor.portfolio.concentrated_body', {
                                name: concentration.business,
                                pct: concentration.share_pct,
                            })}
                        </p>
                        <Link
                            href={deals}
                            className="mt-2.5 inline-block rounded-[10px] bg-[#7d420f] px-3.5 py-2 text-xs font-semibold text-white"
                        >
                            {t('investor.portfolio.browse')}
                        </Link>
                    </div>
                </div>
            )}
            {concentration?.status === 'balanced' && (
                <div className="mt-4 flex gap-3 rounded-2xl border border-[#cfe9d8] bg-[#f0f9f3] p-3.5 dark:border-[rgba(63,205,160,.25)] dark:bg-[rgba(63,205,160,.08)]">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-[rgba(29,158,117,.10)] text-lg">
                        <Icon name="check-badge" tone="green" />
                    </span>
                    <div className="flex-1">
                        <p className="text-[13px] font-semibold text-rz-ink">
                            {t('investor.portfolio.balanced')}
                        </p>
                        <p className="mt-[3px] text-xs leading-normal text-[#3d7a68] dark:text-[#3fcda0]">
                            {t('investor.portfolio.balanced_body')}
                        </p>
                    </div>
                </div>
            )}
            {idle !== null && (
                <div className="mt-4 flex items-center gap-3 rounded-2xl bg-[#17795a] p-4">
                    <div className="flex-1">
                        <p className="text-[11px] font-bold tracking-[.04em] text-white uppercase">
                            {t('investor.portfolio.idle')}
                        </p>
                        <p className="mt-[3px] text-xl font-bold text-white">
                            {formatRwf(idle)}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-white">
                            {t('investor.portfolio.idle_body')}
                        </p>
                    </div>
                    <Link
                        href={deals}
                        className={cn(
                            'shrink-0 rounded-xl bg-white px-4 py-3 text-[13.5px] font-bold',
                            POSITIVE_TEXT,
                            'dark:text-[#17795a]',
                        )}
                    >
                        {t('investor.portfolio.reinvest')}
                    </Link>
                </div>
            )}
        </>
    );
}
