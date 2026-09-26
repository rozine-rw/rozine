import { Link } from '@inertiajs/react';
import { SectionLabel } from '@/components/business/audit-cosign/report-summary';
import { ProofFileList } from '@/components/rozine/proof-file-list';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/rozine/format';
import type { BusinessAuditDispute } from '@/types/business-audit';

/** Where the dispute stands, as one label for its copy. */
export type DisputeState =
    | 'under_review'
    | 'escalated'
    | 'amendment_required'
    | 'amended'
    | 'upheld'
    | 'resolved';

/**
 * The dispute's state from the server's `status` and `outcome` alone — never from a timer. An
 * escalated dispute that staff sent back for amendment stays escalated until the amendment is
 * sealed; a resolved one was amended, or upheld and published by staff.
 */
export const disputeState = (dispute: BusinessAuditDispute): DisputeState => {
    if (dispute.status === 'under_review') {
        return 'under_review';
    }

    if (dispute.status === 'escalated') {
        return dispute.outcome === 'amendment_required'
            ? 'amendment_required'
            : 'escalated';
    }

    if (dispute.outcome === 'amended' || dispute.outcome === 'upheld') {
        return dispute.outcome;
    }

    return 'resolved';
};

/**
 * This Business's dispute (N6): where it stands, in neutral words, then exactly what was sent — the
 * submitted time, the supporting text and each proof file with its download — and any note staff
 * recorded. It shows in every dispute state; nothing on it can be signed or disputed again.
 */
export function DisputeRecord({ dispute }: { dispute: BusinessAuditDispute }) {
    const { t, locale } = useTranslation();
    const state = disputeState(dispute);

    return (
        <>
            <div
                role="status"
                className="mt-[11px] rounded-2xl border border-[#fbe4cc] bg-[#fff8f1] p-4 dark:border-transparent dark:bg-[rgba(194,102,31,.12)]"
            >
                <p className="text-[13.5px] font-bold text-rz-ink">
                    {t(`business.audit_cosign.disputed.${state}.title`)}
                </p>
                <p className="mt-1 text-xs leading-[1.55] text-rz-secondary">
                    {t(`business.audit_cosign.disputed.${state}.body`)}
                </p>
                {state === 'amended' && dispute.amendment !== null && (
                    <Link
                        href={dispute.amendment.link}
                        className="mt-2 inline-block text-[12.5px] font-bold text-rz-accent-app-text"
                    >
                        {t('business.audit_cosign.disputed.open_amendment', {
                            report: dispute.amendment.report_id,
                        })}
                    </Link>
                )}
            </div>
            <SectionLabel>
                {t('business.audit_cosign.disputed.record_title')}
            </SectionLabel>
            <p className="mt-[11px] text-[12.5px] text-rz-secondary">
                {t('business.audit_cosign.disputed.submitted', {
                    date: formatDateTime(dispute.submitted_at, locale),
                })}
            </p>
            {dispute.supporting_text !== null && (
                <div className="mt-2.5 rounded-xl border border-rz-border bg-rz-surface px-3.5 py-3">
                    <p className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.audit_cosign.disputed.supporting_text')}
                    </p>
                    <p className="mt-1 text-[12.5px] leading-[1.55] break-words whitespace-pre-line text-rz-ink">
                        {dispute.supporting_text}
                    </p>
                </div>
            )}
            {dispute.proof_files.length > 0 && (
                <ProofFileList
                    label={t('business.audit_cosign.disputed.files')}
                    files={dispute.proof_files}
                    className="mt-2.5"
                />
            )}
            {dispute.resolution_note !== null && (
                <div className="mt-2.5 rounded-xl bg-rz-page px-3.5 py-3 dark:bg-rz-surface-muted">
                    <p className="text-[11px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('business.audit_cosign.disputed.resolution_note')}
                    </p>
                    <p className="mt-1 text-[12.5px] leading-[1.55] break-words whitespace-pre-line text-rz-ink">
                        {dispute.resolution_note}
                    </p>
                </div>
            )}
        </>
    );
}
