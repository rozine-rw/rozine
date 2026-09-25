import { SectionLabel } from '@/components/business/audit-cosign/report-summary';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatDayMonth } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { AuditCosign, AuditReport } from '@/types/business-audit';

/**
 * Where the co-signatures stand, as the server counts them: signed against required, each
 * mandated signatory with the current person marked, and — for a monthly report only — the
 * original cycle's co-sign date. A Flash report has no deadline, so none is shown.
 */
export function CosignStatus({
    report,
    cosign,
}: {
    report: AuditReport;
    cosign: AuditCosign;
}) {
    const { t, locale } = useTranslation();
    /* A dispute under review or escalated pauses the window, so no deadline is shown. */
    const paused =
        cosign.dispute?.status === 'under_review' ||
        cosign.dispute?.status === 'escalated';

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
                        {cosign.published_reason === 'auto_approved'
                            ? t('business.audit_cosign.published_auto')
                            : t('business.audit_cosign.published', {
                                  date: formatDate(report.published_at, locale),
                              })}
                    </span>
                )}
            </div>
            {cosign.due_at !== null &&
                cosign.published_reason !== 'auto_approved' &&
                !paused && (
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
