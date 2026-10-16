import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { ComponentAmounts } from '@/types/settlement';

const PARTS = ['principal', 'return', 'service_fee', 'late_fees'] as const;

/**
 * The server's split of an amount: principal and return always, a service fee or late fees only
 * when the server sends one above zero, then the server's total. Nothing is summed here.
 */
export function ComponentRows({
    amounts,
    className,
}: {
    amounts: ComponentAmounts;
    className?: string;
}) {
    const { t } = useTranslation();
    const shown = PARTS.filter(
        (part) =>
            part === 'principal' ||
            part === 'return' ||
            amounts[part].amount !== '0',
    );

    return (
        <dl className={cn('text-xs', className)}>
            {shown.map((part) => (
                <div key={part} className="flex justify-between gap-3 py-1">
                    <dt className="text-rz-secondary">
                        {t(`business.servicing.repay.part.${part}`)}
                    </dt>
                    <dd
                        className={cn(
                            'font-semibold text-rz-ink',
                            part === 'late_fees' && 'text-rz-danger-text',
                        )}
                    >
                        {formatRwf(amounts[part])}
                    </dd>
                </div>
            ))}
            <div className="mt-1 flex justify-between gap-3 border-t border-[#eef2f9] pt-2 dark:border-rz-divider">
                <dt className="font-semibold text-rz-ink">
                    {t('business.servicing.repay.part.total')}
                </dt>
                <dd className="font-bold text-rz-ink">
                    {formatRwf(amounts.total)}
                </dd>
            </div>
        </dl>
    );
}
