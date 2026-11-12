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
import type { CostCategory, CostsStage } from '@/types/auditor';

type Amounts = Record<CostCategory, string>;

/**
 * Costs (BRS v3 VIS-R01 step 5): the month's costs in the six categories the Business also uses
 * for its monthly submission (VIS-R04), in whole francs. Every category needs an amount; a
 * category with no cost is 0.
 */
export function StepCosts({
    stage,
    context,
}: {
    stage: CostsStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const initial = Object.fromEntries(
        stage.categories.map((category) => [
            category.key,
            category.amount?.amount ?? '',
        ]),
    ) as Amounts;
    const { form, submit, errors } = useStepForm<{ costs: Amounts }>(context, {
        costs: initial,
    });
    const total = stage.categories.reduce(
        (sum, category) => sum + BigInt(form.data.costs[category.key] || '0'),
        0n,
    );

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.costs.title')}
                lead={t('auditor.costs.lead')}
            />
            <div className="mt-4 flex flex-col gap-2">
                {stage.categories.map((category) => (
                    <label
                        key={category.key}
                        className="flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5"
                    >
                        <span className="text-[12.5px] font-semibold text-rz-ink">
                            {t(`auditor.costs.category.${category.key}`)}
                        </span>
                        <span className="flex items-center gap-1.5 text-[12px] text-rz-secondary">
                            {t('common.currency.rwf')}
                            <input
                                inputMode="numeric"
                                aria-invalid={errors.costs ? true : undefined}
                                aria-describedby={
                                    errors.costs
                                        ? 'auditor-costs-error'
                                        : undefined
                                }
                                value={groupDigits(
                                    form.data.costs[category.key],
                                )}
                                onChange={(event) =>
                                    form.setData('costs', {
                                        ...form.data.costs,
                                        [category.key]: onlyDigits(
                                            event.target.value,
                                        ),
                                    })
                                }
                                placeholder="0"
                                className="w-32 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                            />
                        </span>
                    </label>
                ))}
            </div>
            <p className="mt-3 flex items-center justify-between px-3 text-[12.5px] font-bold text-rz-ink">
                <span>{t('auditor.costs.total')}</span>
                <span className="tabular-nums">
                    {t('common.currency.rwf')} {groupDigits(total.toString())}
                </span>
            </p>
            <p className="mt-2 text-[11.5px] text-rz-secondary">
                {t('auditor.costs.hint')}
            </p>
            <FieldError id="auditor-costs-error">{errors.costs}</FieldError>
        </form>
    );
}
