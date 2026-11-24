import {
    STEP_FORM,
    StepHeading,
    groupDigits,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { HistoryStage } from '@/types/auditor';

/**
 * History (BRS v3 VIS-R02 baseline): the verified money in and out for up to 36 months, newest
 * first, which the CPA compares with the statements before ticking that every month matches.
 */
export function StepHistory({
    stage,
    context,
}: {
    stage: HistoryStage;
    context: StepContext;
}) {
    const { t, locale } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        history_checked: stage.history_checked,
    });
    const month = (value: string) =>
        new Intl.DateTimeFormat(locale, {
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(`${value}-01T00:00:00Z`));

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.history.title')}
                lead={t('auditor.history.lead', {
                    count: stage.months.length,
                })}
            />
            {stage.months.length === 0 ? (
                <p className="mt-4 rounded-xl bg-rz-surface-muted px-3.5 py-2.5 text-[12px] text-rz-secondary">
                    {t('auditor.history.none')}
                </p>
            ) : (
                <div className="mt-4 max-h-80 overflow-y-auto rounded-[10px] border border-rz-border">
                    <table className="w-full text-[12px] text-rz-ink">
                        <thead className="sticky top-0 bg-rz-surface-muted text-[11px] text-rz-secondary">
                            <tr>
                                <th scope="col" className="px-3 py-2 text-left">
                                    {t('auditor.history.month')}
                                </th>
                                <th
                                    scope="col"
                                    className="px-3 py-2 text-right"
                                >
                                    {t('auditor.history.in')}
                                </th>
                                <th
                                    scope="col"
                                    className="px-3 py-2 text-right"
                                >
                                    {t('auditor.history.out')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {stage.months.map((row) => (
                                <tr
                                    key={row.month}
                                    className="border-t border-rz-border"
                                >
                                    <th
                                        scope="row"
                                        className="px-3 py-1.5 text-left font-semibold"
                                    >
                                        {month(row.month)}
                                    </th>
                                    <td className="px-3 py-1.5 text-right tabular-nums">
                                        {groupDigits(row.inflow.amount)}
                                    </td>
                                    <td className="px-3 py-1.5 text-right tabular-nums">
                                        {groupDigits(row.outflow.amount)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            <label className="mt-3 flex items-start gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12px] text-rz-ink">
                <input
                    type="checkbox"
                    className="mt-0.5"
                    aria-invalid={errors.history_checked ? true : undefined}
                    aria-describedby={
                        errors.history_checked
                            ? 'auditor-history-error'
                            : undefined
                    }
                    checked={form.data.history_checked}
                    onChange={(event) =>
                        form.setData('history_checked', event.target.checked)
                    }
                />
                <span>{t('auditor.history.checked')}</span>
            </label>
            <FieldError id="auditor-history-error">
                {errors.history_checked}
            </FieldError>
        </form>
    );
}
