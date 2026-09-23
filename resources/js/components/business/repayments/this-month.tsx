import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatOrdinal, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessRepaymentsProps } from '@/types/business';

/**
 * "This month's payment" (design L1096–1101). Overdue and defaulted notes say so in red; after a
 * payment ahead the card names the next due date instead.
 */
export function ThisMonth({
    thisMonth,
}: {
    thisMonth: BusinessRepaymentsProps['this_month'];
}) {
    const { t, locale } = useTranslation();
    const date = formatDate(thisMonth.due_on, locale);
    const line = {
        due: t(
            thisMonth.days === 1
                ? 'business.note.due_in_day'
                : 'business.note.due_in_days',
            { date, count: thisMonth.days },
        ),
        overdue: t(
            thisMonth.days === 1
                ? 'business.repayments.overdue_day'
                : 'business.repayments.overdue_days',
            { date, count: thisMonth.days },
        ),
        paid: t('business.repayments.paid_ahead', { date }),
        defaulted: t('business.repayments.defaulted'),
    }[thisMonth.state];

    return (
        <div
            className={cn(
                'mt-3 rounded-2xl border bg-rz-surface p-[18px]',
                thisMonth.state === 'overdue' || thisMonth.state === 'defaulted'
                    ? 'border-[#f4c7c9] dark:border-[rgba(255,107,111,.35)]'
                    : 'border-rz-border',
            )}
        >
            <p className="text-xs font-semibold text-rz-secondary uppercase">
                {t(`business.repayments.card_title.${thisMonth.state}`)}
            </p>
            <p className="mt-[5px] text-[23px] font-semibold text-rz-ink">
                {formatRwf(thisMonth.amount)}
            </p>
            <p
                className={cn(
                    'mt-1.5 text-[13px] font-semibold',
                    thisMonth.state === 'overdue' ||
                        thisMonth.state === 'defaulted'
                        ? 'text-rz-danger-text'
                        : 'text-rz-ink',
                )}
            >
                {line}
            </p>
            <p className="mt-2 flex items-start gap-[7px] text-[11px] leading-normal text-rz-ink">
                <span className="shrink-0">
                    <Icon name="repeat" />
                </span>
                <span>
                    {t('business.repayments.due_rule', {
                        day: formatOrdinal(thisMonth.due_day, locale, t),
                    })}
                </span>
            </p>
        </div>
    );
}
