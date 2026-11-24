import {
    STEP_FORM,
    StepHeading,
    useStepForm,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { TradingStage } from '@/types/auditor';

/**
 * Date trading started (BRS v3 VIS-R02 baseline): the month the business began trading, from its
 * first invoices, statements or licence, as `YYYY-MM`.
 */
export function StepTrading({
    stage,
    context,
}: {
    stage: TradingStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm(context, {
        trading_since: stage.trading_since ?? '',
    });
    const error = errors.trading_since;

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.trading.title')}
                lead={t('auditor.trading.lead')}
            />
            <label className="mt-4 flex items-center justify-between gap-3 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5">
                <span className="text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.trading.since')}
                </span>
                <input
                    type="month"
                    aria-invalid={error ? true : undefined}
                    aria-describedby={
                        error ? 'auditor-trading-error' : undefined
                    }
                    value={form.data.trading_since}
                    onChange={(event) =>
                        form.setData('trading_since', event.target.value)
                    }
                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-bold text-rz-ink"
                />
            </label>
            <FieldError id="auditor-trading-error">{error}</FieldError>
        </form>
    );
}
