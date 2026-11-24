import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { profile } from '@/routes/auditor';
import type { AuditorJobsProps, EligibleJob } from '@/types/auditor';

/**
 * The districts dispatch matches this partner on (Erastus, 24 Nov 2026), in place of the design's
 * 30 km radius map: an audit is offered when the Business's district is one the partner accepts.
 * Each accepted district shows how many offers on screen are in it, and the badge counts them all —
 * this page's, never a total, when the offers are a live page. A partner with no district, or
 * paused, is told plainly that no audit can be offered, with the way to change it.
 */
export function DistrictCoverage({
    coverage,
    jobs,
    paged,
}: {
    coverage: NonNullable<AuditorJobsProps['coverage']>;
    jobs: EligibleJob[];
    /** Whether the offers are a live page: the count is then this page's, and says so. */
    paged: boolean;
}) {
    const { t } = useTranslation();
    const open = (district: string) =>
        jobs.filter((job) => job.district === district).length;
    const districts =
        coverage.districts.length === 1
            ? t('auditor.coverage.count_one')
            : t('auditor.coverage.count_other', {
                  count: coverage.districts.length,
              });

    return (
        <section
            aria-label={t('auditor.jobs.coverage_title')}
            className="mt-3.5 rounded-2xl border border-[#dbe3ee] bg-rz-surface p-[15px] dark:border-rz-border"
        >
            <div className="flex items-center justify-between gap-3">
                <h2 className="text-[13px] font-bold text-rz-ink">
                    {t('auditor.jobs.coverage_title')}
                </h2>
                <span className="shrink-0 rounded-[10px] border border-[#e0e7f2] bg-white/92 px-[9px] py-[5px] text-[10.5px] font-bold text-[#5b6a86] uppercase dark:border-rz-border dark:bg-rz-surface/90 dark:text-rz-secondary">
                    {t(
                        paged
                            ? 'auditor.jobs.coverage_badge_page'
                            : 'auditor.jobs.coverage_badge',
                        { districts, count: jobs.length },
                    )}
                </span>
            </div>
            {coverage.districts.length > 0 ? (
                <ul className="mt-3 flex flex-wrap gap-2">
                    {coverage.districts.map((district) => {
                        const count = open(district);

                        return (
                            <li
                                key={district}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-xl border px-3 py-[7px] text-[12.5px] font-semibold',
                                    count > 0
                                        ? 'border-[#e6cfa3] bg-[#fbf3ea] text-rz-ink dark:border-[rgba(240,160,96,.35)] dark:bg-[rgba(240,160,96,.12)]'
                                        : 'border-rz-border bg-rz-page text-rz-secondary dark:bg-rz-surface-muted',
                                )}
                            >
                                {district}
                                {count > 0 && (
                                    <span className="rounded-full bg-[#c2661f] px-1.5 text-[10.5px] leading-[16px] font-bold text-white">
                                        {count}
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            ) : (
                <p
                    role="status"
                    className="mt-2.5 text-[12.5px] leading-[1.5] text-rz-ink"
                >
                    {t('auditor.jobs.coverage_none')}
                </p>
            )}
            {!coverage.accepting && (
                <p
                    role="status"
                    className="mt-2.5 text-[12px] leading-[1.5] text-[#8a6d2b] dark:text-[#e3b56a]"
                >
                    {t('auditor.jobs.coverage_paused')}
                </p>
            )}
            <p className="mt-3 flex items-center justify-between gap-3 text-[11.5px] text-rz-secondary">
                <span>
                    {t('auditor.jobs.coverage_weekly', {
                        max: coverage.max_jobs_per_week,
                    })}
                </span>
                <Link
                    href={profile({ query: { section: 'availability' } })}
                    className="shrink-0 font-bold text-rz-ink"
                >
                    {t('auditor.jobs.coverage_edit')}
                </Link>
            </p>
        </section>
    );
}
