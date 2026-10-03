import { Link } from '@inertiajs/react';
import { ComponentRows } from '@/components/business/repayments/components';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessRepaymentsProps,
    RepaymentReceiptView,
} from '@/types/business';

/**
 * How far a repayment's allocation has got. It reads as shared only once the server reports it
 * allocated with its Investor count; anything less reads as still being shared.
 */
function useAllocation(): (item: RepaymentReceiptView) => {
    shared: boolean;
    text: string;
} {
    const { t } = useTranslation();

    return (item) =>
        item.allocation === 'allocated' && item.investors_paid !== null
            ? {
                  shared: true,
                  text: t('business.servicing.repay.allocated', {
                      count: item.investors_paid,
                  }),
              }
            : { shared: false, text: t('business.servicing.repay.allocating') };
}

/**
 * A recorded repayment (design L1203–1262 "Payment processed", C4 v1 §4a). Its receipt is
 * immutable; allocation reads only "being shared" or "shared with N investors", never an
 * exception or who they are (H15/H16). Recording the payment is not the same as allocating it.
 */
export function RepaymentReceipt({
    receipt,
    close,
}: {
    receipt: RepaymentReceiptView;
    close: BusinessRepaymentsProps['links']['close'];
}) {
    const { t, locale } = useTranslation();
    const { shared: allocated, text } = useAllocation()(receipt);

    return (
        <section
            aria-label={t('business.servicing.repay.receipt_title')}
            className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <div className="flex items-center gap-2.5">
                <span
                    className={cn(
                        'flex size-9 items-center justify-center rounded-full',
                        allocated
                            ? 'bg-rz-accent-soft'
                            : 'bg-[rgba(194,102,31,.10)]',
                    )}
                >
                    <Icon
                        name={allocated ? 'check-badge' : 'hourglass'}
                        tone={allocated ? 'green' : 'amber'}
                    />
                </span>
                <div>
                    <h2 className="text-sm font-semibold text-rz-ink">
                        {t('business.servicing.repay.receipt_title')}
                    </h2>
                    <p role="status" className="text-xs text-rz-secondary">
                        {text}
                    </p>
                </div>
            </div>
            <p className="mt-3 text-2xl font-bold tracking-[-.5px] text-rz-ink">
                {formatRwf(receipt.amount)}
            </p>
            <ComponentRows amounts={receipt.applied} className="mt-2" />
            {receipt.unapplied.amount !== '0' && (
                <p className="mt-2 text-xs text-rz-secondary">
                    {t('business.servicing.repay.unapplied', {
                        amount: formatRwf(receipt.unapplied),
                    })}
                </p>
            )}
            <dl className="mt-3 border-t border-[#eef2f9] pt-2 text-xs dark:border-rz-divider">
                <div className="flex justify-between gap-3 py-1">
                    <dt className="text-rz-secondary">
                        {t('business.wallet.detail.when')}
                    </dt>
                    <dd className="font-semibold text-rz-ink">
                        {formatDateTime(receipt.receipt.recorded_at, locale)}
                    </dd>
                </div>
                <div className="flex justify-between gap-3 py-1">
                    <dt className="text-rz-secondary">
                        {t('business.wallet.detail.reference')}
                    </dt>
                    <dd className="font-semibold break-all text-rz-ink">
                        {receipt.receipt.reference}
                    </dd>
                </div>
            </dl>
            <Link
                href={close}
                className="mt-3 flex h-[46px] items-center justify-center rounded-xl border border-rz-border text-sm font-semibold text-rz-ink"
            >
                {t('business.servicing.repay.receipt_done')}
            </Link>
        </section>
    );
}

/** Recent repayments, newest first, each opening its receipt. */
export function RecentRepayments({
    recent,
}: {
    recent: RepaymentReceiptView[];
}) {
    const { t, locale } = useTranslation();
    const allocation = useAllocation();

    if (recent.length === 0) {
        return null;
    }

    return (
        <section
            aria-label={t('business.servicing.repay.recent')}
            className="mt-[18px]"
        >
            <h2 className="text-base font-semibold text-rz-ink">
                {t('business.servicing.repay.recent')}
            </h2>
            <ul className="mt-3 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {recent.map((item) => (
                    <li
                        key={item.id}
                        className="border-b border-[#eef2f9] last:border-0 dark:border-rz-divider"
                    >
                        <Link
                            href={item.link}
                            preserveScroll
                            className="flex items-center justify-between gap-3 px-[15px] py-3"
                        >
                            <span>
                                <span className="block text-[13px] font-semibold text-rz-ink">
                                    {formatRwf(item.amount)}
                                </span>
                                <span className="block text-[11.5px] text-rz-secondary">
                                    {formatDate(
                                        item.receipt.recorded_at,
                                        locale,
                                    )}
                                </span>
                            </span>
                            <span className="text-[11px] font-semibold text-rz-secondary">
                                {allocation(item).text}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
