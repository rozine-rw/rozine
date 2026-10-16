import { formatCountdown, useServerNow } from '@/components/auditor/clock';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import type { AuditCosign } from '@/types/business-audit';

/** The persisted policy with the 24-hour sign-off or dispute window and automatic approval (N6). */
export const MONTHLY_REVIEW_POLICY = 'monthly-review-2026-09-26';

/**
 * Whether the report was sealed under the 24-hour review policy. Anything else — an earlier
 * monthly policy, Flash, or no policy recorded — keeps its original wording and deadline.
 */
export const underMonthlyReview = (cosign: AuditCosign): boolean =>
    cosign.policy_version === MONTHLY_REVIEW_POLICY;

/**
 * The open 24-hour window, in Robert's held C5 wording (#99): when the report arrived, when the
 * window closes (the server's `due_at`, counted from `delivered_at`) and a countdown to it that
 * runs from `server_time`. At zero it says the window has ended and never "overdue": the server
 * decides what happens at the end of the window, and the page only reads it.
 */
export function ReviewWindow({
    serverTime,
    deliveredAt,
    dueAt,
}: {
    serverTime: string;
    deliveredAt: string | null;
    dueAt: string;
}) {
    const { t, locale } = useTranslation();
    const now = useServerNow(serverTime);
    const remaining = Date.parse(dueAt) - now;
    const open = remaining > 0;

    return (
        <div
            role="note"
            aria-label={t('business.audit_cosign.window.title')}
            className="mt-2 rounded-xl bg-[rgba(194,102,31,.10)] px-3 py-2.5 text-xs leading-normal text-rz-ink"
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-semibold">
                    {t('business.audit_cosign.window.title')}
                </p>
                {open && (
                    <span className="rounded-lg bg-rz-surface px-2 py-0.5 text-[11px] font-bold tabular-nums">
                        {t('business.audit_cosign.window.left', {
                            time: formatCountdown(remaining),
                        })}
                    </span>
                )}
            </div>
            <p className="mt-1 text-rz-secondary">
                {t('business.audit_cosign.window.body')}
            </p>
            {deliveredAt !== null && (
                <p className="mt-1 text-rz-secondary">
                    {t('business.audit_cosign.window.delivered', {
                        date: formatDateTime(deliveredAt, locale),
                    })}
                </p>
            )}
            <p className="mt-1 font-semibold">
                {open
                    ? t('business.audit_cosign.window.due', {
                          date: formatDateTime(dueAt, locale),
                      })
                    : t('business.audit_cosign.window.ended')}
            </p>
            <p className="mt-1 text-rz-secondary">
                {t('business.audit_cosign.window.auto')}
            </p>
        </div>
    );
}
