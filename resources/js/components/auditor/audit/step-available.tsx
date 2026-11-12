import {
    STEP_FORM,
    StepHeading,
    groupDigits,
    onlyDigits,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { ProofToggle } from '@/components/auditor/audit/step-count';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { AvailableStage } from '@/types/auditor';

/**
 * Money available now (BRS v3 VIS-R02 late visit): the cash and balances the business could pay with
 * today, in whole francs, with every financial proof the CPA saw ticked.
 */
export function StepAvailable({
    stage,
    context,
}: {
    stage: AvailableStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm<{
        cash_available: string;
        financial_proofs: string[];
    }>(context, {
        cash_available: stage.cash_available?.amount ?? '',
        financial_proofs: stage.financial_proofs
            .filter((proof) => proof.seen)
            .map((proof) => proof.key),
    });
    const toggle = (key: string) =>
        form.setData(
            'financial_proofs',
            form.data.financial_proofs.includes(key)
                ? form.data.financial_proofs.filter((item) => item !== key)
                : [...form.data.financial_proofs, key],
        );

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.available.title')}
                lead={t('auditor.available.lead')}
            />
            <div className="mt-4 flex flex-col gap-[7px]">
                {stage.financial_proofs.map((proof) => (
                    <ProofToggle
                        key={proof.key}
                        proof={proof}
                        on={form.data.financial_proofs.includes(proof.key)}
                        onToggle={() => toggle(proof.key)}
                    />
                ))}
            </div>
            <FieldError id="auditor-available-proofs-error">
                {errors.financial_proofs}
            </FieldError>
            <label className="mt-3 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.available.amount')}
                </span>
                <span className="flex items-center gap-1.5 text-[12px] text-rz-secondary">
                    {t('common.currency.rwf')}
                    <input
                        inputMode="numeric"
                        aria-invalid={errors.cash_available ? true : undefined}
                        aria-describedby={
                            errors.cash_available
                                ? 'auditor-available-error'
                                : undefined
                        }
                        value={groupDigits(form.data.cash_available)}
                        onChange={(event) =>
                            form.setData(
                                'cash_available',
                                onlyDigits(event.target.value),
                            )
                        }
                        placeholder="0"
                        className="w-32 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                    />
                </span>
            </label>
            <p className="mt-2 text-[11.5px] text-rz-secondary">
                {t('auditor.available.hint')}
            </p>
            <FieldError id="auditor-available-error">
                {errors.cash_available}
            </FieldError>
        </form>
    );
}
