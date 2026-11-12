import {
    STEP_FORM,
    StepHeading,
    groupDigits,
    onlyDigits,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { DrawingsStage } from '@/types/auditor';

/**
 * Owner drawings (BRS v3 VIS-R01 step 7): what the owners took out of the business this month, in
 * whole francs, as the CPA saw it in the statements and the cash book. Nothing taken is 0.
 */
export function StepDrawings({
    stage,
    context,
}: {
    stage: DrawingsStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        owner_drawings: stage.owner_drawings?.amount ?? '',
    });
    const error = errors.owner_drawings;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.drawings.title')}
                lead={t('auditor.drawings.lead')}
            />
            <label className="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.drawings.amount')}
                </span>
                <span className="flex items-center gap-1.5 text-[12px] text-rz-secondary">
                    {t('common.currency.rwf')}
                    <input
                        inputMode="numeric"
                        aria-invalid={error ? true : undefined}
                        aria-describedby={
                            error ? 'auditor-drawings-error' : undefined
                        }
                        value={groupDigits(form.data.owner_drawings)}
                        onChange={(event) =>
                            form.setData(
                                'owner_drawings',
                                onlyDigits(event.target.value),
                            )
                        }
                        placeholder="0"
                        className="w-32 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                    />
                </span>
            </label>
            <p className="mt-2 text-[11.5px] text-rz-secondary">
                {t('auditor.drawings.hint')}
            </p>
            <FieldError id="auditor-drawings-error">{error}</FieldError>
        </form>
    );
}
