import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessRepaymentsProps } from '@/types/business';

const PILL = {
    paid: 'bg-rz-accent-soft text-rz-accent-app-text',
    due: 'bg-[rgba(194,102,31,.10)] text-rz-ink',
    overdue: 'bg-[rgba(229,72,77,.10)] text-rz-danger-text',
    upcoming: 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
} as const;

/** "Repayment schedule" (design L1190–1199), as the server keeps it. */
export function ScheduleList({
    schedule,
}: Pick<BusinessRepaymentsProps, 'schedule'>) {
    const { t, locale } = useTranslation();

    return (
        <>
            <h2 className="mt-[18px] text-base font-semibold text-rz-ink">
                {t('business.repayments.schedule')}
            </h2>
            <ul className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {schedule.map((row) => (
                    <li
                        key={row.due_on}
                        className="flex items-center justify-between border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider"
                    >
                        <div>
                            <p className="text-[13.5px] font-semibold text-rz-ink">
                                {formatRwf(row.amount)}
                            </p>
                            <p className="text-[11.5px] text-rz-secondary">
                                {row.status === 'upcoming'
                                    ? formatDate(row.due_on, locale)
                                    : t(
                                          `business.repayments.row.${row.status}`,
                                          {
                                              date: formatDate(
                                                  row.due_on,
                                                  locale,
                                              ),
                                          },
                                      )}
                            </p>
                        </div>
                        <span
                            className={cn(
                                'inline-flex items-center gap-[5px] rounded-[10px] px-[9px] py-1 text-[11px] font-semibold',
                                PILL[row.status],
                            )}
                        >
                            {t(`business.repayments.status.${row.status}`)}
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}
