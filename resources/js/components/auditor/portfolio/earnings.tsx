import { Link } from '@inertiajs/react';
import { compactRwf } from '@/components/auditor/money';
import {
    Eyebrow,
    EmptyState,
    StatTile,
    StatusPill,
    TILE,
    initials,
} from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatAmount, formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditorEarnings, EarningLine } from '@/types/auditor';
import type { RouteLink } from '@/types/routing';

/** A tile whose label may wrap keeps its figure on the row's baseline. */
const ALIGNED = cn(TILE, 'flex flex-col justify-between');

/**
 * The CPA card (design L334–337): proof of accreditation, which the Profile's Accreditation section
 * holds. The design's "List a deal" beside it has no command yet, so it is left out.
 */
export function CpaCardLink({ profile }: { profile: RouteLink }) {
    const { t } = useTranslation();

    return (
        <div className="mt-3 flex gap-2">
            <Link
                href={profile}
                className="flex-1 rounded-2xl bg-[#f4f7fc] px-3 py-[13px] text-left dark:bg-rz-surface-sunken"
            >
                <span className="block text-[12.5px] font-bold text-rz-ink">
                    {t('auditor.portfolio.cpa_card')}
                </span>
                <span className="mt-px block text-[10.5px] text-rz-secondary">
                    {t('auditor.portfolio.cpa_card_sub')}
                </span>
            </Link>
        </div>
    );
}

/**
 * Deals you sourced (design L340–386). No origination read exists yet, so the panel shows the
 * design's own empty note rather than a commission or pipeline figure.
 */
export function SourcedDeals() {
    const { t } = useTranslation();

    return (
        <section
            aria-labelledby="deals-you-sourced"
            className="mt-3.5 rounded-[20px] border border-rz-border bg-rz-surface p-4"
        >
            <div className="flex items-baseline justify-between gap-2.5">
                <h2
                    id="deals-you-sourced"
                    className="text-[14.5px] font-bold text-rz-ink"
                >
                    {t('auditor.portfolio.sourced.title')}
                </h2>
                <span className="shrink-0 text-[10px] font-bold tracking-[.06em] text-[#a4521a] uppercase dark:text-[#e3b56a]">
                    {t('auditor.portfolio.sourced.tag')}
                </span>
            </div>
            <p className="mt-[9px] text-[12px] leading-[1.55] text-rz-secondary">
                {t('auditor.portfolio.sourced.empty')}
            </p>
        </section>
    );
}

/**
 * This month's earnings, managed deals and next payout (design L416–424). Earnings are the
 * partner's platform fee shares credited this Kigali month and managed deals the funded raises that
 * credited them. No partner payout is scheduled by any read yet, so the next payout reads as a dash.
 */
export function EarningsSummary({ earnings }: { earnings: AuditorEarnings }) {
    const { t } = useTranslation();

    return (
        <section
            aria-labelledby="verification-earned"
            className="mt-3.5 rounded-[20px] bg-[#7d420f] p-[18px] text-white"
        >
            <h2
                id="verification-earned"
                className="text-[11px] font-bold tracking-[.05em] text-white/80 uppercase"
            >
                {t('auditor.portfolio.earnings.title')}
            </h2>
            <p className="mt-[5px] flex items-baseline gap-1.5">
                <span className="text-[14px] font-bold text-white/80">
                    {earnings.this_month.currency}
                </span>
                <span className="text-[30px] font-bold tracking-[-.5px]">
                    {formatAmount(earnings.this_month)}
                </span>
            </p>
            <dl className="mt-3 flex gap-2">
                {(['managed', 'next_payout'] as const).map((key) => (
                    <div
                        key={key}
                        className="min-w-0 flex-1 rounded-[10px] bg-white/15 px-[11px] py-[9px]"
                    >
                        <dt className="text-[10px] font-bold whitespace-nowrap text-white uppercase">
                            {t(`auditor.portfolio.earnings.${key}`)}
                        </dt>
                        <dd className="mt-[3px] text-[14px] font-bold">
                            {key === 'managed' ? earnings.deals : '—'}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

/**
 * The yield-share chart (design L427–447). No yield-share has been paid through a read yet, so the
 * card holds an empty state where the bars would be.
 */
export function YieldShare() {
    const { t } = useTranslation();

    return (
        <section className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface p-[15px]">
            <Eyebrow as="h2" className="tracking-[.05em]">
                {t('auditor.portfolio.yield.title')}
            </Eyebrow>
            <p className="mt-4 flex h-[104px] items-center justify-center rounded-[10px] border border-dashed border-[#dbe3f0] px-4 text-center text-[12px] text-rz-secondary dark:border-rz-border">
                {t('auditor.portfolio.yield.empty')}
            </p>
        </section>
    );
}

/**
 * Managed deals (design L507–552): every funded raise whose sealed report the partner wrote, newest
 * first, with the raise, its platform fee and the partner's 40% share (BRS v2.0 FEE-R02).
 */
export function ManagedDeals({
    deals,
    total,
}: {
    deals: EarningLine[];
    /** Every raise that credited the partner; the list itself is capped. */
    total: number;
}) {
    const { t, locale } = useTranslation();

    return (
        <section aria-labelledby="managed-deals">
            <div className="mt-5 flex items-baseline justify-between gap-2.5 lg:mt-0">
                <Eyebrow as="h2">
                    <span id="managed-deals">
                        {t('auditor.portfolio.managed.title')}
                    </span>
                </Eyebrow>
                {deals.length > 0 && (
                    <span className="text-[11px] text-rz-secondary">
                        {t('auditor.portfolio.managed.count', {
                            count: total,
                        })}
                    </span>
                )}
            </div>
            {deals.length === 0 ? (
                <EmptyState className="mt-2.5 py-[26px]">
                    {t('auditor.portfolio.managed.empty')}
                </EmptyState>
            ) : (
                <ul className="mt-2.5 flex flex-col gap-3">
                    {deals.map((deal) => (
                        <li
                            key={deal.id}
                            className="rounded-2xl border border-rz-border bg-rz-surface p-[15px]"
                        >
                            <div className="flex items-center gap-[11px]">
                                <span
                                    aria-hidden
                                    className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] bg-rz-accent-soft text-[14px] font-bold text-rz-ink"
                                >
                                    {initials(deal.business)}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[14px] font-bold text-rz-ink">
                                        {deal.business}
                                    </p>
                                    <p className="mt-px truncate text-[11.5px] text-rz-secondary">
                                        {t('auditor.portfolio.managed.sub', {
                                            district: deal.district,
                                            date: formatDate(
                                                deal.credited_at,
                                                locale,
                                            ),
                                        })}
                                    </p>
                                </div>
                                <StatusPill
                                    tone="green"
                                    className="px-2.5 py-[5px] text-[10.5px]"
                                >
                                    {t('auditor.portfolio.managed.funded')}
                                </StatusPill>
                            </div>
                            <div className="mt-3 flex gap-2">
                                <StatTile
                                    label={t('auditor.portfolio.managed.raise')}
                                    className={ALIGNED}
                                    value={compactRwf(deal.raise)}
                                />
                                <StatTile
                                    label={t('auditor.portfolio.managed.fee')}
                                    className={ALIGNED}
                                    value={compactRwf(deal.platform_fee)}
                                />
                                <StatTile
                                    label={t('auditor.portfolio.managed.share')}
                                    className={ALIGNED}
                                    value={compactRwf(deal.amount)}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
