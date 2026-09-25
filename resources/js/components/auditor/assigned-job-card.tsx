import { Link } from '@inertiajs/react';
import { ClockChip } from '@/components/auditor/clock';
import { StatusPill } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AssignedJob } from '@/types/auditor';

/**
 * An accepted job still on its clock (design L160–171): business, district, distance and step,
 * the ticking time left, and progress through the procedure's steps. Until the procedure publishes
 * its steps there is no step line or bar, never "Step 0 of 0". An overdue job or one reassigned to
 * this partner says so, without naming anyone else (MVP-AUDITOR-SCR-01-ST-02, SCR-02-ST-02).
 */
export function AssignedJobCard({
    job,
    serverTime,
}: {
    job: AssignedJob;
    serverTime: string;
}) {
    const { t } = useTranslation();
    const progress =
        job.step !== null && job.steps !== null && job.steps > 0
            ? { step: job.step, steps: job.steps }
            : null;

    return (
        <Link
            href={job.link}
            className="block w-full rounded-2xl border border-rz-border bg-rz-surface p-[15px] text-left shadow-[0_8px_20px_-14px_rgba(16,40,90,.3)]"
        >
            <div className="flex items-center justify-between gap-2.5">
                <div className="min-w-0">
                    <p className="truncate text-[14.5px] font-bold text-rz-ink">
                        {job.business}
                    </p>
                    <p className="mt-0.5 text-[11.5px] text-rz-secondary">
                        {[
                            job.district,
                            job.distance_km === null
                                ? null
                                : t('auditor.jobs.km', {
                                      distance: job.distance_km,
                                  }),
                            progress === null
                                ? null
                                : t('auditor.job.step_of', progress),
                        ]
                            .filter((part) => part !== null)
                            .join(' · ')}
                    </p>
                </div>
                <ClockChip
                    serverTime={serverTime}
                    dueAt={job.deadline.due_at}
                />
            </div>
            {(job.status !== 'in_progress' || job.reassigned_from !== null) && (
                <div className="mt-2.5 flex flex-wrap items-center gap-1.5">
                    {job.status === 'overdue' && (
                        <StatusPill tone="red">
                            {t('auditor.job.status.overdue')}
                        </StatusPill>
                    )}
                    {job.status === 'awaiting_cosign' && (
                        <StatusPill tone="blue">
                            {t('auditor.job.status.awaiting_cosign')}
                        </StatusPill>
                    )}
                    {job.reassigned_from !== null && (
                        <StatusPill tone="amber">
                            {t('auditor.job.reassigned')}
                        </StatusPill>
                    )}
                </div>
            )}
            {progress !== null && (
                <div className="mt-[11px] h-1.5 overflow-hidden rounded-[4px] bg-[#eef2f8] dark:bg-rz-surface-muted">
                    <div
                        className="h-full rounded-[4px] bg-[linear-gradient(90deg,#c2661f,#2f7bff)]"
                        style={{
                            width: `${(progress.step / progress.steps) * 100}%`,
                        }}
                    />
                </div>
            )}
        </Link>
    );
}
