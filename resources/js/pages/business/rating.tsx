import { Link } from '@inertiajs/react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { CapacityCard } from '@/components/business/rating/capacity-card';
import { ScoreCard } from '@/components/business/rating/score-card';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import type { BusinessRatingProps } from '@/types/business';

/**
 * Financial health (MVP-BUSINESS-SCR-03, design L811–939), opened from the rating on Home's hero.
 * It shows the published rating and the engine's sizing of capacity; nothing here is computed.
 */
export default function BusinessRating({
    home,
    rating,
    refusal,
    drift,
    factors,
    sizing,
    financials,
    links,
}: BusinessRatingProps) {
    const { t } = useTranslation();

    const sheet = (
        <DetailSheet
            label={t('business.rating.page_title')}
            close={links.close}
            closeLabel={t('business.note.close')}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div>
                        <h1 className="text-xl font-semibold text-rz-ink">
                            {t('business.rating.page_title')}
                        </h1>
                        <p className="text-[12.5px] text-rz-secondary">
                            {t('business.rating.page_subtitle')}
                        </p>
                    </div>
                </div>
                <ScoreCard rating={rating} refusal={refusal} drift={drift} />

                {factors !== null && (
                    <>
                        <h2 className="mt-[18px] text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                            {t('business.rating.factors')}
                        </h2>
                        <ul className="mt-[11px] flex flex-col gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-4">
                            {factors.map((factor) => (
                                <li key={factor.key}>
                                    <div className="mb-[5px] flex items-center justify-between">
                                        <span className="text-[12.5px] font-semibold text-rz-slate">
                                            {t(
                                                `business.rating.factor.${factor.key}`,
                                            )}
                                        </span>
                                        <span className="text-xs font-bold text-rz-accent-app-text">
                                            {factor.score}
                                        </span>
                                    </div>
                                    <div
                                        role="progressbar"
                                        aria-label={t(
                                            `business.rating.factor.${factor.key}`,
                                        )}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={factor.score}
                                        className="h-1.5 overflow-hidden rounded-[4px] bg-[#eef2f8] dark:bg-rz-page"
                                    >
                                        <div
                                            className="h-full bg-rz-accent-fill"
                                            style={{
                                                width: `${factor.score}%`,
                                            }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}

                {sizing !== null && (
                    <CapacityCard sizing={sizing} raise={links.raise} />
                )}

                <div className="mt-5 flex items-center gap-[7px]">
                    <h2 className="text-[13px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                        {t('business.rating.financials')}
                    </h2>
                    <span className="inline-flex shrink-0 items-center gap-1 rounded-[10px] border border-[#cdeddb] bg-[#e7f7ee] px-1.5 py-0.5 text-[10.5px] font-bold text-rz-accent-app-text dark:border-transparent dark:bg-rz-accent-soft">
                        {t('business.rating.sources')}
                    </span>
                </div>
                <dl className="mt-[11px] grid grid-cols-2 gap-[9px]">
                    {(
                        [
                            [
                                'revenue',
                                formatRwfShort(financials.avg_monthly_revenue),
                            ],
                            ['ebitda', formatRwfShort(financials.ebitda_month)],
                            [
                                'margin',
                                t('business.reports.percent', {
                                    value: financials.net_margin_percent,
                                }),
                            ],
                            [
                                'outstanding',
                                formatRwfShort(financials.outstanding),
                            ],
                        ] as const
                    ).map(([key, value]) => (
                        <div
                            key={key}
                            className="rounded-xl border border-rz-border bg-rz-surface p-3"
                        >
                            <dt className="text-[10px] font-semibold text-rz-secondary uppercase">
                                {t(`business.rating.financial.${key}`)}
                            </dt>
                            <dd className="mt-[3px] text-[15px] font-semibold text-rz-ink">
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={t('business.rating.page_title')}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'left', content: sheet }}
            />
        </BusinessShell>
    );
}
