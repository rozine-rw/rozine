import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useAgo } from '@/components/auditor/clock';
import { useJobCommands } from '@/components/auditor/job-commands';
import { compactRwf } from '@/components/auditor/money';
import { SectorTile, StatTile } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { EligibleJob } from '@/types/auditor';

type EligibleCardProps = {
    job: EligibleJob;
    serverTime: string;
    flashHours: number;
};

/**
 * One open Flash Audit (design L218–239). The figures are the engine's; accepting asks the server,
 * which decides whether this partner still gets the file. Decline and conflict sit under the
 * accept button: the design has neither, and the brief requires both (SCR-01-ST-03, AC-08).
 */
export function EligibleCard({
    job,
    serverTime,
    flashHours,
}: EligibleCardProps) {
    const { t } = useTranslation();
    const ago = useAgo(serverTime);
    const [accepting, setAccepting] = useState(false);
    const commands = useJobCommands({
        fileId: job.id,
        business: job.business,
        actions: job.actions,
    });

    const accept = () =>
        router.post(
            job.actions.accept.url,
            {},
            {
                onStart: () => setAccepting(true),
                onFinish: () => setAccepting(false),
            },
        );

    return (
        <article
            aria-label={job.business}
            className="rounded-2xl border border-rz-border bg-rz-surface p-[15px] shadow-[0_8px_22px_-16px_rgba(16,40,90,.3)]"
        >
            <Link href={job.link} className="block w-full text-left">
                <span className="flex items-center gap-[11px]">
                    <SectorTile name={job.business} sector={job.sector} />
                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-[15px] font-bold text-rz-ink">
                            {job.business}
                        </span>
                        <span className="mt-0.5 block text-[11.5px] text-rz-secondary">
                            {t(`auditor.sector.${job.sector}`)} · {job.district}{' '}
                            · {ago(job.offered_at)}
                        </span>
                    </span>
                    <span className="shrink-0 text-right">
                        <span className="block text-[10.5px] font-bold text-rz-slate uppercase">
                            {t('auditor.jobs.distance')}
                        </span>
                        <span className="block text-[14px] font-bold text-rz-ink">
                            {t('auditor.jobs.km', {
                                distance: job.distance_km,
                            })}
                        </span>
                    </span>
                </span>
                <div className="mt-3 flex gap-2">
                    <StatTile
                        label={t('auditor.jobs.requested')}
                        value={compactRwf(job.requested)}
                    />
                    <StatTile
                        label={t('auditor.jobs.dscr')}
                        value={job.dscr === null ? '—' : `${job.dscr}×`}
                    />
                    <StatTile
                        label={t('auditor.jobs.term')}
                        value={t('auditor.jobs.term_months', {
                            months: job.term_months,
                        })}
                    />
                </div>
                <span className="mt-2.5 block text-[11.5px] font-bold text-rz-ink">
                    {t('auditor.jobs.view_file')}
                </span>
            </Link>
            <button
                type="button"
                onClick={accept}
                disabled={accepting}
                aria-busy={accepting || undefined}
                className="mt-3 h-[46px] w-full cursor-pointer rounded-xl bg-rz-accent-fill text-[14px] font-bold text-white disabled:cursor-wait disabled:opacity-80"
            >
                {t('auditor.jobs.accept', { hours: flashHours })}
            </button>
            <div className="mt-2.5">{commands.links}</div>
            {commands.sheet}
        </article>
    );
}
