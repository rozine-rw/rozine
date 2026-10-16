import {
    formatCountdown,
    formatDueAt,
    useServerNow,
} from '@/components/auditor/clock';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Accepting an offer against its two server clocks (AuditAssignmentClock, #96). A flash audit
 * is due `complete_by` — 24 hours from its original dispatch, never reset by accepting or by a
 * reoffer — and each offer must be taken by `accept_by`. The CTA names the due time, and a line
 * beneath counts the offer down from `server_time` plus elapsed time, never the device clock
 * alone. Once `accept_by` passes, the card says the offer has closed and Accept goes; the server
 * still decides, with `ASSIGNMENT_ACCEPTANCE_EXPIRED`.
 */
export function OfferAccept({
    serverTime,
    acceptBy,
    completeBy,
    canAccept,
    busy,
    disabled,
    onAccept,
    buttonClassName,
}: {
    serverTime: string;
    acceptBy: string;
    /** The flash deadline; null for a routine offer, whose deadline the report calendar owns. */
    completeBy: string | null;
    /** Whether the record's `allowed_actions` lists `assignment.accept`. */
    canAccept: boolean;
    busy: boolean;
    disabled: boolean;
    onAccept: () => void;
    buttonClassName: string;
}) {
    const { t, locale } = useTranslation();
    const now = useServerNow(serverTime);
    const remaining = Date.parse(acceptBy) - now;

    if (remaining <= 0) {
        return (
            <p
                role="status"
                className="mt-3 rounded-xl border border-rz-border bg-rz-page px-3.5 py-3 text-center text-[12.5px] font-semibold text-rz-secondary dark:bg-rz-surface-muted"
            >
                {t('auditor.jobs.offer_closed')}
            </p>
        );
    }

    return (
        <>
            {canAccept && (
                <button
                    type="button"
                    onClick={onAccept}
                    disabled={disabled}
                    aria-busy={busy || undefined}
                    className={buttonClassName}
                >
                    {completeBy === null
                        ? t('auditor.jobs.accept_plain')
                        : t('auditor.jobs.accept_due', {
                              time: formatDueAt(completeBy, locale),
                          })}
                </button>
            )}
            <p
                role="timer"
                aria-label={t('auditor.jobs.offer_label')}
                className={cn(
                    'text-center text-[11px] text-rz-secondary tabular-nums',
                    canAccept ? 'mt-1.5' : 'mt-3',
                )}
            >
                {t('auditor.jobs.offer_open', {
                    time: formatDueAt(acceptBy, locale),
                    left: formatCountdown(remaining),
                })}
            </p>
        </>
    );
}
