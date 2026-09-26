import { useState } from 'react';
import { AuditDrawer } from '@/components/investor/audit-drawer';
import { AboutCard, EbitdaValue } from '@/components/investor/deals/deal-facts';
import {
    FundingProgress,
    PhotosRow,
    Section,
    SourceBadge,
    UseOfFundsChips,
} from '@/components/investor/deals/deal-sections';
import { DealStatusNotice } from '@/components/investor/deals/deal-status';
import { UpdateList, UpdateSheet } from '@/components/investor/monthly-updates';
import { POSITIVE_TEXT, RATING_STYLE } from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { C3DealDetail, MonthlyUpdateSummary } from '@/types/investor';

const MICRO =
    'text-[10.5px] font-bold tracking-[.04em] whitespace-nowrap text-rz-slate uppercase';

/**
 * The wide-screen detail panel beside the deck (design L375–466): rating, interest and term; funding
 * progress; then the details. The design leaves the Field Flash drawer out of this panel — a parity
 * gap against the phone deal page — so it is included here. The rating-factor bars are internal
 * score components (engineering contract §4), so only the engine's rationale is shown; the save
 * and follow controls are outside the MVP.
 */
export function DealPanel({
    deal,
    serverTime,
}: {
    deal: C3DealDetail;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const [update, setUpdate] = useState<MonthlyUpdateSummary | null>(null);
    const rating = RATING_STYLE[deal.rating.band];

    return (
        <div className="relative flex min-w-[300px] flex-[0_1_400px]">
            <div
                className="rz-scroll min-w-0 flex-1 overflow-y-auto rounded-[20px] border border-rz-border bg-rz-surface shadow-[0_10px_30px_-18px_rgba(20,45,95,.25)]"
                inert={update !== null || undefined}
            >
                <div className="p-[18px]">
                    <DealStatusNotice deal={deal} />
                    <div className="grid grid-cols-3 gap-2.5">
                        <div
                            className={cn(
                                'min-w-0 rounded-xl px-2.5 py-3',
                                rating.tint,
                            )}
                        >
                            <p className={MICRO}>{t('investor.deal.rating')}</p>
                            <p
                                className={cn(
                                    'mt-1 text-xs font-bold whitespace-nowrap',
                                    rating.text,
                                )}
                            >
                                {t(`investor.rating.${deal.rating.band}`)}{' '}
                                {deal.rating.score}
                            </p>
                        </div>
                        <div className="min-w-0 rounded-xl bg-rz-surface-sunken px-2.5 py-3">
                            <p className={MICRO}>
                                {t('investor.deal.interest')}
                            </p>
                            <p className="mt-1 text-xs font-bold whitespace-nowrap text-rz-ink">
                                {deal.rate_pct}%
                            </p>
                        </div>
                        <div className="min-w-0 rounded-xl bg-rz-surface-sunken px-2.5 py-3">
                            <p className={MICRO}>{t('investor.deal.term')}</p>
                            <p className="mt-1 text-xs font-bold whitespace-nowrap text-rz-ink">
                                {t('investor.deals.months_short', {
                                    count: deal.term_months,
                                })}
                            </p>
                        </div>
                    </div>

                    <div className="mt-3.5">
                        <FundingProgress
                            deal={deal}
                            serverTime={serverTime}
                            variant="desk"
                        />
                    </div>

                    <p className="mt-4 text-[11px] font-bold tracking-[.08em] text-rz-slate uppercase">
                        {t('investor.deal.details')}
                    </p>

                    <PhotosRow deal={deal} variant="desk" />
                    <UseOfFundsChips deal={deal} variant="desk" />

                    <Section
                        title={t('investor.deal.financials')}
                        variant="desk"
                        badge={
                            <SourceBadge kind="verified" variant="desk">
                                {t('investor.deal.verified_audited')}
                            </SourceBadge>
                        }
                    >
                        <div className="mt-2.5 grid grid-cols-2 gap-2.5">
                            <div className="rounded-xl bg-rz-surface-sunken p-[11px]">
                                <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                    {t('investor.deal.avg_monthly_revenue')}
                                </p>
                                <p className="mt-[3px] text-sm font-bold text-rz-ink">
                                    {formatRwfShort(
                                        deal.financials.avg_monthly_revenue,
                                    )}
                                </p>
                            </div>
                            <div className="rounded-xl bg-rz-surface-sunken p-[11px]">
                                <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                    {t('investor.deal.ebitda')}
                                </p>
                                <EbitdaValue
                                    ebitda={deal.financials.ebitda}
                                    className="mt-[3px] text-sm font-bold text-rz-ink"
                                />
                            </div>
                        </div>
                    </Section>

                    <Section
                        title={t('investor.deal.assessment')}
                        variant="desk"
                        badge={
                            <SourceBadge kind="business_blue" variant="desk">
                                {t('investor.deal.assessed_by_rozine')}
                            </SourceBadge>
                        }
                    >
                        <p className="mt-2.5 rounded-xl border border-rz-border bg-rz-surface p-[13px] text-[11.5px] leading-[1.55] text-rz-secondary">
                            {deal.rationale}
                        </p>
                    </Section>

                    {deal.track_record !== null && (
                        <Section
                            title={t('investor.deal.track_record')}
                            variant="desk"
                            badge={
                                <SourceBadge kind="verified" variant="desk">
                                    {t('investor.deal.verified')}
                                </SourceBadge>
                            }
                        >
                            <div className="mt-2.5 grid grid-cols-3 gap-[9px] rounded-xl border border-rz-border bg-rz-surface p-[13px]">
                                <div>
                                    <p className={MICRO}>
                                        {t('investor.deal.raises')}
                                    </p>
                                    <p className="mt-[3px] text-sm font-bold text-rz-ink">
                                        {deal.track_record.raises}
                                    </p>
                                </div>
                                <div>
                                    <p className={MICRO}>
                                        {t('investor.deal.on_time')}
                                    </p>
                                    <p
                                        className={cn(
                                            'mt-[3px] text-sm font-bold',
                                            POSITIVE_TEXT,
                                        )}
                                    >
                                        {deal.track_record.on_time_pct === null
                                            ? '—'
                                            : `${deal.track_record.on_time_pct}%`}
                                    </p>
                                </div>
                                <div>
                                    <p className={MICRO}>
                                        {t('investor.deal.repaid')}
                                    </p>
                                    <p className="mt-[3px] text-sm font-bold text-rz-ink">
                                        {formatRwfShort(
                                            deal.track_record.repaid,
                                        )}
                                    </p>
                                </div>
                            </div>
                        </Section>
                    )}

                    <Section
                        title={t('investor.deal.about')}
                        variant="desk"
                        badge={
                            <SourceBadge kind="business" variant="desk">
                                {t('investor.deal.reported_by_business')}
                            </SourceBadge>
                        }
                    >
                        <AboutCard about={deal.about} />
                    </Section>

                    {deal.audit !== null && (
                        <div className="mt-4">
                            <AuditDrawer audit={deal.audit} />
                        </div>
                    )}

                    <Section
                        title={t('investor.deal.monthly_updates')}
                        variant="desk"
                        badge={
                            <SourceBadge kind="verified" variant="desk">
                                {t('investor.deal.audited')}
                            </SourceBadge>
                        }
                    >
                        <UpdateList
                            updates={deal.updates}
                            variant="desk"
                            onOpen={setUpdate}
                        />
                    </Section>
                </div>
            </div>
            {update !== null && (
                <UpdateSheet update={update} onClose={() => setUpdate(null)} />
            )}
        </div>
    );
}
