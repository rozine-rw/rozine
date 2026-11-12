import { useTranslation } from '@/hooks/use-translation';
import { VISIT_GUIDE, VISIT_GUIDE_PARTS } from '@/lib/audit-visit-guide';
import type { AuditStepKey } from '@/types/auditor';

/**
 * The guided visit card above a procedure step (BRS v3 VIS-R01): Do, Enter, Upload and Done when,
 * then a "How to" guide that opens on demand. Steps that collect files also say what may and may
 * never be collected (VIS-R05).
 */
export function StepGuide({ step }: { step: AuditStepKey }) {
    const { t } = useTranslation();
    const copy = VISIT_GUIDE[step];

    return (
        <section
            aria-label={t('auditor.audit.guide.label')}
            className="mb-3.5 rounded-xl border border-rz-border bg-rz-surface px-3.5 py-3"
        >
            <dl className="grid gap-2 text-[12px] leading-[1.5]">
                {VISIT_GUIDE_PARTS.map((part) => {
                    const message = copy[part];

                    return (
                        message !== undefined && (
                            <div key={part} className="grid gap-0.5">
                                <dt className="text-[10.5px] font-bold tracking-[.06em] text-rz-secondary uppercase">
                                    {t(`auditor.audit.guide.part.${part}`)}
                                </dt>
                                <dd className="text-rz-ink">{t(message)}</dd>
                            </div>
                        )
                    );
                })}
            </dl>
            {copy.upload !== undefined && (
                <p
                    role="note"
                    className="mt-2.5 border-t border-rz-border pt-2.5 text-[11px] leading-[1.5] text-rz-slate"
                >
                    {t('auditor.audit.guide.uploads_rule')}
                </p>
            )}
            <details className="mt-2.5 text-[12px] leading-[1.5]">
                <summary className="cursor-pointer font-bold text-rz-accent-app-text">
                    {t('auditor.audit.guide.how_to')}
                </summary>
                <p className="mt-1.5 text-rz-slate">{t(copy.how)}</p>
            </details>
        </section>
    );
}
