import { Link } from '@inertiajs/react';
import { Eyebrow, EmptyState } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteLink } from '@/types/routing';

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
                <span className="shrink-0 text-[10px] font-bold tracking-[.06em] text-[#c2661f] uppercase dark:text-[#e3b56a]">
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
 * This month's verification earnings, managed deals and next payout (design L416–424). None has a
 * read yet, so each reads as a dash.
 */
export function EarningsSummary() {
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
            <p className="mt-[5px] text-[30px] font-bold tracking-[-.5px]">—</p>
            <dl className="mt-3 flex gap-2">
                {(['managed', 'next_payout'] as const).map((key) => (
                    <div
                        key={key}
                        className="min-w-0 flex-1 rounded-[10px] bg-white/15 px-[11px] py-[9px]"
                    >
                        <dt className="text-[10px] font-bold whitespace-nowrap text-white/75 uppercase">
                            {t(`auditor.portfolio.earnings.${key}`)}
                        </dt>
                        <dd className="mt-[3px] text-[14px] font-bold">—</dd>
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
 * Managed deals (design L507–552). No account-manager read exists yet, so the design's own empty
 * state stands in for the list.
 */
export function ManagedDeals() {
    const { t } = useTranslation();

    return (
        <section aria-labelledby="managed-deals">
            <Eyebrow as="h2" className="mt-5 lg:mt-0">
                <span id="managed-deals">
                    {t('auditor.portfolio.managed.title')}
                </span>
            </Eyebrow>
            <EmptyState className="mt-2.5 py-[26px]">
                {t('auditor.portfolio.managed.empty')}
            </EmptyState>
        </section>
    );
}
