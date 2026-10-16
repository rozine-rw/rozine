import { Link } from '@inertiajs/react';
import { formatKigaliTime } from '@/components/auditor/clock';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import type { ReturnedStage } from '@/types/auditor';

/**
 * A returned or rejected monthly filing (delivery 2, #96): an immutable terminal report version.
 * It shows the decision, the retained reason in the server's label with the partner's factual
 * explanation, and when it was recorded — never a verdict on the business's credit. What follows
 * is either the one linked amendment already created, or the amendment the server allows now;
 * never both, and this report stays unchanged either way.
 */
export function ReturnedStatus({
    stage,
    amend,
}: {
    stage: ReturnedStage;
    /** Starts the linked amendment, when the server allows one. */
    amend: { run: () => void; disabled: boolean } | null;
}) {
    const { t, locale } = useTranslation();

    return (
        <>
            <h3 className="text-[18px] font-bold text-rz-ink">
                {t(`auditor.returned.title.${stage.status}`)}
            </h3>
            <p className="mt-1.5 text-[12.5px] leading-[1.5] text-rz-secondary">
                {t(`auditor.returned.lead.${stage.status}`)}
            </p>

            <dl className="mt-4 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                <div className="border-b border-[#eef2f9] px-[15px] py-3 dark:border-rz-divider">
                    <dt className="text-[11px] font-bold tracking-[.03em] text-rz-slate uppercase">
                        {t('auditor.returned.reason')}
                    </dt>
                    <dd className="mt-1 text-[13px] font-bold text-rz-ink">
                        {stage.reason.label}
                    </dd>
                    <dd className="mt-1 text-[12px] leading-[1.55] whitespace-pre-line text-rz-slate">
                        {stage.reason.explanation}
                    </dd>
                </div>
                <div className="flex items-baseline justify-between gap-3 px-[15px] py-3">
                    <dt className="text-[12px] text-rz-secondary">
                        {t('auditor.returned.recorded')}
                    </dt>
                    <dd className="text-[12px] font-bold text-rz-ink">
                        {formatDate(stage.recorded_at, locale)} ·{' '}
                        {formatKigaliTime(stage.recorded_at)}
                    </dd>
                </div>
            </dl>

            {stage.amended_by !== null ? (
                <p className="mt-3.5 rounded-2xl border border-rz-border bg-rz-surface px-3.5 py-3 text-[12px] leading-[1.5] text-rz-slate">
                    {t('auditor.returned.amended_by', {
                        report: stage.amended_by.report_id,
                    })}{' '}
                    <Link
                        href={stage.amended_by.link}
                        className="font-bold text-rz-accent-app-text"
                    >
                        {t('auditor.returned.view_amendment')}
                    </Link>
                </p>
            ) : (
                amend !== null && (
                    <button
                        type="button"
                        onClick={amend.run}
                        disabled={amend.disabled}
                        className="mt-3.5 flex h-11 w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[13.5px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {t('auditor.returned.amend')}
                    </button>
                )
            )}
        </>
    );
}
