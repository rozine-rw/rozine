import type { FormEvent } from 'react';
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
import type { MessageCode } from '@/lib/i18n/types';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { Money } from '@/types';
import type { DifferenceCause, DifferencesStage } from '@/types/auditor';

const CAUSE_LABEL: Record<DifferenceCause, MessageCode> = {
    unrecorded_sales: 'auditor.differences.cause.unrecorded_sales',
    unproven_expense: 'auditor.differences.cause.unproven_expense',
    personal_spending: 'auditor.differences.cause.personal_spending',
    wrong_month: 'auditor.differences.cause.wrong_month',
    entry_error: 'auditor.differences.cause.entry_error',
    counted_twice: 'auditor.differences.cause.counted_twice',
};

/** Whole francs as a whole number; an empty or non-digit entry counts as nothing. */
const francs = (value: string): bigint =>
    value === '' ? BigInt(0) : BigInt(onlyDigits(value) || '0');

/** Whole francs as server-shaped money, for display only. */
const rwf = (amount: bigint): Money => ({
    currency: 'RWF',
    amount: amount.toString(),
});

/**
 * Explain the difference (BRS v3 VIS-R01 step 9): give each cause that explains the gap between
 * what was counted and what the statements report an amount, until the explained amounts equal the
 * whole difference to the franc. The running total is a guide only; the server checks the sum.
 */
export function StepDifferences({
    stage,
    context,
}: {
    stage: DifferencesStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const saved = new Map(
        stage.explanations.map((item) => [item.cause, item.amount]),
    );
    const { form, submit, errors } = useStepForm(context, {
        amounts: Object.fromEntries(
            stage.causes.map((cause) => [cause, saved.get(cause) ?? '']),
        ) as Record<DifferenceCause, string>,
    });
    const total = francs(stage.difference?.amount ?? '');
    const explained = stage.causes.reduce(
        (sum, cause) => sum + francs(form.data.amounts[cause]),
        BigInt(0),
    );
    const left = total - explained;
    const none = total === BigInt(0);

    const send = (event: FormEvent) =>
        submit(event, {
            explanations: stage.causes
                .filter((cause) => francs(form.data.amounts[cause]) > BigInt(0))
                .map((cause) => ({
                    cause,
                    amount: francs(form.data.amounts[cause]).toString(),
                })),
        });

    return (
        <form id={STEP_FORM} onSubmit={send} noValidate>
            <StepHeading
                title={t('auditor.differences.title')}
                lead={t(
                    none
                        ? 'auditor.differences.lead_none'
                        : 'auditor.differences.lead',
                )}
            />
            <dl className="mt-4 grid grid-cols-3 gap-2 rounded-2xl border border-rz-border bg-rz-surface p-[13px] text-[11px]">
                {(
                    [
                        ['reported', stage.reported],
                        ['observed', stage.observed],
                        ['difference', stage.difference],
                    ] as const
                ).map(([key, money]) => (
                    <div key={key}>
                        <dt className="text-rz-secondary">
                            {t(`auditor.differences.${key}`)}
                        </dt>
                        <dd className="mt-0.5 text-[13px] font-bold text-rz-ink tabular-nums">
                            {money === null
                                ? t('auditor.differences.unavailable')
                                : formatRwf(money)}
                        </dd>
                    </div>
                ))}
            </dl>
            {!none && (
                <>
                    <fieldset className="mt-4 flex flex-col gap-2">
                        <legend className="mb-2 text-[10.5px] font-bold tracking-[.06em] text-rz-secondary uppercase">
                            {t('auditor.differences.causes')}
                        </legend>
                        {stage.causes.map((cause) => (
                            <label
                                key={cause}
                                className="flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2"
                            >
                                <span className="text-[12.5px] font-semibold text-rz-ink">
                                    {t(CAUSE_LABEL[cause])}
                                </span>
                                <span className="flex items-center gap-1.5 text-[12px] text-rz-secondary">
                                    RWF
                                    <input
                                        inputMode="numeric"
                                        aria-label={t(
                                            'auditor.differences.amount',
                                            { cause: t(CAUSE_LABEL[cause]) },
                                        )}
                                        value={groupDigits(
                                            onlyDigits(
                                                form.data.amounts[cause],
                                            ),
                                        )}
                                        onChange={(event) =>
                                            form.setData('amounts', {
                                                ...form.data.amounts,
                                                [cause]: onlyDigits(
                                                    event.target.value,
                                                ),
                                            })
                                        }
                                        placeholder="0"
                                        className="w-28 rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                                    />
                                </span>
                            </label>
                        ))}
                    </fieldset>
                    <p
                        role="status"
                        className={cn(
                            'mt-3 rounded-xl px-3.5 py-2.5 text-[12px] font-semibold',
                            left === BigInt(0)
                                ? 'bg-rz-accent-soft text-rz-positive'
                                : 'bg-[#fff6e5] text-rz-ink dark:bg-rz-surface-muted',
                        )}
                    >
                        {left === BigInt(0)
                            ? t('auditor.differences.balanced')
                            : left > BigInt(0)
                              ? t('auditor.differences.left', {
                                    amount: formatRwf(rwf(left)),
                                })
                              : t('auditor.differences.over', {
                                    amount: formatRwf(rwf(-left)),
                                })}
                    </p>
                </>
            )}
            {errors.explanations && (
                <FieldError id="auditor-differences-error">
                    {errors.explanations}
                </FieldError>
            )}
        </form>
    );
}
