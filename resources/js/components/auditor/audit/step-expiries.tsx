import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { ExpiriesStage } from '@/types/auditor';

/**
 * Tax and RSSB expiry dates (BRS v3 VIS-R01 step 12), as printed on each clearance certificate. A
 * date already past is still recorded as seen; the registry check acts on it (PRO-R05).
 */
export function StepExpiries({
    stage,
    context,
}: {
    stage: ExpiriesStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        tax_clearance_expires_on: stage.tax_clearance_expires_on ?? '',
        rssb_clearance_expires_on: stage.rssb_clearance_expires_on ?? '',
    });
    const tax = errors.tax_clearance_expires_on;
    const rssb = errors.rssb_clearance_expires_on;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.expiries.title')}
                lead={t('auditor.expiries.lead')}
            />
            <label className="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.expiries.tax')}
                </span>
                <input
                    type="date"
                    aria-invalid={tax ? true : undefined}
                    aria-describedby={
                        tax ? 'auditor-tax-expiry-error' : undefined
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
            <FieldError id="auditor-tax-expiry-error">{tax}</FieldError>
            <label className="mt-3 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.expiries.rssb')}
                </span>
                <input
                    type="date"
                    aria-invalid={rssb ? true : undefined}
                    aria-describedby={
                        rssb ? 'auditor-rssb-expiry-error' : undefined
                    }
                    value={form.data.rssb_clearance_expires_on}
                    onChange={(event) =>
                        form.setData(
                            'rssb_clearance_expires_on',
                            event.target.value,
                        )
                    }
                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-bold text-rz-ink"
                />
            </label>
            <FieldError id="auditor-rssb-expiry-error">{rssb}</FieldError>
        </form>
    );
}
