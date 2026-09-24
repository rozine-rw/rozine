import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwf } from '@/lib/rozine/format';
import type { RouteLink } from '@/types';
import type { BusinessRepaymentsProps } from '@/types/business';

/** "Payment processed" (design L1066–1077), from the server's record of the payment. */
export function Receipt({
    receipt,
    home,
}: {
    receipt: NonNullable<BusinessRepaymentsProps['receipt']>;
    home: RouteLink;
}) {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            className="animate-[rz-fade_.3s_ease] pt-[50px] text-center"
        >
            <div className="mx-auto flex size-[86px] animate-[rz-pop_.5s_ease] items-center justify-center rounded-full bg-rz-accent-soft">
                <div className="flex size-[58px] items-center justify-center rounded-full bg-rz-accent-fill">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden
                        className="size-[30px]"
                    >
                        <path
                            d="M5 13l4 4L19 7"
                            stroke="#fff"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>
            </div>
            <h2 className="mt-[22px] text-[22px] font-semibold text-rz-ink">
                {t('business.repayments.done.title')}
            </h2>
            <p className="mt-2.5 text-sm text-rz-secondary">
                {t('business.repayments.done.body', {
                    amount: formatRwf(receipt.amount),
                    count: formatCount(receipt.investors),
                })}
            </p>
            <dl className="mt-[18px] rounded-2xl border border-rz-border bg-rz-surface p-4 text-left">
                <div className="flex justify-between py-[5px]">
                    <dt className="text-[13px] text-rz-secondary">
                        {t('business.repayments.done.outstanding')}
                    </dt>
                    <dd className="text-[13px] font-semibold text-rz-ink">
                        {formatRwf(receipt.outstanding)}
                    </dd>
                </div>
                <div className="flex justify-between py-[5px]">
                    <dt className="text-[13px] text-rz-secondary">
                        {t('business.repayments.done.made')}
                    </dt>
                    <dd className="text-[13px] font-semibold text-rz-accent-app-text">
                        {t('business.note.payments_made', {
                            made: receipt.payments_made,
                            total: receipt.payments_total,
                        })}
                    </dd>
                </div>
            </dl>
            <Link
                href={home}
                className="mt-5 flex h-[50px] w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white"
            >
                {t('business.repayments.done.home')}
            </Link>
        </div>
    );
}
