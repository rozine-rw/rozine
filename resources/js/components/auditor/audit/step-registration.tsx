import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { RegistrationStage } from '@/types/auditor';

/**
 * Registration and tax (BRS v3 VIS-R02 baseline): the CPA checks the RDB certificate against the
 * business's record and enters the tax clearance expiry date as written on the certificate.
 */
export function StepRegistration({
    stage,
    context,
}: {
    stage: RegistrationStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        registration_confirmed: stage.registration_confirmed,
        tax_clearance_expires_on: stage.tax_clearance_expires_on ?? '',
    });
    const confirmed = errors.registration_confirmed;
    const tax = errors.tax_clearance_expires_on;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.registration.title')}
                lead={t('auditor.registration.lead')}
            />
            <label className="mt-4 flex items-start gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12px] text-rz-ink">
                <input
                    type="checkbox"
                    className="mt-0.5"
                    aria-invalid={confirmed ? true : undefined}
                    aria-describedby={
                        confirmed ? 'auditor-registration-error' : undefined
                    }
                    checked={form.data.registration_confirmed}
                    onChange={(event) =>
                        form.setData(
                            'registration_confirmed',
                            event.target.checked,
                        )
                    }
                />
                <span>{t('auditor.registration.confirmed')}</span>
            </label>
            <FieldError id="auditor-registration-error">{confirmed}</FieldError>
            <label className="mt-3 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.registration.tax')}
                </span>
                <input
                    type="date"
                    aria-invalid={tax ? true : undefined}
                    aria-describedby={
                        tax ? 'auditor-registration-tax-error' : undefined
                    }
                    value={form.data.tax_clearance_expires_on}
                    onChange={(event) =>
                        form.setData(
                            'tax_clearance_expires_on',
                            event.target.value,
                        )
                    }
                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-bold text-rz-ink"
                />
            </label>
            <FieldError id="auditor-registration-tax-error">{tax}</FieldError>
        </form>
    );
}
