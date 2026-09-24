import { Link } from '@inertiajs/react';
import {
    AuditedBadge,
    JustListedBadge,
    PhotoStrip,
    RatingGlassBadge,
    VerifiedRosette,
} from '@/components/investor/deals/deal-bits';
import { TONE_TEXT, useTimeLeft } from '@/components/investor/deals/time-left';
import { ACCENT_FILL } from '@/components/investor/tokens';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatCount, formatRwf, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { DealCard } from '@/types/investor';

type SwipeCardProps = {
    deal: DealCard;
    serverTime: string;
    /** Only the front card is interactive; the fanned cards behind are decoration. */
    front: boolean;
};

/**
 * The phone deal card (SwipeCard.dc.html L20–139): photos over a scrim with the audit, listing
 * and rating badges and the business identity; then raised against target, average monthly
 * revenue, funding, time left, investors, the pitch and what is left to fill. The design's
 * investor face avatars (random stock portraits) and save button (the watchlist, outside the MVP)
 * are left out.
 */
export function SwipeCard({ deal, serverTime, front }: SwipeCardProps) {
    const { t } = useTranslation();
    const clock = useTimeLeft(deal.closes_at, serverTime);

    return (
        <article
            aria-label={deal.name}
            className="flex size-full flex-col overflow-hidden rounded-[20px] border border-rz-border bg-rz-surface shadow-[0_26px_50px_-22px_rgba(20,45,95,.30),0_8px_20px_-12px_rgba(20,45,95,.10)] select-none"
        >
            <div className="relative min-h-0 flex-[0_0_46%] overflow-hidden bg-[#0b1c3a]">
                <PhotoStrip
                    photos={deal.photos}
                    accent={deal.accent}
                    name={deal.name}
                    variant="card"
                >
                    <span className="pointer-events-none absolute inset-x-0 top-0 z-[1] h-[70px] bg-[linear-gradient(180deg,rgba(8,16,34,.5),rgba(8,16,34,0))]" />
                    <span className="pointer-events-none absolute inset-0 z-[1] bg-[linear-gradient(180deg,rgba(8,16,34,0)_52%,rgba(8,16,34,.86)_100%)]" />
                </PhotoStrip>
                <div className="absolute top-[11px] right-3 z-[2] flex items-center gap-[7px]">
                    {deal.just_listed && <JustListedBadge />}
                    {deal.audited && <AuditedBadge />}
                    <RatingGlassBadge rating={deal.rating} />
                </div>
                <div className="pointer-events-none absolute inset-x-3.5 bottom-3 z-[2] flex items-center gap-[11px]">
                    <span
                        className="flex size-[42px] shrink-0 items-center justify-center rounded-xl bg-white text-lg font-bold shadow-[0_6px_14px_-6px_rgba(0,0,0,.5)]"
                        style={{ color: ACCENT_FILL[deal.accent] }}
                    >
                        {deal.name.charAt(0)}
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-[5px]">
                            <span className="truncate text-base font-semibold text-white [text-shadow:0_2px_8px_rgba(0,0,0,.4)]">
                                {deal.name}
                            </span>
                            <VerifiedRosette className="size-[15px] shrink-0" />
                        </div>
                        <p className="mt-0.5 text-[11px] font-medium text-white [text-shadow:0_1px_4px_rgba(0,0,0,.5)]">
                            {deal.industry} · {deal.district}
                        </p>
                    </div>
                </div>
            </div>

            <div className="relative flex min-h-0 flex-1 flex-col px-[15px] pt-[13px] pb-3">
                {front && (
                    <Link
                        href={deal.links.detail}
                        aria-label={t('investor.deals.open_deal', {
                            name: deal.name,
                        })}
                        className="absolute inset-0 z-[1]"
                    />
                )}
                <div className="flex items-start justify-between gap-2.5">
                    <div className="min-w-0">
                        <p className="text-[10.5px] font-semibold text-rz-secondary">
                            {t('investor.deals.raised')}
                        </p>
                        <p className="mt-0.5 text-xs whitespace-nowrap">
                            <span className="font-bold text-rz-ink">
                                {formatRwf(deal.raised)}
                            </span>{' '}
                            <span className="font-medium text-rz-secondary">
                                / {formatRwfShort(deal.target)}
                            </span>
                        </p>
                    </div>
                    <div className="shrink-0 text-right">
                        <p className="text-[10.5px] font-semibold text-rz-secondary">
                            {t('investor.deals.avg_revenue_short')}
                        </p>
                        <p className="mt-0.5 text-xs font-semibold whitespace-nowrap text-rz-ink">
                            {formatRwfShort(deal.avg_monthly_revenue)}
                        </p>
                    </div>
                </div>

                <div className="mt-[13px]">
                    <div className="flex items-center gap-[9px]">
                        <div
                            role="progressbar"
                            aria-label={t('investor.deals.funded_label', {
                                name: deal.name,
                            })}
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={Number(deal.funded_pct)}
                            className="h-1.5 flex-1 overflow-hidden rounded-[4px] bg-[#eef2f8] dark:bg-rz-surface-muted"
                        >
                            <div
                                className="h-full rounded-[4px] bg-[linear-gradient(90deg,#1e3aff,#3b82f6)]"
                                style={{ width: `${deal.funded_pct}%` }}
                            />
                        </div>
                        <span className="shrink-0 text-xs font-semibold whitespace-nowrap text-rz-accent-app-text">
                            {deal.funded_pct}%
                        </span>
                    </div>
                    <div className="mt-2 flex items-center justify-between gap-2 whitespace-nowrap">
                        <span
                            className={cn(
                                'inline-flex items-center gap-[3px] text-[11px] font-semibold tabular-nums',
                                TONE_TEXT[clock.tone],
                            )}
                        >
                            <Icon name="stopwatch" />
                            {clock.label}
                        </span>
                        <span className="flex items-center gap-1">
                            <span className="size-1.5 shrink-0 animate-[rz-live_1.8s_ease-out_infinite] rounded-full bg-[#17795a]" />
                            <span className="text-[11px] font-bold text-rz-ink">
                                {formatCount(deal.investors)}
                            </span>
                            <span className="text-[11px] font-semibold text-[#17795a] dark:text-[#3fcda0]">
                                {t('investor.deals.investors')}
                            </span>
                        </span>
                    </div>
                </div>

                <p className="mt-[11px] line-clamp-2 text-xs leading-normal text-[#5b6680] dark:text-rz-body">
                    {deal.story}
                </p>

                <div className="mt-[9px] grid grid-cols-2 items-end gap-2.5 border-t border-[#eef2f8] pt-2 dark:border-rz-divider">
                    <div className="min-w-0">
                        <p className="text-[10px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase">
                            {t('investor.deals.left_to_fill')}
                        </p>
                        <p className="mt-[3px] text-xs font-bold tracking-[-.2px] whitespace-nowrap text-rz-ink">
                            {deal.status === 'sold_out'
                                ? t('investor.deals.fully_funded')
                                : formatRwf(deal.left_to_fill)}
                        </p>
                    </div>
                    <div className="min-w-0">
                        <p className="text-[10px] font-bold tracking-[.05em] whitespace-nowrap text-rz-slate uppercase">
                            {t('investor.deals.notes')}
                        </p>
                        <p className="mt-[3px] text-xs font-semibold whitespace-nowrap text-rz-ink">
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
