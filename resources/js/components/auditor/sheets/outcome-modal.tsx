import { useState } from 'react';
import { Tick } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditorOutcome } from '@/types/auditor';

type Tone = 'ok' | 'warn' | 'bad';

const RING: Record<Tone, string> = {
    ok: 'bg-[rgba(29,158,117,.10)]',
    warn: 'bg-rz-accent-soft',
    bad: 'bg-[rgba(192,57,43,.10)]',
};

const DISC: Record<Tone, string> = {
    ok: 'bg-[#17795a]',
    warn: 'bg-[#0c1830] dark:bg-rz-accent-fill',
    bad: 'bg-[#c0392b]',
};

/**
 * The design's result card (L1790–1803): what the server did, said once, in plain words. It is
 * shown for a command's own result, or an outcome the server flashed, and dismissed by the
 * partner. A conflict never names who takes the file next.
 */
export function OutcomeModal({
    outcome,
    onDone,
}: {
    outcome: AuditorOutcome | null;
    /** What Done does after a command's own result: move on to the server's `next` page. */
    onDone?: () => void;
}) {
    const { t, locale } = useTranslation();
    const [dismissed, setDismissed] = useState<AuditorOutcome | null>(null);

    if (outcome === null || dismissed === outcome) {
        return null;
    }

    let tone: Tone = 'ok';
    let title: string;
    let body: string;

    switch (outcome.kind) {
        case 'conflict_declared':
            title = t('auditor.outcome.conflict.title');
            body = outcome.blocking
                ? t(`auditor.outcome.conflict.blocking.${outcome.resolution}`, {
                      business: outcome.business,
                  })
                : t('auditor.outcome.conflict.recorded', {
                      business: outcome.business,
                  });
            tone = 'warn';
            break;
        case 'job_declined':
            title = t('auditor.outcome.declined.title');
            body = t('auditor.outcome.declined.body', {
                business: outcome.business,
            });
            tone = 'warn';
            break;
        case 'report_sealed':
            title = t('auditor.outcome.sealed.title');
            body =
                outcome.month === null
                    ? t('auditor.outcome.sealed.flash', {
                          business: outcome.business,
                          date: formatDate(outcome.cosign_due_on, locale),
                      })
                    : t('auditor.outcome.sealed.monthly', {
                          business: outcome.business,
                          month: formatMonthYearLong(outcome.month, locale),
                          date: formatDate(outcome.cosign_due_on, locale),
                      });
            break;
        case 'changes_requested':
            title = t('auditor.outcome.suggested.title');
            body = t('auditor.outcome.suggested.body');
            tone = 'warn';
            break;
        case 'report_rejected':
            title = t('auditor.outcome.rejected.title');
            body = t('auditor.outcome.rejected.body');
            tone = 'bad';
            break;
    }

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-[rgba(8,18,38,.46)] p-5 lg:absolute lg:rounded-r-[20px]">
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="auditor-outcome-title"
                aria-describedby="auditor-outcome-body"
                className="w-full max-w-[380px] animate-[rz-pop_.4s_ease] rounded-[24px] bg-rz-surface px-[22px] py-[30px] text-center"
            >
                <div
                    className={cn(
                        'mx-auto flex size-20 items-center justify-center rounded-full',
                        RING[tone],
                    )}
                >
                    <div
                        className={cn(
                            'flex size-14 items-center justify-center rounded-full text-white',
                            DISC[tone],
                        )}
                    >
                        {tone === 'ok' ? (
                            <Tick className="size-7" strokeWidth={3} />
                        ) : (
                            <span aria-hidden className="text-[26px] font-bold">
                                {tone === 'bad' ? '✕' : '!'}
                            </span>
                        )}
                    </div>
                </div>
                <h2
                    id="auditor-outcome-title"
                    className="mt-[18px] text-[21px] font-bold text-rz-ink"
                >
                    {title}
                </h2>
                <p
                    id="auditor-outcome-body"
                    className="mt-2 text-[13.5px] leading-[1.55] text-rz-secondary"
                >
                    {body}
                </p>
                <button
                    type="button"
                    onClick={() => {
                        setDismissed(outcome);
                        onDone?.();
                    }}
                    className="mt-5 h-12 w-full rounded-2xl bg-rz-accent-fill text-[14px] font-bold text-white"
                >
                    {t('auditor.outcome.done')}
                </button>
            </div>
        </div>
    );
}
