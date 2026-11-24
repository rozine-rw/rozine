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
import type { CashSalesStage } from '@/types/auditor';

/**
 * Cash sales not deposited (BRS v3 VIS-R01 step 4): the month's cash takings that never reached a
 * bank or mobile-money account, in whole francs, from the sales book and receipts. None is 0.
 */
export function StepCashSales({
    stage,
    context,
}: {
    stage: CashSalesStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        undeposited_cash_sales: stage.undeposited_cash_sales?.amount ?? '',
    });
    const error = errors.undeposited_cash_sales;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.cash_sales.title')}
                lead={t('auditor.cash_sales.lead')}
            />
            <label className="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.cash_sales.amount')}
                </span>
                <span className="flex items-center gap-1.5 text-[12px] text-rz-secondary">
                    {t('common.currency.rwf')}
                    <input
                        inputMode="numeric"
                        aria-invalid={error ? true : undefined}
                        aria-describedby={
                            error ? 'auditor-cash-sales-error' : undefined
                        }
                        value={groupDigits(form.data.undeposited_cash_sales)}
                        onChange={(event) =>
                            form.setData(
                                'undeposited_cash_sales',
                                onlyDigits(event.target.value),
                            )
                        }
                        placeholder="0"
                        className="w-32 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                    />
                </span>
            </label>
            <p className="mt-2 text-[11.5px] text-rz-secondary">
                {t('auditor.cash_sales.hint')}
            </p>
            <FieldError id="auditor-cash-sales-error">{error}</FieldError>
        </form>
    );
}
