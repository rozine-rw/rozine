import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { PeopleStage } from '@/types/auditor';

/**
 * People (BRS v3 VIS-R02 baseline): the CPA checks the identity of every director and owner
 * against the register, and confirms the management profiles the business gave.
 */
export function StepPeople({
    stage,
    context,
}: {
    stage: PeopleStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        owners_confirmed: stage.owners_confirmed,
        management_confirmed: stage.management_confirmed,
    });

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.people.title')}
                lead={t('auditor.people.lead')}
            />
            {(['owners_confirmed', 'management_confirmed'] as const).map(
                (field) => (
                    <div key={field}>
                        <label className="mt-3 flex items-start gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12px] text-rz-ink">
                            <input
                                type="checkbox"
                                className="mt-0.5"
                                aria-invalid={errors[field] ? true : undefined}
                                aria-describedby={
                                    errors[field]
                                        ? `auditor-${field}-error`
                                        : undefined
                                }
                                checked={form.data[field]}
                                onChange={(event) =>
                                    form.setData(field, event.target.checked)
                                }
                            />
                            <span>{t(`auditor.people.${field}`)}</span>
                        </label>
                        <FieldError id={`auditor-${field}-error`}>
                            {errors[field]}
                        </FieldError>
                    </div>
                ),
            )}
        </form>
    );
}
