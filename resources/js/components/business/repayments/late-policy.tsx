import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { BusinessRepaymentsProps } from '@/types/business';

const DOT = {
    due_day: 'bg-[#c2661f] shadow-[0_0_0_4px_rgba(194,102,31,.10)]',
    day_7: 'bg-[#f5811f] shadow-[0_0_0_4px_rgba(194,102,31,.10)]',
    day_30: 'bg-[#b3383c] shadow-[0_0_0_4px_rgba(229,72,77,.10)]',
} as const;

type LatePolicyProps = Pick<BusinessRepaymentsProps, 'late_ladder'> &
    Pick<BusinessRepaymentsProps['links'], 'defer' | 'review'>;

/**
 * "If a payment is late" (design L1214–1260): the approved late-fee ladder with what this
 * instalment would come to at each step. Deferral and manual review show only where offered.
 */
export function LatePolicy({
    late_ladder: ladder,
    defer,
    review,
}: LatePolicyProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    return (
        <div className="mt-[18px] overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-center gap-[11px] bg-rz-surface p-[15px] text-left"
            >
                <span className="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-[rgba(229,72,77,.10)] text-[15px]">
                    <Icon name="warning" tone="red" />
                </span>
                <span className="flex-1 text-sm font-semibold text-rz-ink">
                    {t('business.repayments.late.title')}
                </span>
                <span
                    aria-hidden
                    className={cn(
                        'text-rz-secondary transition-transform duration-200',
                        open && 'rotate-90',
                    )}
                >
                    ›
                </span>
            </button>
            {open && (
                <div className="px-[15px] pt-0.5 pb-4">
                    <div className="mb-3.5 h-px bg-rz-page" />
                    <p className="mb-3.5 text-[12.5px] leading-normal text-rz-secondary">
                        {t('business.repayments.late.intro')}
                    </p>
                    <ol>
                        {ladder.map((rung) => (
                            <li key={rung.step} className="flex gap-3">
                                <span
                                    className={cn(
                                        'mt-[3px] size-[11px] shrink-0 rounded-full',
                                        DOT[rung.step],
                                    )}
                                />
                                <div className="-mt-0.5 -ml-1.5 flex-1 border-l-[1.5px] border-[#eef2f9] pb-4 pl-[17px] dark:border-rz-divider">
                                    <div className="flex items-baseline justify-between gap-2">
                                        <span className="text-[13.5px] font-semibold text-rz-ink">
                                            {t(
                                                `business.repayments.late.${rung.step}`,
                                            )}
                                        </span>
                                        <span
                                            className={cn(
                                                'text-[13px] font-bold',
                                                rung.step === 'due_day'
                                                    ? 'text-rz-ink'
                                                    : 'text-rz-danger-text',
                                            )}
                                        >
                                            {t(
                                                rung.step === 'day_30'
                                                    ? 'business.repayments.late.fee_legal'
                                                    : 'business.repayments.late.fee',
                                                { percent: rung.fee_percent },
                                            )}
                                        </span>
                                    </div>
                                    <p className="mt-0.5 text-xs text-rz-secondary">
                                        {t(
                                            `business.repayments.late.${rung.step}_body`,
                                            { percent: rung.fee_percent },
                                        )}
                                    </p>
                                    <p className="mt-[5px] text-[11.5px] font-semibold text-rz-ink">
                                        {t('business.repayments.late.total', {
                                            amount: formatRwf(rung.total),
                                        })}
                                    </p>
                                </div>
                            </li>
                        ))}
                        <li className="flex gap-3">
                            <span className="mt-[3px] size-[11px] shrink-0 rounded-full bg-rz-page" />
                            <div className="-mt-0.5 -ml-1.5 flex-1 pl-[17px]">
                                <p className="text-[13.5px] font-semibold text-rz-ink">
                                    {t('business.repayments.late.halted')}
                                </p>
                                <p className="mt-0.5 text-xs text-rz-secondary">
                                    {t('business.repayments.late.halted_body')}
                                </p>
                            </div>
                        </li>
                    </ol>
                    {defer !== null && (
                        <div className="mt-3.5 rounded-xl border border-[#cfe9d8] bg-[#f0f9f3] p-[13px] dark:border-transparent dark:bg-rz-accent-soft">
                            <p className="text-[12.5px] font-bold text-rz-accent-app-text">
                                {t('business.repayments.late.warn_title')}
                            </p>
                            <p className="mt-1 text-[11.5px] leading-[1.55] text-[#3d7a68] dark:text-rz-accent-app-text">
                                {t('business.repayments.late.warn_body')}
                            </p>
                            <Link
                                href={defer}
                                className="mt-[11px] block w-full rounded-[10px] bg-rz-accent-fill p-[11px] text-center text-[12.5px] font-bold text-white"
                            >
                                {t('business.repayments.late.defer')}
                            </Link>
                        </div>
                    )}
                    {review !== null && (
                        <Link
                            href={review}
                            className="mt-2.5 block w-full rounded-[10px] border border-rz-border bg-[#f3f6fc] p-[11px] text-center text-[12.5px] font-semibold text-rz-accent-app-text dark:bg-rz-page"
                        >
                            {t('business.repayments.late.review')}
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}
