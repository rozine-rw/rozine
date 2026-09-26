import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AuditDrawer } from '@/components/investor/audit-drawer';
import { AboutCard, EbitdaValue } from '@/components/investor/deals/deal-facts';
import {
    FundingProgress,
    PhotosRow,
    RatingWord,
    Section,
    SourceBadge,
    UseOfFundsChips,
} from '@/components/investor/deals/deal-sections';
import {
    DealStatusNotice,
    OverdueReport,
} from '@/components/investor/deals/deal-status';
import { CapNote, canReserve } from '@/components/investor/deals/invest-bar';
import { useQuotedUnits } from '@/components/investor/deals/use-quote';
import { UpdateList, UpdateSheet } from '@/components/investor/monthly-updates';
import {
    ACCENT_FILL,
    accentBanner,
    POSITIVE_TEXT,
    RATING_STYLE,
} from '@/components/investor/tokens';
import { useTranslation } from '@/hooks/use-translation';
import { withQuery } from '@/lib/investor/links';
import { formatAmount, formatRwf, formatRwfShort } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    C3InvestorDealProps,
    MonthlyUpdateSummary,
} from '@/types/investor';

const ROW = 'flex items-center justify-between py-[9px]';
const ROW_LINE = 'border-b border-[#eef2f9] dark:border-rz-divider';

/**
 * The phone deal page (MVP-INVESTOR-SCR-02, design L729–997): banner, rating/return/term strip,
 * funding, then the details with their provenance — photos, use of funds, verified financials,
 * Rozine's assessment, track record, the business, the sealed Field Flash report and the audited
 * monthly updates — under the sticky investment card (L4389–4429).
 */
