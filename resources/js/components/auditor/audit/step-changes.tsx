import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { ChangeKind, ChangesStage } from '@/types/auditor';

type Answers = Record<ChangeKind, boolean | null>;

/**
 * Changes (BRS v3 VIS-R01 step 14): a yes or no for each change PRO-R05 treats as material. Nothing
 * is pre-answered; the server refuses the step until every question has an answer.
 */
export function StepChanges({
    stage,
    context,
}: {
    stage: ChangesStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const initial = Object.fromEntries(
        stage.kinds.map((kind) => [kind, stage.changes?.[kind] ?? null]),
    ) as Answers;
    const { form, submit, errors } = useStepForm<{ changes: Answers }>(
        context,
        { changes: initial },
    );
    const answer = (kind: ChangeKind, value: boolean) =>
        form.setData('changes', { ...form.data.changes, [kind]: value });

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.changes.title')}
                lead={t('auditor.changes.lead')}
            />
            <div className="mt-4 flex flex-col gap-2">
                {stage.kinds.map((kind) => (
                    <fieldset
                        key={kind}
                        aria-invalid={errors.changes ? true : undefined}
                        aria-describedby={
                            errors.changes ? 'auditor-changes-error' : undefined
                        }
                        className="flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5"
                    >
                        <legend className="sr-only">
                            {t(`auditor.changes.kind.${kind}`)}
                        </legend>
                        <span
                            aria-hidden="true"
                            className="text-[12.5px] font-semibold text-rz-ink"
                        >
                            {t(`auditor.changes.kind.${kind}`)}
                        </span>
                        <span className="flex gap-3 text-[12px] text-rz-ink">
                            <label className="flex items-center gap-1.5">
                                <input
                                    type="radio"
                                    name={`change-${kind}`}
                                    checked={form.data.changes[kind] === true}
                                    onChange={() => answer(kind, true)}
                                />
                                {t('auditor.changes.yes')}
                            </label>
                            <label className="flex items-center gap-1.5">
                                <input
                                    type="radio"
                                    name={`change-${kind}`}
                                    checked={form.data.changes[kind] === false}
                                    onChange={() => answer(kind, false)}
                                />
                                {t('auditor.changes.no')}
                            </label>
                        </span>
                    </fieldset>
                ))}
            </div>
            <FieldError id="auditor-changes-error">{errors.changes}</FieldError>
        </form>
    );
}
