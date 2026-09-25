import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { CampaignProgress } from '@/components/business/campaign/campaign-progress';
import { CancelSheet } from '@/components/business/campaign/cancel-sheet';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { PerformanceTrend } from '@/components/business/note/performance-trend';
import { PhotoStrip } from '@/components/business/note/photo-strip';
import { C3Notice } from '@/components/rozine/c3-notice';
import { useBoundedPoll } from '@/hooks/use-bounded-poll';
import { useC3Command } from '@/hooks/use-c3-command';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessCampaignProps } from '@/types/business';

/** The props a poll of an in-flight closing reloads: fresh facts, authority and actions. */
const POLLED = [
    'server_time',
    'allowed_actions',
    'actions',
    'campaign',
    'note',
];

/**
 * A published campaign's progress (C3 v2 §2f, `business.campaigns.show`), opened from the live
 * raise or Your notes. Only aggregate progress is shown: no Investor names, identity kinds or
 * per-Investor amounts, and no investor list (H16). While raising, and only while the server lists
 * `campaign.cancel`, the business may cancel; an in-flight closing is polled within bounds.
 */
export default function BusinessCampaign({
    home,
    note,
    links,
    actions,
    allowed_actions,
    campaign,
    identity_context_revision,
    server_time,
    preview_outcome,
}: BusinessCampaignProps) {
    const { t } = useTranslation();
    const [cancelling, setCancelling] = useState(false);
    const { progress } = note;
    const command = useC3Command<'campaign.cancel'>({
        actions: { 'campaign.cancel': actions.cancel },
        lookup: links.operation,
        lookupQuery: { identity_context_revision },
        allowed: allowed_actions,
        preview: preview_outcome,
    });
    const poll = useBoundedPoll(
        progress.phase === 'funded' && progress.closing.stage === 'in_flight',
        POLLED,
    );
    const canCancel =
        progress.phase === 'raising' &&
        actions.cancel !== null &&
        allowed_actions.includes('campaign.cancel');

    /* The sheet closes as the command goes; its outcome shows on the page, never ahead of it. */
    const cancel = (reason: string | null) => {
        command.send('campaign.cancel', {
            identity_context_revision,
            campaign_id: campaign.id,
            expected_campaign_revision: campaign.revision,
            reason,
        });
        setCancelling(false);
    };

    const sheet = (
        <DetailSheet
            label={note.title}
            close={links.close}
            closeLabel={t('business.note.close')}
            footer={
                canCancel &&
                cancelling && (
                    <CancelSheet
                        busy={command.busy}
                        onClose={() => setCancelling(false)}
                        onConfirm={cancel}
                    />
                )
            }
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-6 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div>
                        <h1 className="text-lg font-semibold text-rz-ink">
                            {note.title}
                        </h1>
                        <p className="text-xs text-rz-secondary">
                            {note.id} ·{' '}
                            <span
                                className={cn(
                                    note.status === 'failed'
                                        ? 'text-rz-danger-text'
                                        : 'text-rz-accent-app-text',
                                )}
                            >
                                {t(`business.note.status.${note.status}`)}
                            </span>
                        </p>
                    </div>
                </div>
                <C3Notice command={command} className="mt-4" />
                <CampaignProgress
                    progress={progress}
                    serverTime={server_time}
                    poll={poll}
                />
                {canCancel && (
                    <button
                        type="button"
                        onClick={() => setCancelling(true)}
                        disabled={command.busy || command.unresolved}
                        aria-busy={command.busy || undefined}
                        className="mt-4 flex h-[46px] w-full items-center justify-center rounded-xl border border-[rgba(229,72,77,.35)] text-sm font-semibold text-rz-danger-text disabled:opacity-60"
                    >
                        {command.busy
                            ? t('business.campaign.cancel.cancelling')
                            : t('business.campaign.cancel.open')}
                    </button>
                )}
                <PhotoStrip photos={note.photos} />
                {note.performance !== null && (
                    <PerformanceTrend
                        ranges={{
                            six_months: note.performance.six_months,
                            twelve_months: note.performance.twelve_months,
                        }}
                    />
                )}
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={note.title}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'right', content: sheet }}
            />
        </BusinessShell>
    );
}
