import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { formatTimestamp } from '@/components/admin/format';
import { KycDocuments } from '@/components/admin/kyc-documents';
import { ReasonStage } from '@/components/admin/reason-stage';
import { LABEL } from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    InvestorVerificationReview,
    KycCaseStatus,
    StaffViewer,
    Tone,
} from '@/types/admin';

export const KYC_STATUS_TONE: Record<KycCaseStatus, Tone> = {
    draft: 'amber',
    submitted: 'blue',
    approved: 'green',
    rejected: 'red',
};

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className={cn('text-[11px] font-semibold uppercase', LABEL)}>
                {label}
            </dt>
            <dd className="mt-0.5 text-[13px] font-semibold text-rz-ink">
                {value}
            </dd>
        </div>
    );
}

/**
 * One Investor identity submission as Compliance reviews it: the submitted answers, the documents
 * themselves, any decision, the case history and, while it waits, approve and reject through the
 * reason stage. The server rechecks the revision and the permission. `returnTo` asks the server to
 * come back to the Investor directory after a decision instead of the review queue.
 */
export function KycReview({
    review,
    viewer,
    returnTo,
}: {
    review: InvestorVerificationReview;
    viewer: StaffViewer;
    returnTo?: 'directory';
}) {
    const { t } = useTranslation();
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [stage, setStage] = useState<{
        kind: 'approve' | 'reject';
        action: RouteAction;
    } | null>(null);
    const [requestId] = useState(() => crypto.randomUUID());
    const { approve, reject } = review.actions;

    return (
        <>
            <section aria-label={t('admin.kyc.facts')}>
                <dl className="grid grid-cols-2 gap-x-4 gap-y-3">
                    <Fact
                        label={t('admin.kyc.fact.date_of_birth')}
                        value={review.date_of_birth}
                    />
                    <Fact
                        label={t('admin.kyc.fact.id_type')}
                        value={t(`admin.kyc.id_type.${review.id_type}`)}
                    />
                    <Fact
                        label={t('admin.kyc.fact.id_number')}
                        value={review.id_number}
                    />
                    {review.submitted_at && (
                        <Fact
                            label={t('admin.kyc.fact.submitted_at')}
                            value={formatTimestamp(review.submitted_at)}
                        />
                    )}
                </dl>
            </section>
            <section aria-label={t('admin.kyc.documents')} className="mt-5">
                <h3
                    className={cn(
                        'text-[12px] font-bold tracking-[.05em] uppercase',
                        LABEL,
                    )}
                >
                    {t('admin.kyc.documents')}
                </h3>
                <KycDocuments documents={review.documents} />
            </section>
            {review.decision && (
                <section
                    aria-label={t('admin.kyc.decision')}
                    className="mt-5 rounded-[13px] border border-rz-hairline bg-rz-surface px-[15px] py-3"
                >
                    <div className="text-[13px] font-semibold text-rz-ink">
                        {t(`admin.kyc.status.${review.decision.outcome}`)}
                        {' · '}
                        <time
                            dateTime={review.decision.decided_at}
                            className="font-normal text-rz-muted tabular-nums"
                        >
                            {formatTimestamp(review.decision.decided_at)}
                        </time>
                    </div>
                    <p className="mt-1 text-[12.5px] text-rz-body">
                        {review.decision.reason}
                    </p>
                </section>
            )}
            <section aria-label={t('admin.kyc.history')} className="mt-5">
                <h3
                    className={cn(
                        'text-[12px] font-bold tracking-[.05em] uppercase',
                        LABEL,
                    )}
                >
                    {t('admin.kyc.history')}
                </h3>
                <ol className="mt-2.5 overflow-hidden rounded-[13px] border border-rz-hairline bg-rz-surface">
                    {review.history.map((entry) => (
                        <li
                            key={entry.revision}
                            className="border-b border-[#eef2f8] px-[15px] py-2.5 last:border-b-0 dark:border-rz-divider"
                        >
                            <div className="flex items-center gap-2 text-[12.5px]">
                                <span className="flex-1 font-semibold text-rz-ink">
                                    {t(`admin.kyc.command.${entry.command}`)}
                                </span>
                                <time
                                    dateTime={entry.at}
                                    className="text-[11px] text-rz-muted tabular-nums"
                                >
                                    {formatTimestamp(entry.at)}
                                </time>
                            </div>
                            {entry.reason && (
                                <p className="mt-0.5 text-[12px] text-rz-body">
                                    {entry.reason}
                                </p>
                            )}
                        </li>
                    ))}
                </ol>
            </section>
            {errors.form && (
                <p
                    role="alert"
                    className="mt-4 rounded-[11px] bg-[rgba(229,72,77,.1)] px-3.5 py-2.5 text-[12.5px] font-semibold text-[#c2292e]"
                >
                    {errors.form}
                </p>
            )}
            {stage ? (
                <ReasonStage
                    title={t(`admin.kyc.stage.${stage.kind}.title`)}
                    body={t(`admin.kyc.stage.${stage.kind}.body`)}
                    cta={t(`admin.kyc.stage.${stage.kind}.cta`)}
                    placeholder={t(`admin.kyc.stage.${stage.kind}.placeholder`)}
                    tone={stage.kind === 'approve' ? 'green' : 'red'}
                    action={stage.action}
                    viewer={viewer}
                    onCancel={() => setStage(null)}
                    payload={{
                        request_id: requestId,
                        expected_revision: review.revision,
                        ...(returnTo && { return_to: returnTo }),
                    }}
                />
            ) : (
                review.status === 'submitted' && (
                    <div className="mt-5 flex gap-2.5">
                        {reject && (
                            <button
                                type="button"
                                onClick={() =>
                                    setStage({
                                        kind: 'reject',
                                        action: reject,
                                    })
                                }
                                className="h-[42px] flex-1 rounded-[11px] border border-[#e5484d] text-[13.5px] font-bold text-[#c2292e]"
                            >
                                {t('admin.kyc.reject')}
                            </button>
                        )}
                        {approve && (
                            <button
                                type="button"
                                onClick={() =>
                                    setStage({
                                        kind: 'approve',
                                        action: approve,
                                    })
                                }
                                className="h-[42px] flex-1 rounded-[11px] bg-[#1d9e75] text-[13.5px] font-bold text-white"
                            >
                                {t('admin.kyc.approve')}
                            </button>
                        )}
                    </div>
                )
            )}
        </>
    );
}
