import { SectionLabel } from '@/components/business/audit-cosign/report-summary';
import {
    ReviewWindow,
    underMonthlyReview,
} from '@/components/business/audit-cosign/review-window';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDayMonth } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditCosign, AuditReport } from '@/types/business-audit';

/**
 * Where the co-signatures stand, as the server counts them: signed against required, each
 * mandated signatory with the current person marked, and when the window closes. A report under
 * the 24-hour review policy shows its open window with a countdown; a legacy monthly report keeps
 * its by-the-7th date; a Flash report has no deadline, so none is shown. A disputed report shows
 * no deadline at all: its window is paused or closed, and it is never called overdue.
 */
export function CosignStatus({
    report,
    cosign,
    serverTime,
}: {
    report: AuditReport;
    cosign: AuditCosign;
    serverTime: string;
}) {
    const { t, locale } = useTranslation();
    const disputed = cosign.dispute !== null;
    const reviewPolicy = underMonthlyReview(cosign);
    const published = (publishedAt: string): string => {
        switch (cosign.published_reason) {
            case 'auto_approved':
                return t('business.audit_cosign.published_auto');
            case 'staff_resolved':
                return t('business.audit_cosign.published_staff', {
                    date: formatDate(publishedAt, locale),
                });
            default:
                return t('business.audit_cosign.published', {
                    date: formatDate(publishedAt, locale),
                });
        }
    };

    return (
        <>
            <SectionLabel>
                {t('business.audit_cosign.status.title')}
            </SectionLabel>
            <div className="mt-[11px] flex flex-wrap items-center justify-between gap-2">
                <p className="text-[13.5px] font-semibold text-rz-ink">
                    {t('business.audit_cosign.status.count', {
                        signed: cosign.signed_count,
                        required: cosign.required_signatures,
                    })}
                </p>
                {report.published_at !== null && (
                    <span className="rounded-[10px] bg-rz-accent-soft px-[9px] py-1 text-[11px] font-semibold text-rz-accent-app-text">
                        {published(report.published_at)}
                    </span>
                )}
            </div>
            {reviewPolicy &&
                cosign.due_at !== null &&
                report.published_at === null &&
                !disputed && (
                    <ReviewWindow
                        serverTime={serverTime}
                        deliveredAt={cosign.delivered_at}
                        dueAt={cosign.due_at}
                    />
                )}
            {!reviewPolicy &&
                cosign.due_at !== null &&
                cosign.published_reason !== 'auto_approved' &&
                !disputed && (
                    <p
                        data-overdue={cosign.overdue || undefined}
                        className={cn(
                            'mt-2 rounded-xl px-3 py-2 text-xs leading-normal font-semibold',
                            cosign.overdue
                                ? 'border border-[#fdeaea] bg-[rgba(229,72,77,.08)] text-rz-danger-text dark:border-[rgba(255,107,111,.25)]'
                                : 'bg-[rgba(194,102,31,.10)] text-rz-ink',
                        )}
                    >
                        {t(
                            cosign.overdue
                                ? 'business.audit_cosign.overdue'
                                : 'business.audit_cosign.due',
                            { date: formatDate(cosign.due_at, locale) },
                        )}
                    </p>
                )}
            <ul
                aria-label={t('business.audit_cosign.status.signers')}
                className="mt-2.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface"
            >
                {cosign.signers.map((signer) => (
                    <li
                        key={`${signer.name}-${signer.is_you}`}
                        aria-current={signer.is_you || undefined}
                        className={cn(
                            'flex items-center justify-between gap-3 border-b border-[#eef2f9] px-[15px] py-[13px] last:border-b-0 dark:border-rz-divider',
                            signer.is_you && 'bg-rz-accent-soft',
                        )}
                    >
                        <p className="flex min-w-0 items-center gap-2 text-[13.5px] font-semibold text-rz-ink">
                            <span className="truncate">{signer.name}</span>
                            {signer.is_you && (
                                <span className="shrink-0 rounded-md bg-rz-accent-fill px-1.5 py-px text-[10px] font-bold tracking-[.04em] text-white uppercase">
                                    {t('business.audit_cosign.status.you')}
                                </span>
                            )}
                        </p>
                        <span
                            className={cn(
                                'inline-flex shrink-0 items-center rounded-[10px] px-[9px] py-1 text-[11px] font-semibold',
                                signer.state === 'signed'
                                    ? 'bg-rz-accent-soft text-rz-accent-app-text'
                                    : 'bg-[rgba(105,116,138,.10)] text-rz-secondary',
                            )}
                        >
                            {signer.signed_at !== null
                                ? t('business.audit_cosign.status.signed_on', {
                                      date: formatDayMonth(
                                          signer.signed_at,
                                          locale,
                                      ),
                                  })
                                : t(
                                      `business.audit_cosign.status.${signer.state}`,
                                  )}
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}
