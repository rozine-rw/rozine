import { Link } from '@inertiajs/react';
import { ACCENT_FILL, POSITIVE_TEXT } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatSigned,
    formatSignedPct,
    isNegative,
} from '@/lib/investor/format';
import { formatAmount, formatMonthYear } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { HoldingHealth, HoldingSummary } from '@/types/investor';

/** Health pill colours; arrears, freezes and defaults surface at once (crosswalk AC-06). */
export const HEALTH_PILL: Record<HoldingHealth, string> = {
    healthy: cn(
        'bg-[rgba(29,158,117,.10)] dark:bg-[rgba(63,205,160,.14)]',
        POSITIVE_TEXT,
    ),
    watch: 'bg-[rgba(194,102,31,.10)] text-[#a55418] dark:bg-[rgba(240,160,96,.14)] dark:text-[#f0a060]',
    arrears:
        'bg-[rgba(229,72,77,.10)] text-rz-danger-text dark:bg-rz-danger-tint',
    frozen: 'bg-[rgba(30,58,255,.10)] text-rz-accent-app-text',
    defaulted:
        'bg-[rgba(229,72,77,.10)] text-rz-danger-text dark:bg-rz-danger-tint',
    matured: 'bg-rz-surface-muted text-rz-secondary',
};

export function HealthPill({
    health,
    className,
}: {
    health: HoldingHealth;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center gap-[5px] rounded-[20px] px-[9px] py-1 text-[11px] font-bold',
                HEALTH_PILL[health],
                className,
            )}
        >
            <span className="size-1.5 rounded-full bg-current" />
            {t(`investor.health.${health}`)}
        </span>
    );
}

/**
 * A holding in the portfolio list (design L1115–1142): business, health, current value against
 * what was invested, the gain the server reports, repayment progress and maturity.
 */
export function HoldingCard({ holding }: { holding: HoldingSummary }) {
    const { t, locale } = useTranslation();
    const down = isNegative(holding.gain);

    return (
        <Link
            href={holding.link}
            className="block rounded-2xl border border-[#eef1f6] bg-rz-surface px-[13px] py-[11px] shadow-[0_1px_2px_rgba(16,24,40,.04),0_10px_20px_-18px_rgba(16,24,40,.45)] dark:border-rz-border"
        >
            <span className="flex items-center gap-[11px]">
                <span
                    className="flex size-9 shrink-0 items-center justify-center rounded-[10px] text-[15px] font-bold text-white shadow-[inset_0_1px_0_rgba(255,255,255,.28)] dark:ring-1 dark:ring-white/15"
                    style={{ background: ACCENT_FILL[holding.accent] }}
                >
                    {holding.name.charAt(0)}
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-bold tracking-[-.2px] text-rz-ink">
                        {holding.name}
                    </span>
                    <span className="mt-px block truncate text-[11px] text-rz-secondary">
                        {holding.industry} · {holding.district}
                    </span>
                </span>
                <HealthPill health={holding.health} />
            </span>
            <span className="mt-2.5 flex items-center justify-between gap-2.5">
                <span className="min-w-0">
                    <span className="flex items-baseline gap-1">
                        <span className="text-[10.5px] font-bold text-rz-slate">
                            {t('investor.money.rwf')}
                        </span>
                        <span className="text-[15px] font-bold tracking-[-.2px] text-rz-ink tabular-nums">
                            {formatAmount(holding.value)}
                        </span>
                    </span>
                    <span className="mt-px block text-[10px] text-rz-secondary">
                        {t('investor.portfolio.invested_line', {
                            amount: formatAmount(holding.invested),
                        })}
                    </span>
                </span>
                <span className="shrink-0 text-right">
                    <span
                        className={cn(
                            'inline-flex items-center gap-[3px] rounded-[20px] px-2 py-[3px] text-[11px] font-bold tabular-nums',
                            down
                                ? 'bg-[rgba(229,72,77,.10)] text-rz-danger-text'
                                : cn(
                                      'bg-[rgba(29,158,117,.10)] dark:bg-[rgba(63,205,160,.14)]',
                                      POSITIVE_TEXT,
                                  ),
                        )}
                    >
                        <span aria-hidden className="text-[10px]">
                            {down ? '▼' : '▲'}
                        </span>
                        {formatSignedPct(holding.gain_pct)}
                    </span>
                    <span
                        className={cn(
                            'mt-0.5 block text-[10.5px] font-semibold tabular-nums',
                            down ? 'text-rz-danger-text' : POSITIVE_TEXT,
                        )}
                    >
                        {formatSigned(holding.gain)}
                    </span>
                </span>
            </span>
            <span className="mt-2.5 block h-[5px] overflow-hidden rounded-[3px] bg-[#eef2f8] dark:bg-rz-surface-muted">
                <span
                    className="block h-full rounded-[3px] bg-[linear-gradient(90deg,#2f6bff,#5b9bff)]"
                    style={{ width: `${holding.repaid_pct}%` }}
                />
            </span>
            <span className="mt-1.5 flex items-center justify-between text-[11px]">
                <span className="text-rz-secondary">
                    {t('investor.portfolio.matures_line', {
                        date: formatMonthYear(holding.matures_on, locale),
                        made: holding.payments_made,
                        total: holding.payments_total,
                    })}
                </span>
                <span className="inline-flex items-center gap-px font-bold text-rz-accent-app-text">
                    {t('investor.portfolio.details')}
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[13px]"
                    >
                        <path
                            d="M9 18l6-6-6-6"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </span>
            </span>
        </Link>
    );
}
