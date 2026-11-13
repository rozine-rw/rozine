import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { LoanUse, LoanUseStage } from '@/types/auditor';

/**
 * Use of the loan money (BRS v3 VIS-R01 step 13): whether the Rozine note's money went where the
 * prospectus said. Anything but "as planned" needs one plain line saying where it went.
 */
export function StepLoanUse({
    stage,
    context,
}: {
    stage: LoanUseStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm<{
        loan_use: LoanUse | '';
        loan_use_note: string;
    }>(context, { loan_use: stage.loan_use ?? '', loan_use_note: stage.note });
    const noteRequired =
        form.data.loan_use !== '' && form.data.loan_use !== 'as_planned';

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.loan_use.title')}
                lead={t('auditor.loan_use.lead')}
            />
            <fieldset
                className="mt-4 flex flex-col gap-2"
                aria-invalid={errors.loan_use ? true : undefined}
                aria-describedby={
                    errors.loan_use ? 'auditor-loan-use-error' : undefined
                }
            >
                <legend className="sr-only">
                    {t('auditor.loan_use.title')}
                </legend>
                {stage.options.map((option) => (
                    <label
                        key={option}
                        className="flex items-center gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12.5px] font-semibold text-rz-ink"
                    >
                        <input
                            type="radio"
                            name="loan_use"
                            value={option}
                            checked={form.data.loan_use === option}
                            onChange={() => form.setData('loan_use', option)}
                        />
                        {t(`auditor.loan_use.option.${option}`)}
                    </label>
                ))}
            </fieldset>
            <FieldError id="auditor-loan-use-error">
                {errors.loan_use}
            </FieldError>
            <label className="mt-3 block text-[11.5px] font-bold text-rz-ink">
                {noteRequired
                    ? t('auditor.loan_use.note_required')
                    : t('auditor.loan_use.note')}
                <textarea
                    maxLength={200}
                    rows={2}
                    aria-invalid={errors.loan_use_note ? true : undefined}
                    aria-describedby={
                        errors.loan_use_note
                            ? 'auditor-loan-use-note-error'
                            : undefined
                    }
                    value={form.data.loan_use_note}
                    onChange={(event) =>
                        form.setData('loan_use_note', event.target.value)
                    }
                    className="mt-1.5 w-full rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-normal text-rz-ink"
                />
            </label>
            <FieldError id="auditor-loan-use-note-error">
                {errors.loan_use_note}
            </FieldError>
        </form>
    );
}
