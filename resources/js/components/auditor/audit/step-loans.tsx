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
import type { LoansStage } from '@/types/auditor';

type LoanRow = { lender: string; outstanding: string; monthly_payment: string };

const BLANK: LoanRow = { lender: '', outstanding: '', monthly_payment: '' };

/**
 * Other loans (BRS v3 VIS-R01 step 8): every loan the business owes anyone other than its Rozine
 * note, with what is still owed and the monthly payment, from the agreement or statement. A
 * business with no other loans continues with an empty list.
 */
export function StepLoans({
    stage,
    context,
}: {
    stage: LoansStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        loans: stage.loans.map((loan): LoanRow => ({
            lender: loan.lender,
            outstanding: loan.outstanding.amount,
            monthly_payment: loan.monthly_payment.amount,
        })),
    });
    const loans = form.data.loans;
    const error = errors.loans;

    const change = (index: number, field: keyof LoanRow, value: string) =>
        form.setData(
            'loans',
            loans.map((loan, at) =>
                at === index ? { ...loan, [field]: value } : loan,
            ),
        );

    const send = (event: FormEvent) =>
        submit(event, {
            loans: loans.map((loan) => ({
                lender: loan.lender.trim(),
                outstanding: loan.outstanding,
                monthly_payment: loan.monthly_payment,
            })),
        });

    return (
        <form id={STEP_FORM} onSubmit={send} noValidate>
            <StepHeading
                title={t('auditor.loans.title')}
                lead={t('auditor.loans.lead')}
            />
            {loans.length === 0 ? (
                <p className="mt-4 rounded-xl bg-rz-surface-muted px-3.5 py-2.5 text-[12px] text-rz-secondary">
                    {t('auditor.loans.none')}
                </p>
            ) : (
                <ol className="mt-4 flex flex-col gap-2">
                    {loans.map((loan, index) => (
                        <li
                            key={index}
                            aria-label={t('auditor.loans.item', {
                                number: index + 1,
                            })}
                            className="grid grid-cols-2 gap-2 rounded-[10px] border border-rz-border bg-rz-surface p-3"
                        >
                            <label className="col-span-2 flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary">
                                {t('auditor.loans.lender')}
                                <input
                                    value={loan.lender}
                                    maxLength={100}
                                    aria-invalid={error ? true : undefined}
                                    aria-describedby={
                                        error
                                            ? 'auditor-loans-error'
                                            : undefined
                                    }
                                    onChange={(event) =>
                                        change(
                                            index,
                                            'lender',
                                            event.target.value,
                                        )
                                    }
                                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-semibold text-rz-ink"
                                />
                            </label>
                            {(['outstanding', 'monthly_payment'] as const).map(
                                (field) => (
                                    <label
                                        key={field}
                                        className="flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary"
                                    >
                                        {t(`auditor.loans.${field}`)}
                                        <input
                                            inputMode="numeric"
                                            aria-invalid={
                                                error ? true : undefined
                                            }
                                            aria-describedby={
                                                error
                                                    ? 'auditor-loans-error'
                                                    : undefined
                                            }
                                            value={groupDigits(loan[field])}
                                            onChange={(event) =>
                                                change(
                                                    index,
                                                    field,
                                                    onlyDigits(
                                                        event.target.value,
                                                    ),
                                                )
                                            }
                                            placeholder="0"
                                            className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                                        />
                                    </label>
                                ),
                            )}
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData(
                                        'loans',
                                        loans.filter((_, at) => at !== index),
                                    )
                                }
                                className="col-span-2 justify-self-end text-[12px] font-semibold text-rz-secondary underline"
                            >
                                {t('auditor.loans.remove', {
                                    number: index + 1,
                                })}
                            </button>
                        </li>
                    ))}
                </ol>
            )}
            <button
                type="button"
                disabled={loans.length >= stage.max}
                onClick={() => form.setData('loans', [...loans, BLANK])}
                className="mt-3 rounded-lg border border-rz-border px-3 py-2 text-[12.5px] font-semibold text-rz-ink disabled:opacity-50"
            >
                {t('auditor.loans.add')}
            </button>
            <FieldError id="auditor-loans-error">{error}</FieldError>
        </form>
    );
}
