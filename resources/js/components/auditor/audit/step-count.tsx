import {
    STEP_FORM,
    StepEyebrow,
    StepHeading,
    VarianceChip,
    groupDigits,
    onlyDigits,
    useStepForm,
    useVariancePreview,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { Icon } from '@/components/rozine/icon';
import type { IconName } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDayMonth, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { CountStage, ProofItem } from '@/types/auditor';

type Operational = NonNullable<CountStage['operational_status']>;

const OPERATIONAL: Operational[] = ['active', 'suspended', 'restricted'];

const OPERATIONAL_ON: Record<Operational, string> = {
    active: 'border-rz-positive text-rz-positive',
    suspended:
        'border-[#d0342c] text-[#d0342c] dark:border-rz-danger-text dark:text-rz-danger-text',
    restricted: 'border-[#0c1830] text-rz-ink dark:border-rz-ink',
};

/** The design's proof glyphs (L3726–3729), by the proof's key. */
const PROOF_ICON: Record<string, IconName> = {
    bank: 'bank',
    momo: 'phone',
    cash: 'money-bag',
    po: 'receipt',
    photo: 'camera',
};

const INSET_FIELD =
    'flex items-center gap-2 rounded-[10px] border border-rz-border bg-[#f6f8fb] px-3 dark:bg-rz-surface-sunken';

function ProofToggle({
    proof,
    on,
    onToggle,
}: {
    proof: ProofItem;
    on: boolean;
    onToggle: () => void;
}) {
    return (
        <button
            type="button"
            role="checkbox"
            aria-checked={on}
            onClick={onToggle}
            className={cn(
                'flex w-full items-center gap-2.5 rounded-[10px] border px-[11px] py-2.5 text-left',
                on
                    ? 'border-[#cfe9d8] bg-rz-page dark:border-[rgba(63,205,160,.3)] dark:bg-rz-surface-muted'
                    : 'border-rz-border bg-[#f8fafc] dark:bg-rz-surface-sunken',
            )}
        >
            <span className="flex size-[30px] shrink-0 items-center justify-center rounded-[10px] bg-rz-page text-[13px] dark:bg-rz-surface-muted">
                <Icon name={PROOF_ICON[proof.key] ?? 'document'} tone="amber" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-[12px] font-bold text-rz-ink">
                    {proof.label}
                </span>
                <span className="mt-px block text-[10.5px] text-rz-secondary">
                    {proof.hint}
                </span>
            </span>
            <span
                aria-hidden
                className={cn(
                    'shrink-0 text-[13px] font-bold',
                    on ? 'text-rz-positive' : 'text-rz-secondary',
                )}
            >
                {on ? '✓' : '＋'}
            </span>
        </button>
    );
}

/**
 * Count and cash (design L1269–1369): tick the proof actually seen, then record what was observed.
 * The server measures each figure against the statements on file under the policy tolerance. The
 * design's "credit discount factor" readout is credit logic and is removed (MVP-AUDITOR-AC-02).
 */
export function StepCount({
    stage,
    context,
}: {
    stage: CountStage;
    context: StepContext;
}) {
    const { t, locale } = useTranslation();
    const { form, submit } = useStepForm(context, {
        financial_proofs: stage.financial_proofs
            .filter((proof) => proof.seen)
            .map((proof) => proof.key),
        inventory_proofs: stage.inventory_proofs
            .filter((proof) => proof.seen)
            .map((proof) => proof.key),
        cash: stage.cash.observed?.amount ?? '',
        stock_units: stage.stock.observed_units ?? '',
        operational_status: stage.operational_status ?? '',
    });

    useVariancePreview({
        cash: form.data.cash,
        stock_units: form.data.stock_units,
    });

    const toggle = (
        field: 'financial_proofs' | 'inventory_proofs',
        key: string,
    ) =>
        form.setData(
            field,
            form.data[field].includes(key)
                ? form.data[field].filter((item) => item !== key)
                : [...form.data[field], key],
        );

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            <StepHeading
                title={t('auditor.count.title')}
                lead={t('auditor.count.lead')}
            />
            <div className="mt-4 flex items-center gap-2">
                <StepEyebrow>{t('auditor.count.financial')}</StepEyebrow>
                <span className="rounded-full border border-[#d4af37] bg-rz-accent-soft px-[7px] py-0.5 text-[10.5px] font-bold tracking-[.07em] text-rz-ink uppercase">
                    {t('auditor.count.vault')}
                </span>
            </div>
            <div className="mt-2.5 flex flex-col gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[13px]">
                <div className="flex flex-col gap-[7px]">
                    {stage.financial_proofs.map((proof) => (
                        <ProofToggle
                            key={proof.key}
                            proof={proof}
                            on={form.data.financial_proofs.includes(proof.key)}
                            onToggle={() =>
                                toggle('financial_proofs', proof.key)
                            }
                        />
                    ))}
                </div>
                <div>
                    <label
                        htmlFor="auditor-cash"
                        className="text-[11.5px] font-bold text-rz-ink"
                    >
                        {t('auditor.count.cash')}
                    </label>
                    <div className="mt-[7px] flex items-center gap-2">
                        <div className={cn(INSET_FIELD, 'flex-1')}>
                            <span className="text-[10.5px] font-bold text-rz-slate">
                                {t('common.currency.rwf')}
                            </span>
                            <input
                                id="auditor-cash"
                                inputMode="numeric"
                                autoComplete="off"
                                placeholder="0"
                                value={groupDigits(form.data.cash)}
                                onChange={(event) =>
                                    form.setData(
                                        'cash',
                                        onlyDigits(event.target.value),
                                    )
                                }
                                className="h-[42px] min-w-0 flex-1 bg-transparent text-[15px] font-bold text-rz-ink outline-none"
                            />
                        </div>
                        <VarianceChip variance={stage.cash.variance} compact />
                    </div>
                    <p className="mt-1.5 text-[10.5px] text-rz-secondary">
                        {stage.cash.statement === null
                            ? t('auditor.count.cash_no_statement')
                            : t('auditor.count.cash_hint', {
                                  amount: formatRwf(stage.cash.statement),
                                  tolerance: stage.tolerance,
                              })}
                    </p>
                </div>
                <div className="flex gap-[9px]">
                    <div
                        className={cn(
                            INSET_FIELD,
                            'block min-w-0 flex-1 px-[11px] py-[9px]',
                        )}
                    >
                        <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('auditor.count.period')}
                        </p>
                        {stage.period === null ? (
                            <p className="mt-[3px] text-[12px] font-bold text-rz-secondary">
                                {t('auditor.count.period_unavailable')}
                            </p>
                        ) : (
                            <p className="mt-[3px] text-[12px] font-bold text-rz-ink">
                                {formatDayMonth(stage.period.from, locale)} –{' '}
                                {formatDayMonth(stage.period.to, locale)}
                            </p>
                        )}
                    </div>
                    <div
                        className={cn(
                            INSET_FIELD,
                            'block min-w-0 flex-1 px-[11px] py-[9px]',
                        )}
                    >
                        <p className="text-[10.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                            {t('auditor.count.account')}
                        </p>
                        <p className="mt-[3px] truncate text-[12px] font-bold text-rz-ink">
                            {stage.account_ref}
                        </p>
                    </div>
                </div>
            </div>

            <div className="mt-[18px] flex items-center gap-2">
                <StepEyebrow>{t('auditor.count.inventory')}</StepEyebrow>
                <span className="rounded-full border border-rz-border bg-rz-surface px-[7px] py-0.5 text-[10.5px] font-bold tracking-[.07em] text-[#3a5fd0] uppercase dark:text-rz-investor-text">
                    {stage.sector.label}
                </span>
            </div>
            <p className="mt-1.5 text-[11px] leading-[1.5] text-rz-secondary">
                {stage.sector.definition}
            </p>
            <div className="mt-2.5 flex flex-col gap-[13px] rounded-2xl border border-rz-border bg-rz-surface p-[13px]">
                <div className="flex flex-col gap-[7px]">
                    {stage.inventory_proofs.map((proof) => (
                        <ProofToggle
                            key={proof.key}
                            proof={proof}
                            on={form.data.inventory_proofs.includes(proof.key)}
                            onToggle={() =>
                                toggle('inventory_proofs', proof.key)
                            }
                        />
                    ))}
                </div>
                <div>
                    <label
                        htmlFor="auditor-stock"
                        className="text-[11.5px] font-bold text-rz-ink"
                    >
                        {t('auditor.count.stock')}
                    </label>
                    <div className="mt-[7px] flex items-center gap-2">
                        <div className={cn(INSET_FIELD, 'flex-1')}>
                            <input
                                id="auditor-stock"
                                inputMode="numeric"
                                autoComplete="off"
                                placeholder="0"
                                value={groupDigits(form.data.stock_units)}
                                onChange={(event) =>
                                    form.setData(
                                        'stock_units',
                                        onlyDigits(event.target.value),
                                    )
                                }
                                className="h-[42px] min-w-0 flex-1 bg-transparent text-[15px] font-bold text-rz-ink outline-none"
                            />
                            <span className="text-[10.5px] font-semibold text-rz-secondary">
                                {t('auditor.count.units')}
                            </span>
                        </div>
                        <VarianceChip variance={stage.stock.variance} compact />
                    </div>
                    <p className="mt-1.5 text-[10.5px] text-rz-secondary">
                        {stage.stock.reported_units === null
                            ? t('auditor.count.stock_no_baseline')
                            : t('auditor.count.stock_hint', {
                                  units: groupDigits(
                                      stage.stock.reported_units,
                                  ),
                                  tolerance: stage.tolerance,
                              })}
                    </p>
                </div>
                <fieldset>
                    <legend className="text-[11.5px] font-bold text-rz-ink">
                        {t('auditor.count.operational')}
                    </legend>
                    <div className="mt-[7px] flex gap-1.5">
                        {OPERATIONAL.map((status) => {
                            const on = form.data.operational_status === status;

                            return (
                                <button
                                    key={status}
                                    type="button"
                                    role="radio"
                                    aria-checked={on}
                                    onClick={() =>
                                        form.setData(
                                            'operational_status',
                                            status,
                                        )
                                    }
                                    className={cn(
                                        'h-10 flex-1 rounded-[10px] border text-[12px] font-bold',
                                        on
                                            ? cn(
                                                  'bg-rz-page dark:bg-rz-surface-muted',
                                                  OPERATIONAL_ON[status],
                                              )
                                            : 'border-rz-border bg-[#f6f8fb] text-rz-secondary dark:bg-rz-surface-sunken',
                                    )}
                                >
                                    {t(`auditor.count.status.${status}`)}
                                </button>
                            );
                        })}
                    </div>
                </fieldset>
            </div>
        </form>
    );
}
