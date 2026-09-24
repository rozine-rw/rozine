import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import type { BusinessRepaymentsProps } from '@/types/business';

/** "Repayment progress" (design L1081–1094). */
export function ProgressCard({
    progress,
}: Pick<BusinessRepaymentsProps, 'progress'>) {
    const { t } = useTranslation();
    const title = t('business.repayments.progress');

    return (
        <div className="mt-[18px] rounded-2xl border border-rz-border bg-rz-surface p-4">
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
                aria-valuenow={progress.repaid_pct}
                className="mt-[11px] h-[9px] overflow-hidden rounded-[5px] bg-rz-page"
            >
                <div
                    className="h-full rounded-[5px] bg-rz-accent-fill"
                    style={{ width: `${progress.repaid_pct}%` }}
                />
            </div>
            <div className="mt-[13px] flex gap-2.5">
                <div className="min-w-0 flex-1 rounded-xl border border-[#eef2f9] bg-[#f7f9fc] px-3 py-[11px] dark:border-rz-border dark:bg-rz-page">
                    <p className="text-[10.5px] font-bold tracking-[.02em] whitespace-nowrap text-rz-slate uppercase">
                        {t('business.note.tracker.repaid')}
                    </p>
                    <p className="mt-[3px] text-base font-bold tracking-[-.4px] text-rz-ink">
                        {formatRwf(progress.repaid)}
                    </p>
                    <p className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {t('business.repayments.payments', {
                            made: progress.payments_made,
                            total: progress.payments_total,
                        })}
                    </p>
                </div>
                <div className="min-w-0 flex-1 rounded-xl border border-[#eef2f9] bg-[#f7f9fc] px-3 py-[11px] dark:border-rz-border dark:bg-rz-page">
                    <p className="text-[10.5px] font-bold tracking-[.02em] text-rz-slate uppercase">
                        {t('business.repayments.remaining')}
                    </p>
                    <p className="mt-[3px] text-base font-bold tracking-[-.4px] text-rz-ink">
                        {formatRwf(progress.remaining)}
                    </p>
                    <p className="mt-0.5 text-[10.5px] text-rz-secondary">
                        {progress.remaining_months === 0
                            ? t('business.note.tracker.fully_repaid')
                            : t(
                                  progress.remaining_months === 1
                                      ? 'business.note.tracker.over_month'
                                      : 'business.note.tracker.over_months',
                                  {
                                      count: progress.remaining_months,
                                  },
                              )}
                    </p>
                </div>
            </div>
            <div className="mt-2.5 flex items-baseline justify-between gap-2.5 border-t border-[#eef2f9] pt-[11px] dark:border-rz-divider">
                <p className="min-w-0">
                    <span className="text-[10.5px] font-bold tracking-[.02em] text-rz-slate uppercase">
                        {t('business.repayments.total')}
                    </span>
                    <span className="ml-[7px] text-[10.5px] text-rz-secondary">
                        {t('business.repayments.total_note')}
                    </span>
                </p>
                <p className="text-[17px] font-bold tracking-[-.4px] whitespace-nowrap text-rz-ink">
                    {formatRwf(progress.total)}
                </p>
            </div>
        </div>
    );
}
