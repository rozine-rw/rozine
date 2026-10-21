import { DIVIDER } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import {
    formatAmount,
    formatDayMonth,
    formatMonthYearLong,
    formatRwf,
} from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditorEarnings } from '@/types/auditor';

const CARD =
    'mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface';

const LABEL = 'text-[11px] font-bold tracking-[.06em] text-rz-slate uppercase';

/**
 * Earnings (design L635–735), as BRS v2.0 FEE-R02 pays the partner: 40% of the 3% platform fee on
 * each raise they audited, credited when it is paid out. The month's total and every line, each
 * with the raise and fee it was worked out from. The 25% share of the earnings fee is listed as a
 * source that starts with Investor payouts; the design's bonus cap, filing multiplier and withdraw
 * card belong to the superseded earnings model and are left out.
 */
export function EarningsSection({ earnings }: { earnings: AuditorEarnings }) {
    const { t, locale } = useTranslation();
    const month = formatMonthYearLong(
        `${earnings.month}T00:00:00+02:00`,
        locale,
    );

    return (
        <>
            <h2 className={LABEL}>{t('auditor.profile.earnings.title')}</h2>
            <p className="mt-[3px] text-[11.5px] leading-normal text-rz-secondary">
                {t('auditor.profile.earnings.lead')}
            </p>

            <section className="mt-2.5 rounded-[20px] bg-[#7d420f] p-[18px] text-white">
                <h3 className="text-[11px] font-bold tracking-[.05em] text-white/80 uppercase">
                    {t('auditor.profile.earnings.this_month', { month })}
                </h3>
                <p className="mt-[5px] flex items-baseline gap-1.5">
                    <span className="text-[14px] font-bold text-white/80">
                        {earnings.this_month.currency}
                    </span>
                    <span className="text-[30px] font-bold tracking-[-.5px]">
                        {formatAmount(earnings.this_month)}
                    </span>
                </p>
                <dl className="mt-3 rounded-[10px] bg-white/15 px-[11px] py-[9px]">
                    <dt className="text-[10px] font-bold text-white/75 uppercase">
                        {t('auditor.profile.earnings.total')}
                    </dt>
                    <dd className="mt-[3px] text-[14px] font-bold">
                        {formatRwf(earnings.total)}
                    </dd>
                </dl>
            </section>

            <h3 className={cn(LABEL, 'mt-5')}>
                {t('auditor.profile.earnings.sources')}
            </h3>
            <ul className={CARD}>
                <li
                    className={cn(
                        'flex items-center gap-3 border-b px-[15px] py-3',
                        DIVIDER,
                    )}
                >
                    <div className="min-w-0 flex-1">
                        <p className="text-[13px] font-semibold text-rz-ink">
                            {t('auditor.profile.earnings.platform_fee')}
                        </p>
                        <p className="text-[11.5px] text-rz-secondary">
                            {t('auditor.profile.earnings.platform_fee_rule')}
                        </p>
                    </div>
                    <span className="shrink-0 text-[13px] font-bold text-rz-ink">
                        {formatRwf(earnings.total)}
                    </span>
                </li>
                <li className="flex items-center gap-3 px-[15px] py-3">
                    <div className="min-w-0 flex-1">
                        <p className="text-[13px] font-semibold text-rz-ink">
                            {t('auditor.profile.earnings.earnings_fee')}
                        </p>
                        <p className="text-[11.5px] text-rz-secondary">
                            {t('auditor.profile.earnings.earnings_fee_rule')}
                        </p>
                    </div>
                    <span className="shrink-0 text-[13px] font-bold text-rz-muted">
                        —
                    </span>
                </li>
            </ul>

            <div className="mt-5 flex items-baseline justify-between gap-2">
                <h3 className={LABEL}>{t('auditor.profile.earnings.lines')}</h3>
                <span className="text-[11px] font-semibold text-rz-secondary">
                    {t('auditor.profile.earnings.line_count', {
                        count: String(earnings.lines.length),
                    })}
                </span>
            </div>
            {earnings.lines.length === 0 ? (
                <p className="mt-2.5 rounded-2xl border border-dashed border-[#dbe3f0] px-5 py-[26px] text-center text-[13px] text-rz-secondary dark:border-rz-border">
                    {t('auditor.profile.earnings.empty')}
                </p>
            ) : (
                <ul className={CARD}>
                    {earnings.lines.map((line) => (
                        <li
                            key={line.id}
                            className={cn(
                                'border-b px-[15px] py-3 last:border-b-0',
                                DIVIDER,
                            )}
                        >
                            <div className="flex items-start gap-3">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[13px] font-semibold text-rz-ink">
                                        {line.business}
                                    </p>
                                    <p className="text-[11.5px] text-rz-secondary">
                                        {line.district} ·{' '}
                                        {formatDayMonth(
                                            line.credited_at,
                                            locale,
                                        )}
                                    </p>
                                </div>
                                <span className="shrink-0 text-[13px] font-bold text-[#17795a] dark:text-[#5fd0a5]">
                                    {formatRwf(line.amount)}
                                </span>
                            </div>
                            <p className="mt-2 rounded-[10px] bg-rz-page px-[11px] py-2 text-[11.5px] leading-normal text-rz-secondary dark:bg-rz-surface-muted">
                                {t('auditor.profile.earnings.basis', {
                                    raise: formatRwf(line.raise),
                                    fee: formatRwf(line.platform_fee),
                                    amount: formatRwf(line.amount),
                                })}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
