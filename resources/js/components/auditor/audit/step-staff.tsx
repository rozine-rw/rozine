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
import type { StaffStage } from '@/types/auditor';

/**
 * Staff count (BRS v3 VIS-R01 step 10): how many people the business employs, read from the RSSB
 * declaration's total page with the names covered (VIS-R05 never collects employee details).
 */
export function StepStaff({
    stage,
    context,
}: {
    stage: StaffStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        staff_count: stage.staff_count ?? '',
        rssb_checked: stage.rssb_checked,
    });

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.staff.title')}
                lead={t('auditor.staff.lead')}
            />
            <label className="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.staff.count')}
                </span>
                <input
                    inputMode="numeric"
                    aria-invalid={errors.staff_count ? true : undefined}
                    aria-describedby={
                        errors.staff_count ? 'auditor-staff-error' : undefined
                    }
                    value={groupDigits(form.data.staff_count)}
                    onChange={(event) =>
                        form.setData(
                            'staff_count',
                            onlyDigits(event.target.value),
                        )
                    }
                    placeholder="0"
                    className="w-24 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                />
            </label>
            <FieldError id="auditor-staff-error">
                {errors.staff_count}
            </FieldError>
            <label className="mt-3 flex items-start gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12px] text-rz-ink">
                <input
                    type="checkbox"
                    className="mt-0.5"
                    aria-invalid={errors.rssb_checked ? true : undefined}
                    aria-describedby={
                        errors.rssb_checked ? 'auditor-rssb-error' : undefined
                    }
                    checked={form.data.rssb_checked}
                    onChange={(event) =>
                        form.setData('rssb_checked', event.target.checked)
                    }
                />
                <span>{t('auditor.staff.rssb_checked')}</span>
            </label>
            <FieldError id="auditor-rssb-error">
                {errors.rssb_checked}
            </FieldError>
        </form>
    );
}
