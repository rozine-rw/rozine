import { Link } from '@inertiajs/react';
import { useAgo } from '@/components/auditor/clock';
import { useAuditorCommands } from '@/components/auditor/commands';
import { useJobCommands } from '@/components/auditor/job-commands';
import { OfferAccept } from '@/components/auditor/jobs/offer-accept';
import { compactRwf } from '@/components/auditor/money';
import { SectorTile, StatTile, StatusPill } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { DeclineReason, EligibleJob, ServerOption } from '@/types/auditor';

type EligibleCardProps = {
    job: EligibleJob;
    serverTime: string;
    declineOptions: ServerOption<DeclineReason>[];
};

/** A figure the server did not state: a dash, never 0. */
const NO_FIGURE = '—';

/**
 * One open offer (design L218–239): a Flash Audit, or a monthly visit marked as such. The figures
 * are the engine's, and one the draft lacks reads as a dash or "unavailable", never 0; accepting
 * asks the server, which decides whether this partner still gets the file. Decline and conflict
 * sit under the accept button: the design has neither, and the brief requires both (SCR-01-ST-03,
 * AC-08). Each command shows only when this offer's own `allowed_actions` lists it (#96 point 3).
 */
export function EligibleCard({
    job,
    serverTime,
    declineOptions,
}: EligibleCardProps) {
    const { t } = useTranslation();
    const ago = useAgo(serverTime);
    const center = useAuditorCommands();
    const commands = useJobCommands({
        assignment: { id: job.id, revision: job.revision },
        business: job.business,
        scope: job.allowed_actions,
        conflict: job.actions.conflict,
        decline: { route: job.actions.decline, options: declineOptions },
    });

    const accept = () =>
        center.send({
            name: 'assignment.accept',
            business: job.business,
            route: job.actions.accept,
            scope: job.allowed_actions,
            payload: { assignment_id: job.id, expected_revision: job.revision },
        });

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
                            {job.sector === null
                                ? t('auditor.jobs.sector_unavailable')
                                : t(`auditor.sector.${job.sector}`)}{' '}
                            · {job.district} · {ago(job.offered_at)}
                        </span>
                    </span>
                    <span className="shrink-0 text-right">
                        <span className="block text-[10.5px] font-bold text-rz-slate uppercase">
                            {t('auditor.jobs.distance')}
                        </span>
                        <span className="block text-[14px] font-bold text-rz-ink">
                            {job.distance_km === null
                                ? NO_FIGURE
                                : t('auditor.jobs.km', {
                                      distance: job.distance_km,
                                  })}
                        </span>
                    </span>
                </span>
                {job.kind === 'monthly' && (
                    <StatusPill tone="blue" className="mt-2.5 inline-block">
                        {t('auditor.jobs.kind_monthly')}
                    </StatusPill>
                )}
                <div className="mt-3 flex gap-2">
                    <StatTile
                        label={t('auditor.jobs.requested')}
                        value={
                            job.requested === null
                                ? NO_FIGURE
                                : compactRwf(job.requested)
                        }
                    />
                    <StatTile
                        label={t('auditor.jobs.dscr')}
                        value={job.dscr === null ? NO_FIGURE : `${job.dscr}×`}
                    />
                    <StatTile
                        label={t('auditor.jobs.term')}
                        value={
                            job.term_months === null
                                ? NO_FIGURE
                                : t('auditor.jobs.term_months', {
                                      months: job.term_months,
                                  })
                        }
                    />
                </div>
                <span className="mt-2.5 block text-[11.5px] font-bold text-rz-ink">
                    {t('auditor.jobs.view_file')}
                </span>
            </Link>
            <OfferAccept
                serverTime={serverTime}
                acceptBy={job.accept_by}
                completeBy={job.complete_by}
                canAccept={job.allowed_actions.includes('assignment.accept')}
                busy={center.busy}
                disabled={!center.idle}
                onAccept={accept}
                buttonClassName="mt-3 h-[46px] w-full cursor-pointer rounded-xl bg-rz-accent-fill text-[14px] font-bold text-white disabled:cursor-wait disabled:opacity-80"
            />
            {commands.links !== null && (
                <div className="mt-2.5">{commands.links}</div>
            )}
            {commands.sheet}
        </article>
    );
}
