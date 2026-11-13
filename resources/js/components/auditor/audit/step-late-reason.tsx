import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { LateReason, LateReasonStage } from '@/types/auditor';

/**
 * Why the repayment is late (BRS v3 VIS-R02 late visit): one reason from the fixed list. Nothing is
 * negotiated, so there is no plan or promise to record; "other" needs one plain line.
 */
export function StepLateReason({
    stage,
    context,
}: {
    stage: LateReasonStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm<{
        late_reason: LateReason | '';
        late_reason_note: string;
    }>(context, {
        late_reason: stage.late_reason ?? '',
        late_reason_note: stage.late_reason_note,
    });
    const noteRequired = form.data.late_reason === 'other';

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.late_reason.title')}
                lead={t('auditor.late_reason.lead')}
            />
            <fieldset
                className="mt-4 flex flex-col gap-2"
                aria-invalid={errors.late_reason ? true : undefined}
                aria-describedby={
                    errors.late_reason ? 'auditor-late-reason-error' : undefined
                }
            >
                <legend className="sr-only">
                    {t('auditor.late_reason.title')}
                </legend>
                {stage.reasons.map((option) => (
                    <label
                        key={option}
                        className="flex items-center gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12.5px] font-semibold text-rz-ink"
                    >
                        <input
                            type="radio"
                            name="late_reason"
                            value={option}
                            checked={form.data.late_reason === option}
                            onChange={() => form.setData('late_reason', option)}
                        />
                        {t(`auditor.late_reason.option.${option}`)}
                    </label>
                ))}
            </fieldset>
            <FieldError id="auditor-late-reason-error">
                {errors.late_reason}
            </FieldError>
            <label className="mt-3 block text-[11.5px] font-bold text-rz-ink">
                {noteRequired
                    ? t('auditor.late_reason.note_required')
                    : t('auditor.late_reason.note')}
                <textarea
                    maxLength={200}
                    rows={2}
                    aria-invalid={errors.late_reason_note ? true : undefined}
                    aria-describedby={
                        errors.late_reason_note
                            ? 'auditor-late-reason-note-error'
                            : undefined
                    }
                    value={form.data.late_reason_note}
                    onChange={(event) =>
                        form.setData('late_reason_note', event.target.value)
                    }
                    className="mt-1.5 w-full rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-normal text-rz-ink"
                />
            </label>
            <FieldError id="auditor-late-reason-note-error">
                {errors.late_reason_note}
            </FieldError>
        </form>
    );
}
