import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import type { RouteLink } from '@/types';
import type { C4InvestorPortfolioProps } from '@/types/investor';

const LABEL =
    'text-[10px] font-bold tracking-[.04em] text-white/[.76] uppercase';

function Line({
    label,
    value,
    basis,
    strong = false,
}: {
    label: string;
    value: string;
    basis?: RouteLink;
    strong?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div className="flex items-baseline justify-between gap-3 py-[3px]">
            <dt className="min-w-0 text-[11.5px] text-white/[.82]">
                {label}
                {basis !== undefined && (
                    <Link
                        href={basis}
                        aria-label={t('investor.servicing.basis_for', {
                            figure: label,
                        })}
                        className="ml-1.5 text-[10.5px] font-bold text-white underline"
                    >
                        {t('investor.servicing.basis')}
                    </Link>
                )}
            </dt>
            <dd
                className={
                    strong
                        ? 'text-[13px] font-bold whitespace-nowrap text-white'
                        : 'text-[11.5px] font-semibold whitespace-nowrap text-white'
                }
            >
                {value}
            </dd>
        </div>
    );
}

/**
 * The portfolio's earnings card (C4 v1 §4c, `investor-servicing-v1`). It replaces the Phase 1B
 * value and gain, which mixed what was paid with what is scheduled: realised figures are paid and
 * credited, and projected figures are labelled "scheduled, not guaranteed". Every figure is the
 * server's; the client never adds, nets or averages.
 */
export function EarningsCard({
    earnings,
    bases,
}: Pick<C4InvestorPortfolioProps, 'earnings' | 'bases'>) {
    const { t, locale } = useTranslation();

    return (
        <section
            aria-label={t('investor.earnings.title')}
            className="relative overflow-hidden rounded-[20px] bg-[#1428a4] px-[18px] py-[17px] shadow-[inset_0_0_0_1px_rgba(255,255,255,.14)]"
        >
            <p className="text-[11px] font-bold tracking-[.05em] text-white/[.78] uppercase">
                {t('investor.earnings.title')}
            </p>
            <dl className="mt-2">
                <Line
                    label={t('investor.earnings.invested')}
                    value={formatRwf(earnings.invested)}
                    basis={bases.invested}
                    strong
                />
                <Line
                    label={t('investor.earnings.outstanding_principal')}
                    value={formatRwf(earnings.outstanding_principal)}
                    basis={bases.outstanding_principal}
                />
            </dl>
            <div className="mt-3 border-t border-white/20 pt-3">
                <p className={LABEL}>{t('investor.earnings.realised')}</p>
                <dl className="mt-1">
                    <Line
                        label={t('investor.earnings.principal_back')}
                        value={formatRwf(earnings.realised.principal)}
                    />
                    <Line
                        label={t('investor.earnings.return')}
                        value={formatRwf(earnings.realised.return)}
                    />
                    <Line
                        label={t('investor.earnings.late_fees')}
                        value={formatRwf(earnings.realised.late_fees)}
                    />
                    <Line
                        label={t('investor.earnings.fees')}
                        value={formatRwf(earnings.realised.fees)}
                    />
                    <Line
                        label={t('investor.earnings.net_return')}
                        value={formatRwf(earnings.realised.net_return)}
                        basis={bases.realised}
                        strong
                    />
                    <Line
                        label={t('investor.earnings.this_month')}
                        value={formatRwf(earnings.this_month_net)}
                    />
                    <Line
                        label={t('investor.earnings.avg_monthly')}
                        value={
                            earnings.avg_monthly_net === null
                                ? '—'
                                : formatRwf(earnings.avg_monthly_net)
                        }
                    />
                </dl>
            </div>
            <div className="mt-3 border-t border-white/20 pt-3">
                <p className={LABEL}>{t('investor.earnings.projected')}</p>
                <p className="mt-0.5 text-[10.5px] text-white/[.82]">
                    {t('investor.servicing.projection')}
                </p>
                <dl className="mt-1">
                    <Line
                        label={t('investor.earnings.remaining_return')}
                        value={formatRwf(earnings.projected.remaining_return)}
                    />
                    <Line
                        label={t('investor.earnings.next_3m')}
                        value={formatRwf(earnings.projected.next_3m)}
                    />
                </dl>
                {earnings.next_payout !== null && (
                    <p className="mt-1 text-[10.5px] text-white/[.82]">
                        {t('investor.earnings.next_payout', {
                            amount: formatRwf(earnings.next_payout.projected),
                            date: formatDate(
                                earnings.next_payout.due_on,
                                locale,
                            ),
                        })}
                    </p>
                )}
            </div>
        </section>
    );
}
