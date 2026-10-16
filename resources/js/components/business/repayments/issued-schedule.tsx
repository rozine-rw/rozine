import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import type { BusinessIssuedSchedule } from '@/types/business';

/**
 * "Repayment schedule" (design L1190–1199) before servicing opens: one issued Note's instalments
 * as they were issued, each its scheduled total with the principal and return it is made of, under
 * the Note's totals. Nothing is shown as due, paid or late; those are servicing facts.
 */
export function IssuedSchedule({
    schedule,
}: {
    schedule: BusinessIssuedSchedule;
}) {
    const { t, locale } = useTranslation();
    const totals: [string, BusinessIssuedSchedule['total']][] = [
        [t('business.repayments.issued.principal'), schedule.principal],
        [t('business.repayments.issued.return'), schedule.return],
        [t('business.repayments.issued.total'), schedule.total],
    ];

    return (
        <section
            aria-label={`${t('business.repayments.schedule')} · ${schedule.title}`}
        >
            <h2 className="mt-[18px] text-base font-semibold text-rz-ink">
                {t('business.repayments.schedule')}
            </h2>
            <p className="mt-0.5 text-xs text-rz-secondary">
                {t('business.repayments.issued.lede', {
                    title: schedule.title,
                })}
            </p>
            <dl className="mt-3 grid grid-cols-3 gap-2">
                {totals.map(([label, amount]) => (
                    <div
                        key={label}
                        className="rounded-2xl border border-rz-border bg-rz-surface px-3 py-2.5"
                    >
                        <dt className="text-[10.5px] font-semibold text-rz-secondary uppercase">
                            {label}
                        </dt>
                        <dd className="mt-0.5 text-[13px] font-semibold text-rz-ink tabular-nums">
                            {formatRwf(amount)}
                        </dd>
                    </div>
                ))}
            </dl>
            <ul className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {schedule.instalments.map((row) => (
                    <li
                        key={row.index}
                        className="flex items-center justify-between gap-3 border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                    >
                        <div>
                            <p className="text-[13.5px] font-semibold text-rz-ink tabular-nums">
                                {formatRwf(row.total)}
                            </p>
                            <p className="text-[11.5px] text-rz-secondary">
                                {t('business.servicing.repay.row.due', {
                                    index: row.index,
                                    date: formatDate(row.due_on, locale),
                                })}
                            </p>
                        </div>
                        <p className="text-right text-[11.5px] text-rz-secondary tabular-nums">
                            {t('business.repayments.issued.split', {
                                principal: formatRwf(row.principal),
                                return: formatRwf(row.return),
                            })}
                        </p>
                    </li>
                ))}
            </ul>
            <p className="mt-2 text-[11.5px] text-rz-secondary">
                {t('business.repayments.issued.note')}
            </p>
        </section>
    );
}
