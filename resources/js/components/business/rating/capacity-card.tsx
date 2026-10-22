import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteLink } from '@/types';
import type { CapacityLimit, CapacitySizing } from '@/types/business';

/**
 * "How your capacity is sized" (design L853–921), on the BRS sizing the engine runs: the cash the
 * business keeps each month, the cover every instalment needs, and the three limits on one note.
 * Every figure, the binding limit and the headroom are the engine's; the card only lays them out.
 */
export function CapacityCard({
    sizing,
    raise,
}: {
    sizing: CapacitySizing;
    raise: RouteLink | null;
}) {
    const { t } = useTranslation();

    const limitLabel = (limit: CapacityLimit): string =>
        t(`business.rating.limit.${limit.key}`);

    const limitDetail = (limit: CapacityLimit): string => {
        switch (limit.key) {
            case 'cash_flow':
                return t('business.rating.limit.cash_flow_detail', {
                    cover: sizing.target_cover,
                    months: sizing.term_months,
                });
            case 'exposure':
                return t('business.rating.limit.exposure_detail', {
                    limit: formatRwf(limit.limit),
                    committed: formatRwf(limit.committed),
                });
            default:
                return t('business.rating.limit.policy_max_detail');
        }
    };

    const bound =
        sizing.limits.find((limit) => limit.binding) ?? sizing.limits[0];

    return (
        <>
            <h2 className="mt-5 text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                {t('business.rating.sizing.title')}
            </h2>
            <div className="mt-[11px] rounded-2xl border border-rz-border bg-rz-surface p-4">
                <div className="flex items-baseline justify-between gap-2.5 py-[9px]">
                    <div className="min-w-0">
                        <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-secondary uppercase">
                            {t('business.rating.sizing.cash')}
                        </p>
                        <p className="mt-0.5 text-[11px] leading-[1.45] text-rz-secondary">
                            {t('business.rating.sizing.cash_note')}
                        </p>
                    </div>
                    <p className="shrink-0 text-[17px] font-bold tracking-[-.3px] text-rz-ink">
                        {formatRwf(sizing.cash_per_month)}
                    </p>
                </div>
                <div className="flex items-baseline justify-between gap-2.5 border-t border-[#eef2f9] py-[9px] dark:border-rz-divider">
                    <div className="min-w-0">
                        <p className="text-[10.5px] font-bold tracking-[.04em] text-rz-secondary uppercase">
                            {t('business.rating.sizing.cover')}
                        </p>
                        <p className="mt-0.5 text-[11px] leading-[1.45] text-rz-secondary">
                            {t('business.rating.sizing.cover_note', {
                                cover: sizing.target_cover,
                            })}
                        </p>
                    </div>
                    <p className="shrink-0 text-[17px] font-bold tracking-[-.3px] text-[#1832c8] dark:text-[#99a3ff]">
                        {t('business.rating.sizing.cover_value', {
                            cover: sizing.target_cover,
                        })}
                    </p>
                </div>
                <div className="flex items-baseline justify-between gap-2.5 border-t-2 border-rz-ink pt-2.5 pb-[3px]">
                    <p className="text-[12.5px] font-bold text-rz-ink">
                        {t('business.rating.sizing.term')}
                    </p>
                    <p className="shrink-0 text-[15px] font-bold tracking-[-.3px] text-rz-ink">
                        {t('business.rating.sizing.term_value', {
                            months: sizing.term_months,
                            rate: sizing.rate_percent,
                        })}
                    </p>
                </div>

                <p className="mt-4 text-[10.5px] font-bold tracking-[.04em] text-rz-secondary uppercase">
                    {t('business.rating.limits')}
                </p>
                <ul className="mt-1.5">
                    {sizing.limits.map((limit) => (
                        <li
                            key={limit.key}
                            className="flex items-start gap-2.5 border-b border-[#eef2f9] py-2.5 last:border-b-0 dark:border-rz-divider"
                        >
                            <div className="min-w-0 flex-1">
                                <p
                                    className={cn(
                                        'text-[12.5px] font-semibold',
                                        limit.binding
                                            ? 'text-[#1832c8] dark:text-[#99a3ff]'
                                            : 'text-rz-ink',
                                    )}
                                >
                                    {limitLabel(limit)}
                                </p>
                                <p className="mt-0.5 text-[11px] leading-[1.45] text-rz-secondary">
                                    {limitDetail(limit)}
                                </p>
                                {limit.binding && (
                                    <p className="text-[10px] font-bold tracking-[.05em] text-[#1832c8] uppercase dark:text-[#99a3ff]">
                                        {t('business.rating.limit.yours')}
                                    </p>
                                )}
                            </div>
                            <p
                                className={cn(
                                    'shrink-0 text-[13px] font-bold',
                                    limit.binding
                                        ? 'text-[#1832c8] dark:text-[#99a3ff]'
                                        : 'text-rz-ink',
                                )}
                            >
                                {formatRwf(limit.value)}
                            </p>
                        </li>
                    ))}
                </ul>
                <div className="mt-3 flex items-center justify-between border-t-2 border-rz-ink pt-[11px] pb-1">
                    <span className="text-[13px] font-bold text-rz-ink">
                        {t('business.rating.approved')}
                    </span>
                    <span className="text-base font-bold text-rz-accent-app-text">
                        {formatRwf(sizing.approved)}
                    </span>
                </div>
                <div className="mt-2.5 rounded-xl border border-[#dbe4f7] bg-[#eef3fb] p-3 dark:border-transparent dark:bg-[rgba(61,87,255,.14)]">
                    <p className="text-[10.5px] font-bold tracking-[.04em] text-[#1832c8] uppercase dark:text-[#99a3ff]">
                        {t('business.rating.bound', {
                            limit: limitLabel(bound),
                        })}
                    </p>
                    <p className="mt-1 text-[11.5px] leading-normal text-rz-secondary">
                        {limitDetail(bound)}
                    </p>
                    <p className="mt-1 text-[11.5px] leading-normal font-semibold text-[#1832c8] dark:text-[#99a3ff]">
                        {t('business.rating.lift', {
                            how: t(`business.rating.limit.${bound.key}_lift`),
                        })}
                    </p>
                </div>
                <div className="mt-[11px] flex items-center gap-[11px] rounded-xl border border-[#cfe9d8] bg-[#f0f9f3] p-3 dark:border-transparent dark:bg-rz-accent-soft">
                    <div className="flex-1">
                        <p className="text-[10.5px] font-bold tracking-[.02em] text-[#3d7a68] uppercase dark:text-rz-accent-app-text">
                            {t('business.rating.headroom')}
                        </p>
                        <p className="mt-0.5 text-[17px] font-bold text-rz-accent-app-text">
                            {formatRwf(sizing.headroom)}
                        </p>
                        <p className="text-[11px] text-rz-accent-app-text">
                            {t('business.rating.headroom_note')}
                        </p>
                    </div>
                    {raise !== null && (
                        <Link
                            href={raise}
                            className="shrink-0 rounded-[10px] bg-rz-accent-fill px-[15px] py-[11px] text-[13px] font-semibold text-white"
                        >
                            {t('business.rating.raise')}
                        </Link>
                    )}
                </div>
                <p className="mt-2.5 text-[11px] leading-normal text-rz-secondary">
                    {t('business.rating.sizing.explainer')}
                </p>
            </div>
        </>
    );
}
