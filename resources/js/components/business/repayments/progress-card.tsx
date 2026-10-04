import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import type { BusinessRepaymentsProps } from '@/types/business';
import type { RouteLink } from '@/types/routing';

/** A headline figure links to its basis only when the server gives one (C4 v1 convention 4). */
export function Figure({
    basis,
    children,
}: {
    basis: RouteLink | undefined;
    children: ReactNode;
}) {
    return basis === undefined ? (
        children
    ) : (
        <Link
            href={basis}
            className="underline decoration-rz-border underline-offset-4"
        >
            {children}
        </Link>
    );
}

/** "Repayment progress" (design L1081–1094): the server's repaid, remaining and total figures. */
export function ProgressCard({
    progress,
    bases,
}: {
    progress: BusinessRepaymentsProps['servicing']['progress'];
    bases: BusinessRepaymentsProps['bases'];
}) {
    const { t } = useTranslation();
    const title = t('business.repayments.progress');

    return (
        <section
            aria-label={title}
            className="mt-[18px] rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <div className="flex items-center justify-between">
                <span className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                    {title}
                </span>
                <span className="text-[11px] font-semibold text-rz-accent-app-text">
                    {t('business.note.tracker.repaid_pct', {
                        pct: progress.repaid_pct,
                    })}
                </span>
            </div>
            <div
                role="progressbar"
                aria-label={title}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={Number(progress.repaid_pct)}
                className="mt-[11px] h-[9px] overflow-hidden rounded-[5px] bg-rz-page"
            >
                <div
                    className="h-full rounded-[5px] bg-rz-accent-fill"
                    style={{ width: `${progress.repaid_pct}%` }}
                />
            </div>
            <dl className="mt-[13px] flex gap-2.5">
                <div className="min-w-0 flex-1 rounded-xl border border-[#eef2f9] bg-[#f7f9fc] px-3 py-[11px] dark:border-rz-border dark:bg-rz-page">
                    <dt className="text-[10.5px] font-bold tracking-[.02em] whitespace-nowrap text-rz-slate uppercase">
                        {t('business.note.tracker.repaid')}
                    </dt>
                    <dd className="mt-[3px] text-base font-bold tracking-[-.4px] text-rz-ink">
                        <Figure basis={bases.repaid}>
                            {formatRwf(progress.repaid)}
                        </Figure>
                    </dd>
                    <dd className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {t('business.repayments.payments', {
                            made: progress.payments_made,
                            total: progress.payments_total,
                        })}
                    </dd>
                </div>
                <div className="min-w-0 flex-1 rounded-xl border border-[#eef2f9] bg-[#f7f9fc] px-3 py-[11px] dark:border-rz-border dark:bg-rz-page">
                    <dt className="text-[10.5px] font-bold tracking-[.02em] text-rz-slate uppercase">
                        {t('business.repayments.remaining')}
                    </dt>
                    <dd className="mt-[3px] text-base font-bold tracking-[-.4px] text-rz-ink">
                        <Figure basis={bases.remaining}>
                            {formatRwf(progress.remaining)}
                        </Figure>
                    </dd>
                    <dd className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {progress.remaining_instalments === 0
                            ? t('business.note.tracker.fully_repaid')
                            : t('business.servicing.repay.instalments_left', {
                                  count: progress.remaining_instalments,
                              })}
                    </dd>
                </div>
            </dl>
            <div className="mt-2.5 flex items-baseline justify-between gap-2.5 border-t border-[#eef2f9] pt-[11px] dark:border-rz-divider">
                <span className="text-[10.5px] font-bold tracking-[.02em] text-rz-slate uppercase">
                    {t('business.repayments.total')}
                </span>
                <span className="text-[17px] font-bold tracking-[-.4px] whitespace-nowrap text-rz-ink">
                    {formatRwf(progress.total)}
                </span>
            </div>
        </section>
    );
}
