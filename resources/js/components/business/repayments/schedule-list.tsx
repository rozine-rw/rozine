import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessInstalment } from '@/types/business';
import type { InstalmentStatus } from '@/types/settlement';

const PILL: Record<InstalmentStatus, string> = {
    paid: 'bg-rz-accent-soft text-rz-accent-app-text',
    processing: 'bg-rz-accent-soft text-rz-accent-app-text',
    due: 'bg-[rgba(194,102,31,.10)] text-rz-ink',
    partially_paid: 'bg-[rgba(194,102,31,.10)] text-rz-ink',
    overdue: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
    upcoming: 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
};

/**
 * "Repayment schedule" (design L1190–1199), as the server keeps it: each instalment's scheduled
 * total, what is still outstanding once it is due, and every late-fee step applied to it, line by
 * line. A `processing` instalment is recorded but not yet allocated, so it never reads as paid.
 */
export function ScheduleList({ schedule }: { schedule: BusinessInstalment[] }) {
    const { t, locale } = useTranslation();

    if (schedule.length === 0) {
        return null;
    }

    return (
        <section aria-label={t('business.repayments.schedule')}>
            <h2 className="mt-[18px] text-base font-semibold text-rz-ink">
                {t('business.repayments.schedule')}
            </h2>
            <ul className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {schedule.map((row) => {
                    const owing =
                        row.status === 'overdue' ||
                        row.status === 'partially_paid';

                    return (
                        <li
                            key={row.index}
                            className="border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-[13.5px] font-semibold text-rz-ink">
                                        {formatRwf(
                                            owing
                                                ? row.outstanding.total
                                                : row.scheduled.total,
                                        )}
                                    </p>
                                    <p className="text-[11.5px] text-rz-secondary">
                                        {row.paid_on === null
                                            ? t(
                                                  'business.servicing.repay.row.due',
                                                  {
                                                      index: row.index,
                                                      date: formatDate(
                                                          row.due_on,
                                                          locale,
                                                      ),
                                                  },
                                              )
                                            : t(
                                                  'business.servicing.repay.row.paid',
                                                  {
                                                      index: row.index,
                                                      date: formatDate(
                                                          row.paid_on,
                                                          locale,
                                                      ),
                                                  },
                                              )}
                                    </p>
                                </div>
                                <span
                                    className={cn(
                                        'rounded-[10px] px-[9px] py-1 text-[11px] font-semibold whitespace-nowrap',
                                        PILL[row.status],
                                    )}
                                >
                                    {t(
                                        `business.servicing.repay.status.${row.status}`,
                                    )}
                                </span>
                            </div>
                            {row.late_fees.length > 0 && (
                                <ul
                                    aria-label={t(
                                        'business.servicing.repay.late_lines',
                                    )}
                                    className="mt-2 rounded-xl bg-[rgba(229,72,77,.06)] px-3 py-1.5"
                                >
                                    {row.late_fees.map((line) => (
                                        <li
                                            key={line.id}
                                            className="flex justify-between gap-3 py-1 text-[11.5px]"
                                        >
                                            <span className="text-rz-secondary">
                                                {t(
                                                    'business.servicing.repay.late_line',
                                                    {
                                                        step: t(
                                                            `business.servicing.repay.step.${line.step}`,
                                                        ),
                                                        date: formatDate(
                                                            line.applies_on,
                                                            locale,
                                                        ),
                                                        status: t(
                                                            `business.servicing.repay.fee_status.${line.status}`,
                                                        ),
                                                    },
                                                )}
                                            </span>
                                            <span className="font-semibold text-rz-danger-text">
                                                {formatRwf(line.outstanding)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
