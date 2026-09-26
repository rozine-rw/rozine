import { formatKigaliTime } from '@/components/auditor/clock';
import { StatusPill } from '@/components/auditor/ui';
import type { PillTone } from '@/components/auditor/ui';
import { ProofFileList } from '@/components/rozine/proof-file-list';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import type { AuditDispute, AuditDisputeStatus } from '@/types/audit-dispute';

const STATUS_TONE: Record<AuditDisputeStatus, PillTone> = {
    under_review: 'amber',
    escalated: 'blue',
    resolved: 'neutral',
};

/**
 * What the dispute asks of the CPA now, from its status and outcome alone: review the proof first;
 * once escalated, staff decide, and an amendment resolves it only after staff record that one is
 * required. A resolved dispute needs nothing further.
 */
const guidance = (
    dispute: AuditDispute,
):
    | 'auditor.sealed.dispute.guide.under_review'
    | 'auditor.sealed.dispute.guide.escalated'
    | 'auditor.sealed.dispute.guide.amendment_required'
    | null => {
    if (dispute.status === 'under_review') {
        return 'auditor.sealed.dispute.guide.under_review';
    }

    if (dispute.status === 'escalated') {
        return dispute.outcome === 'amendment_required'
            ? 'auditor.sealed.dispute.guide.amendment_required'
            : 'auditor.sealed.dispute.guide.escalated';
    }

    return null;
};

/**
 * The Business's dispute of a sealed report (N6), for the assigned CPA: where it stands and when it
 * was submitted, the Business's supporting text and proof files exactly as retained — each a plain
 * download from the server's link — and the outcome with any note staff recorded. Upholding the
 * findings is offered only when the server offers it; it sends the dispute to Rozine staff.
 */
export function DisputePanel({
    dispute,
    uphold,
}: {
    dispute: AuditDispute;
    /** Opens the uphold sheet, when the server offers it. */
    uphold: { open: () => void; disabled: boolean } | null;
}) {
    const { t, locale } = useTranslation();
    const guide = guidance(dispute);

    return (
        <section
            aria-labelledby="auditor-dispute-title"
            className="mt-3.5 rounded-2xl border border-[#f0dcb8] bg-rz-surface px-3.5 py-3.5 dark:border-[rgba(240,160,96,.3)]"
        >
            <div className="flex items-center justify-between gap-2.5">
                <h4
                    id="auditor-dispute-title"
                    className="text-[13.5px] font-bold text-rz-ink"
                >
                    {t('auditor.sealed.dispute.title')}
                </h4>
                <StatusPill tone={STATUS_TONE[dispute.status]}>
                    {t(`auditor.sealed.dispute.status.${dispute.status}`)}
                </StatusPill>
            </div>
            <p className="mt-1 text-[11.5px] text-rz-secondary">
                {t('auditor.sealed.dispute.submitted', {
                    date: formatDate(dispute.submitted_at, locale),
                    time: formatKigaliTime(dispute.submitted_at),
                })}
            </p>
            {guide !== null && (
                <p className="mt-2 text-[12px] leading-[1.5] text-rz-slate">
                    {t(guide)}
                </p>
            )}
            {dispute.supporting_text !== null && (
                <div className="mt-2.5">
                    <p className="text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.sealed.dispute.supporting_text')}
                    </p>
                    <p className="mt-1 text-[12.5px] leading-[1.55] break-words whitespace-pre-line text-rz-ink">
                        {dispute.supporting_text}
                    </p>
                </div>
            )}
            {dispute.proof_files.length > 0 && (
                <ProofFileList
                    label={t('auditor.sealed.dispute.files')}
                    files={dispute.proof_files}
                    className="mt-2.5"
                />
            )}
            {dispute.outcome !== null && (
                <p className="mt-2.5 text-[12px] font-bold text-rz-ink">
                    {t(`auditor.sealed.dispute.outcome.${dispute.outcome}`)}
                </p>
            )}
            {dispute.resolution_note !== null && (
                <div className="mt-2 rounded-xl bg-rz-page px-3 py-2.5 dark:bg-rz-surface-muted">
                    <p className="text-[10px] font-bold tracking-[.04em] text-rz-slate uppercase">
                        {t('auditor.sealed.dispute.resolution_note')}
                    </p>
                    <p className="mt-1 text-[12px] leading-[1.5] break-words whitespace-pre-line text-rz-ink">
                        {dispute.resolution_note}
                    </p>
                </div>
            )}
            {uphold !== null && (
                <button
                    type="button"
                    onClick={uphold.open}
                    disabled={uphold.disabled}
                    className="mt-3 flex h-11 w-full items-center justify-center rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {t('auditor.sealed.dispute.uphold')}
                </button>
            )}
        </section>
    );
}
