import {
    AmberNote,
    STEP_FORM,
    StepEyebrow,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { compactRwf } from '@/components/auditor/money';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { StatementsStage } from '@/types/auditor';

function Figure({
    label,
    value,
    tone = 'text-rz-ink',
}: {
    label: string;
    value: string;
    tone?: string;
}) {
    return (
        <div className="rounded-xl border border-rz-border bg-rz-surface p-3">
            <p className="text-[10px] font-bold text-rz-secondary uppercase">
                {label}
            </p>
            <p className={cn('mt-[3px] text-[16px] font-bold', tone)}>
                {value}
            </p>
        </div>
    );
}

/**
 * Read the month (design L1253–1267): what the business's statements on file report for the period.
 * Nothing to fill in. When no approved statement is on file the step says so instead of guessing
 * (MVP-AUDITOR-SCR-05-ST-02); there is no automated bank-feed pull.
 *
 * The figures are facts, never a verdict: cover is shown without threshold colours (none are
 * approved) and reads "Unavailable" when it cannot be computed, and only a net inflow reads green.
 * Every original document behind the month is listed, each an ordinary download link.
 */
export function StepStatements({
    stage,
    context,
}: {
    stage: StatementsStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { submit } = useStepForm(context, {});
    const statements = stage.statements;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.statements.title')}
                lead={t('auditor.statements.lead')}
            />
            {statements.status === 'unavailable' ? (
                <AmberNote title={t('auditor.statements.unavailable')}>
                    {statements.reason}
                </AmberNote>
            ) : (
                <>
                    <StepEyebrow className="mt-3.5">
                        {t('auditor.statements.parsed')}
                    </StepEyebrow>
                    <div className="mt-2.5 grid grid-cols-2 gap-[9px]">
                        <Figure
                            label={t('auditor.statements.inflow')}
                            value={compactRwf(statements.inflow)}
                        />
                        <Figure
                            label={t('auditor.statements.outflow')}
                            value={compactRwf(statements.outflow)}
                        />
                        <Figure
                            label={t('auditor.statements.net')}
                            value={compactRwf(statements.net)}
                            tone={
                                Number(statements.net.amount) > 0
                                    ? 'text-rz-positive'
                                    : undefined
                            }
                        />
                        <Figure
                            label={t('auditor.statements.cover')}
                            value={
                                statements.cover === null
                                    ? t('auditor.statements.cover_unavailable')
                                    : `${statements.cover.value}×`
                            }
                            tone={
                                statements.cover === null
                                    ? 'text-rz-secondary'
                                    : undefined
                            }
                        />
                    </div>
                    <StepEyebrow className="mt-3.5">
                        {t('auditor.statements.documents')}
                    </StepEyebrow>
                    {statements.documents.length === 0 ? (
                        <p className="mt-2 text-[11.5px] leading-[1.5] text-rz-secondary">
                            {t('auditor.statements.no_documents')}
                        </p>
                    ) : (
                        <ul
                            aria-label={t('auditor.statements.documents')}
                            className="mt-2 flex flex-col gap-2"
                        >
                            {statements.documents.map((document) => (
                                <li key={document.link.url}>
                                    <a
                                        href={document.link.url}
                                        className="flex items-center gap-2.5 rounded-xl border border-rz-border bg-rz-surface px-[13px] py-3"
                                    >
                                        <span className="text-[18px]">
                                            <Icon name="document" />
                                        </span>
                                        <span className="min-w-0 flex-1 truncate text-[12.5px] font-semibold text-rz-ink">
                                            {document.name}
                                        </span>
                                        <span className="text-[11.5px] font-bold text-rz-ink">
                                            {t('auditor.statements.view')}
                                        </span>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </>
            )}
        </form>
    );
}
