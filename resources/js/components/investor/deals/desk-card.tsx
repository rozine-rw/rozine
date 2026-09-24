import {
    PhotoStrip,
    RatingGlassBadge,
} from '@/components/investor/deals/deal-bits';
import { useTimeLeft } from '@/components/investor/deals/time-left';
import { ACCENT_FILL } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwf, formatRwfShort } from '@/lib/rozine/format';
import type { DealCard } from '@/types/investor';

/**
 * The wide-screen deal card (design L205–241), the Deals deck's own markup: a 196px photo banner,
 * raised against target, funding, time left, investors, a three-line pitch and what is left.
 */
export function DeskCard({
    deal,
    serverTime,
}: {
    deal: DealCard;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const clock = useTimeLeft(deal.closes_at, serverTime);

    return (
        <article
            aria-label={deal.name}
            className="flex size-full flex-col overflow-hidden rounded-[20px] border border-[#eef1f7] bg-rz-surface shadow-[0_26px_50px_-24px_rgba(20,45,95,.4)] dark:border-rz-border"
        >
            <div className="relative h-[196px] min-h-0 overflow-hidden">
                <PhotoStrip
                    photos={deal.photos}
                    accent={deal.accent}
                    name={deal.name}
                    variant="desk"
                >
                    <span className="pointer-events-none absolute inset-0 z-[1] bg-[linear-gradient(180deg,rgba(8,14,28,.22)_0%,rgba(8,14,28,0)_40%,rgba(8,14,28,.9)_100%)]" />
                </PhotoStrip>
                <div className="absolute top-[13px] right-3.5 z-[2]">
                    <RatingGlassBadge rating={deal.rating} variant="desk" />
                </div>
                <div className="pointer-events-none absolute inset-x-4 bottom-3.5 z-[2] flex items-center gap-[11px]">
                    <span
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl bg-white text-[19px] font-extrabold shadow-[0_5px_14px_-6px_rgba(0,0,0,.4)]"
                        style={{ color: ACCENT_FILL[deal.accent] }}
                    >
                        {deal.name.charAt(0)}
                    </span>
                    <div className="min-w-0">
                        <div className="flex items-center gap-1.5">
                            <span className="truncate text-[19px] font-bold whitespace-nowrap text-white">
                                {deal.name}
                            </span>
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden
                                className="size-4 shrink-0"
                            >
                                <circle cx="12" cy="12" r="10" fill="#1e3aff" />
                                <path
                                    d="M8 12l2.5 2.5L16 9"
                                    stroke="#fff"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        </div>
                        <p className="text-[12.5px] text-white/85">
                            {deal.industry} · {deal.district}
                        </p>
                    </div>
                </div>
            </div>
            <div className="flex flex-1 flex-col px-[18px] pt-3.5 pb-4">
                <div className="flex items-start justify-between">
                    <div>
                        <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('investor.deals.raised')}
                        </p>
                        <p className="mt-0.5 text-[15px] font-bold text-rz-ink">
                            {formatRwfShort(deal.raised)}{' '}
                            <span className="text-[11.5px] font-semibold text-rz-secondary">
                                / {formatRwfShort(deal.target)}
                            </span>
                        </p>
                    </div>
                    <div className="text-right">
                        <p className="text-[10px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('investor.deals.avg_revenue')}
                        </p>
                        <p className="mt-0.5 text-[15px] font-bold text-rz-ink">
                            {formatRwfShort(deal.avg_monthly_revenue)}
                        </p>
                    </div>
                </div>
                <div className="mt-2.5 flex items-center gap-2.5">
                    <div
                        role="progressbar"
                        aria-label={t('investor.deals.funded_label', {
                            name: deal.name,
                        })}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={Number(deal.funded_pct)}
                        className="h-[7px] flex-1 overflow-hidden rounded-full bg-[#eef2f9] dark:bg-rz-surface-muted"
                    >
                        <div
                            className="h-full rounded-full bg-rz-accent-fill"
                            style={{ width: `${deal.funded_pct}%` }}
                        />
                    </div>
                    <span className="shrink-0 text-[12.5px] font-bold whitespace-nowrap text-rz-accent-app-text">
                        {deal.funded_pct}%
                    </span>
                </div>
                <div className="mt-[9px] flex items-center justify-between">
                    <span className="inline-flex items-center gap-[5px] text-[12.5px] font-semibold text-rz-ink tabular-nums">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden
                            className="size-[13px]"
                        >
                            <circle
                                cx="12"
                                cy="13"
                                r="8"
                                stroke="#c2661f"
                                strokeWidth="1.8"
                            />
                            <path
                                d="M12 9v4l2.5 2M9 3h6"
                                stroke="#c2661f"
                                strokeWidth="1.8"
                                strokeLinecap="round"
                            />
                        </svg>
                        {clock.label}
                    </span>
                    <span className="text-[12.5px] text-rz-secondary">
                        <span className="font-bold text-rz-ink">
                            {formatCount(deal.investors)}
                        </span>{' '}
                        {t('investor.deals.investors')}
                    </span>
                </div>
                <p className="mt-2.5 line-clamp-3 text-[13px] leading-normal text-[#46526b] dark:text-rz-body">
                    {deal.story}
                </p>
                <div className="mt-[13px] flex items-end justify-between gap-2.5 border-t border-[#eef1f7] pt-[13px] dark:border-rz-divider">
                    <div className="min-w-0">
                        <p className="text-[10px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase">
                            {t('investor.deals.left_to_fill')}
                        </p>
                        <p className="mt-[3px] text-[15px] font-bold tracking-[-.2px] whitespace-nowrap text-rz-ink">
                            {deal.status === 'sold_out'
                                ? t('investor.deals.fully_funded')
                                : formatRwf(deal.left_to_fill)}
                        </p>
                    </div>
                    <div className="min-w-0 text-right">
                        <p className="text-[10px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase">
                            {t('investor.deals.notes')}
                        </p>
                        <p className="mt-[3px] text-[15px] font-semibold whitespace-nowrap text-rz-ink">
                            {formatCount(deal.units_sold)}{' '}
                            <span className="font-medium text-rz-secondary">
                                {t('investor.deals.taken')}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </article>
    );
}