export function DealDetailPage({
    deal,
    gate,
    quote,
    server_time: serverTime,
    allowed_actions: allowed,
    links,
}: Omit<C3InvestorDealProps, 'home'>) {
    const { t } = useTranslation();
    const [update, setUpdate] = useState<MonthlyUpdateSummary | null>(null);
    const quoted = useQuotedUnits(Number(quote?.units ?? '1'), {
        deal: deal.campaign_id,
    });
    const rating = RATING_STYLE[deal.rating.band];
    const current = quote;
    const max = Number(current?.capacity.max_units ?? '1');
    const checkout =
        links.checkout !== null &&
        canReserve({ deal, gate, allowed, quote: current })
            ? withQuery(links.checkout, {
                  deal: deal.campaign_id,
                  units: quoted.units,
              })
            : null;
    const canBuy = checkout !== null;
    const tile =
        'rounded-xl border border-rz-border px-[5px] py-[7px] text-center';

    return (
        <div className="pb-[250px]">
            <div
                className="relative h-[154px] flex-none"
                style={{ background: accentBanner(deal.accent) }}
            >
                <span className="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,15,28,.35)_0%,rgba(10,15,28,0)_42%,rgba(10,15,28,.92)_100%)]" />
                <Link
                    href={links.back}
                    aria-label={t('investor.common.back')}
                    className="absolute top-[calc(env(safe-area-inset-top)+12px)] left-4 z-[5] flex size-[38px] items-center justify-center rounded-[10px] bg-[rgba(10,15,28,.6)] text-[17px] leading-none text-white backdrop-blur-[6px]"
                >
                    <span aria-hidden>←</span>
                </Link>
                <div className="absolute inset-x-4 bottom-3 flex items-center gap-[11px]">
                    <span
                        className="flex size-[42px] shrink-0 items-center justify-center rounded-xl bg-white/95 text-lg font-semibold"
                        style={{ color: ACCENT_FILL[deal.accent] }}
                    >
                        {deal.name.charAt(0)}
                    </span>
                    <div className="min-w-0">
                        <h1 className="truncate text-[17px] font-semibold text-white [text-shadow:0_1px_6px_rgba(0,0,0,.45)]">
                            {deal.name}
                        </h1>
                        <div className="mt-0.5 flex items-center gap-1.5">
                            <span className="inline-flex shrink-0 items-center gap-1 rounded-[10px] bg-white/[.22] px-[7px] py-0.5 text-[10.5px] font-semibold text-white">
                                {t('investor.deal.verified_pill')}
                            </span>
                            <span className="truncate text-[11.5px] text-white/90 [text-shadow:0_1px_4px_rgba(0,0,0,.45)]">
                                {deal.industry} · {deal.district}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div className="flex gap-[7px] px-3.5 pt-[11px]">
                <div className={cn(tile, 'flex-[1.3]', rating.tint)}>
                    <p className="text-[10.5px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                        {t('investor.deal.rating')}
                    </p>
                    <p className="mt-0.5 flex items-baseline justify-center">
                        <RatingWord deal={deal} />
                    </p>
                </div>
                <div className={cn(tile, 'flex-1 bg-rz-surface')}>
                    <p className="text-[10.5px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                        {t('investor.deal.return')}
                    </p>
                    <p
                        className={cn(
                            'mt-0.5 text-[15px] font-bold',
                            POSITIVE_TEXT,
                        )}
                    >
                        {deal.rate_pct}%
                    </p>
                </div>
                <div className={cn(tile, 'flex-1 bg-rz-surface')}>
                    <p className="text-[10.5px] font-semibold tracking-[.04em] text-rz-secondary uppercase">
                        {t('investor.deal.term')}
                    </p>
                    <p className="mt-0.5 text-[15px] font-bold text-rz-ink">
                        {t('investor.deals.months_short', {
                            count: deal.term_months,
                        })}
                    </p>
                </div>
            </div>

            <div className="px-3.5 pt-[11px]">
                <DealStatusNotice deal={deal} className="mb-[11px]" />
                <FundingProgress
                    deal={deal}
                    serverTime={serverTime}
                    variant="phone"
                />
            </div>

            <div className="flex items-center gap-2.5 px-3.5 pt-[22px]">
                <span className="text-[10.5px] font-bold tracking-[.12em] text-rz-accent-app-text uppercase">
                    {t('investor.deal.details')}
                </span>
                <span className="h-px flex-1 bg-rz-border" />
            </div>

            <PhotosRow deal={deal} variant="phone" />
            <UseOfFundsChips deal={deal} variant="phone" />

            <Section
                title={t('investor.deal.financials')}
                variant="phone"
                badge={
                    <SourceBadge kind="audited">
                        {t('investor.deal.verified_audited')}
                    </SourceBadge>
                }
            >
                <div className="mt-2.5 grid grid-cols-2 gap-[9px]">
                    <div className="rounded-xl border border-rz-border bg-rz-surface px-3 py-[11px]">
                        <p className="text-[10.5px] font-semibold tracking-[.02em] whitespace-nowrap text-rz-secondary uppercase">
                            {t('investor.deal.avg_monthly_revenue')}
                        </p>
                        <p className="mt-[3px] text-base font-semibold whitespace-nowrap text-rz-ink">
                            {formatRwfShort(
                                deal.financials.avg_monthly_revenue,
                            )}
                        </p>
                    </div>
                    <div className="rounded-xl border border-rz-border bg-rz-surface px-3 py-[11px]">
                        <p className="text-[10.5px] font-semibold tracking-[.02em] whitespace-nowrap text-rz-secondary uppercase">
                            {t('investor.deal.ebitda')}
                        </p>
                        <EbitdaValue
                            ebitda={deal.financials.ebitda}
                            className="mt-[3px] text-base font-semibold text-rz-ink"
                        />
                    </div>
                </div>
            </Section>

            <Section
                title={t('investor.deal.assessment')}
                variant="phone"
                badge={
                    <SourceBadge kind="rozine">
                        {t('investor.deal.rozine_analysis')}
                    </SourceBadge>
                }
            >
                <dl className="mt-2.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-0.5">
                    <div className={cn(ROW, ROW_LINE)}>
                        <dt className="text-[13px] text-rz-secondary">
                            {t('investor.deal.rozine_rating')}
                        </dt>
                        <dd className="inline-flex items-baseline gap-[5px]">
                            <span
                                className={cn(
                                    'text-[13px] font-bold',
                                    rating.text,
                                )}
                            >
                                {t(`investor.rating.${deal.rating.band}`)}
                            </span>
                            <span
                                className={cn(
                                    'text-xs font-semibold',
                                    rating.text,
                                )}
                            >
                                {t('investor.deal.out_of_five', {
                                    score: deal.rating.score,
                                })}
                            </span>
                        </dd>
                    </div>
                    <div className={cn(ROW, ROW_LINE)}>
                        <dt className="text-[13px] text-rz-secondary">
                            {t('investor.deal.total_return')}
                        </dt>
                        <dd
                            className={cn(
                                'text-[13px] font-semibold',
                                POSITIVE_TEXT,
                            )}
                        >
                            {deal.rate_pct}%
                        </dd>
                    </div>
                    <div className={cn(ROW, ROW_LINE)}>
                        <dt className="text-[13px] text-rz-secondary">
                            {t('investor.deal.repayment')}
                        </dt>
                        <dd className="text-[13px] font-semibold text-rz-ink">
                            {t('investor.deal.monthly')}
                        </dd>
                    </div>
                    <div className={ROW}>
                        <dt className="text-[13px] text-rz-secondary">
                            {t('investor.deal.term')}
                        </dt>
                        <dd className="text-[13px] font-semibold text-rz-ink">
                            {t('investor.deal.months_long', {
                                count: deal.term_months,
                            })}
                        </dd>
                    </div>
                </dl>
                <p className="mt-[9px] rounded-xl border border-[rgba(30,58,255,.10)] bg-[rgba(30,58,255,.06)] px-[13px] py-[11px] text-xs leading-normal text-rz-slate dark:border-rz-border dark:bg-rz-accent-soft">
                    {deal.rationale}
                </p>
                {deal.track_record !== null && (
                    <div className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
                        <div className="flex items-center gap-[7px]">
                            <span className="text-[12.5px] font-bold text-rz-ink">
                                {t('investor.deal.track_record')}
                            </span>
                            <span
                                className={cn(
                                    'inline-flex shrink-0 items-center rounded-[10px] border border-rz-border bg-rz-surface px-1.5 py-0.5 text-[10.5px] font-bold uppercase',
                                    POSITIVE_TEXT,
                                )}
                            >
                                {t('investor.deal.history')}
                            </span>
                        </div>
                        <div className="mt-3 flex gap-[9px]">
                            {(
                                [
                                    [
                                        'investor.deal.raises_done',
                                        String(deal.track_record.raises),
                                        'text-rz-ink',
                                    ],
                                    [
                                        'investor.deal.on_time',
                                        deal.track_record.on_time_pct === null
                                            ? '—'
                                            : `${deal.track_record.on_time_pct}%`,
                                        POSITIVE_TEXT,
                                    ],
                                    [
                                        'investor.deal.repaid',
                                        formatRwfShort(
                                            deal.track_record.repaid,
                                        ),
                                        'text-rz-ink',
                                    ],
                                ] as const
                            ).map(([label, value, tone]) => (
                                <div
                                    key={label}
                                    className="flex-1 rounded-xl border border-[#eef2f9] bg-[#f6f9fd] px-1.5 py-[11px] text-center dark:border-rz-divider dark:bg-rz-surface-sunken"
                                >
                                    <p
                                        className={cn(
                                            'text-lg font-bold',
                                            tone,
                                        )}
                                    >
                                        {value}
                                    </p>
                                    <p className="mt-0.5 text-[10.5px] font-semibold tracking-[.02em] text-rz-secondary uppercase">
                                        {t(label)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </Section>

            <Section
                title={t('investor.deal.about')}
                variant="phone"
                badge={
                    <SourceBadge kind="business">
                        {t('investor.deal.reported_by_business')}
                    </SourceBadge>
                }
            >
                <p className="mt-[9px] text-[13px] leading-[1.55] text-rz-secondary">
                    {deal.story}
                </p>
                <AboutCard about={deal.about} />
            </Section>

            {deal.audit !== null && (
                <div className="px-3.5 pt-[22px]">
                    <AuditDrawer audit={deal.audit} />
                </div>
            )}

            <Section
                title={t('investor.deal.monthly_updates')}
                variant="phone"
                badge={
                    <SourceBadge kind="audited">
                        {t('investor.deal.audited')}
                    </SourceBadge>
                }
            >
                <OverdueReport deal={deal} />
                <UpdateList
                    updates={deal.updates}
                    variant="phone"
                    onOpen={setUpdate}
                />
            </Section>

            {update !== null && (
                <UpdateSheet update={update} onClose={() => setUpdate(null)} />
            )}

            <div className="fixed inset-x-0 bottom-0 z-[40] bg-[linear-gradient(180deg,rgba(244,247,252,0)_0%,rgba(244,247,252,.92)_20%,var(--rz-page)_44%)] px-4 pt-[13px] pb-[calc(env(safe-area-inset-bottom)+17px)] dark:bg-[linear-gradient(180deg,rgba(10,18,32,0)_0%,rgba(10,18,32,.92)_20%,var(--rz-page)_44%)]">
                <section
                    aria-label={t('investor.deal.your_investment')}
                    className="rounded-2xl border border-rz-border bg-rz-surface p-[13px] shadow-[0_18px_42px_-14px_rgba(20,45,95,.34)]"
                >
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-bold tracking-[.03em] text-rz-slate uppercase">
                            {t('investor.deal.your_investment')}
                        </p>
                        <span className="text-[11px] font-semibold text-rz-secondary">
                            {t('investor.deal.repaid_monthly', {
                                count: deal.term_months,
                            })}
                        </span>
                    </div>
                    <div
                        aria-busy={quoted.quoting || undefined}
                        className={cn(
                            'mt-2.5 rounded-xl border border-[#eef2f9] bg-[#f8fafc] px-3 py-[11px] transition-opacity dark:border-rz-divider dark:bg-rz-surface-sunken',
                            quoted.quoting && 'opacity-60',
                        )}
                    >
                        <div className="flex items-center justify-between text-xs">
                            <span className="text-rz-slate">
                                {current === null
                                    ? '—'
                                    : t(
                                          current.units === '1'
                                              ? 'investor.deal.unit_times_one'
                                              : 'investor.deal.unit_times_other',
                                          {
                                              price: formatRwf(
                                                  current.unit_price,
                                              ),
                                              count: Number(current.units),
                                          },
                                      )}
                            </span>
                            <span className="font-semibold text-rz-ink tabular-nums">
                                {current === null
                                    ? '—'
                                    : formatRwf(current.amount)}
                            </span>
                        </div>
                        <div className="mt-[7px] flex items-center justify-between">
                            <span className="text-xs text-rz-slate">
                                {t('investor.deal.expected_return')}{' '}
                                <span className="text-rz-secondary">
                                    · {deal.rate_pct}%
                                </span>
                            </span>
                            <span
                                className={cn(
                                    'text-[13.5px] font-bold tabular-nums',
                                    POSITIVE_TEXT,
                                )}
                            >
                                {current === null
                                    ? '—'
                                    : `+${formatRwf(current.expected_return)}`}
                            </span>
                        </div>
                        <div className="mt-[7px] flex items-center justify-between">
                            <span className="text-xs text-rz-slate">
                                {t('investor.checkout.payout_fee')}
                            </span>
                            <span className="text-xs font-semibold text-rz-ink tabular-nums">
                                {current === null
                                    ? '—'
                                    : formatRwf(current.payout_fee)}
                            </span>
                        </div>
                        <div className="mt-2 flex items-center justify-between border-t border-rz-border pt-2">
                            <span className="text-[12.5px] font-semibold text-rz-ink">
                                {t('investor.deal.total_at_maturity')}
                            </span>
                            <span className="text-[15px] font-bold text-rz-accent-app-text tabular-nums">
                                {current === null
                                    ? '—'
                                    : formatRwf(current.maturity_value)}
                            </span>
                        </div>
                    </div>
                    <div className="mt-2.5 flex items-center gap-2">
                        <div className="flex shrink-0 items-center rounded-xl border border-rz-border bg-rz-surface p-1">
                            <button
                                type="button"
                                aria-label={t('investor.deal.fewer_notes')}
                                disabled={quoted.units <= 1 || !canBuy}
                                onClick={() =>
                                    quoted.setUnits(quoted.units - 1)
                                }
                                className="h-[46px] w-7 rounded-[10px] bg-[#eef2f9] text-xl text-rz-ink disabled:opacity-40 dark:bg-rz-surface-muted"
                            >
                                <span aria-hidden>−</span>
                            </button>
                            <div className="flex min-w-16 flex-col items-center justify-center px-2">
                                <span className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate">
                                    {t('investor.money.rwf')}
                                </span>
                                <span className="text-[15px] leading-[1.1] font-semibold whitespace-nowrap text-rz-ink">
                                    {current === null
                                        ? '—'
                                        : formatAmount(current.amount)}
                                </span>
                            </div>
                            <button
                                type="button"
                                aria-label={t('investor.deal.more_notes')}
                                disabled={quoted.units >= max || !canBuy}
                                onClick={() =>
                                    quoted.setUnits(quoted.units + 1)
                                }
                                className="h-[46px] w-7 rounded-[10px] bg-[#eef2f9] text-xl text-rz-ink disabled:opacity-40 dark:bg-rz-surface-muted"
                            >
                                <span aria-hidden>+</span>
                            </button>
                        </div>
                        <div className="flex shrink-0 flex-col items-center justify-center self-stretch rounded-xl border border-rz-border bg-rz-surface px-2.5 py-[7px]">
                            <span className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate">
                                {t('investor.deals.notes_suffix')}
                            </span>
                            <span className="text-[15px] leading-[1.1] font-semibold text-rz-ink">
                                {quoted.units}
                            </span>
                        </div>
                        {checkout !== null ? (
                            <Link
                                href={checkout}
                                className="flex h-14 min-w-0 flex-1 items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-semibold whitespace-nowrap text-white"
                            >
                                {t('investor.deals.invest')}
                            </Link>
                        ) : gate.status === 'verification_required' ? (
                            <Link
                                href={gate.link}
                                className="flex h-14 min-w-0 flex-1 items-center justify-center rounded-2xl bg-rz-accent-fill text-[14px] font-semibold whitespace-nowrap text-white"
                            >
                                {t('investor.deals.verify_to_invest')}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                disabled
                                className="flex h-14 min-w-0 flex-1 cursor-not-allowed items-center justify-center rounded-2xl bg-[#eef2f9] text-[15px] font-semibold whitespace-nowrap text-rz-secondary dark:bg-rz-surface-muted"
                            >
                                {deal.lifecycle === 'live'
                                    ? t('investor.deals.invest')
                                    : t(
                                          `investor.deal.lifecycle.${deal.lifecycle}`,
                                      )}
                            </button>
                        )}
                    </div>
                    <CapNote
                        quote={current}
                        units={quoted.units}
                        className="mt-2"
                    />
                </section>
            </div>
        </div>
    );
}
