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
import type { PremisesStage, PremisesTenure } from '@/types/auditor';

type AssetRow = { item: string; value: string };

const BLANK: AssetRow = { item: '', value: '' };

/**
 * Premises and assets (BRS v3 VIS-R02 baseline): who holds the premises, and the business's main
 * assets as the CPA saw them, each with an estimated value in whole francs.
 */
export function StepPremises({
    stage,
    context,
}: {
    stage: PremisesStage;
    context: StepContext;
}) {
    const { t } = useTranslation();
    const { form, submit, errors } = useStepForm<{
        premises_tenure: PremisesTenure | null;
        assets: AssetRow[];
    }>(context, {
        premises_tenure: stage.premises_tenure,
        assets: stage.assets.map((asset): AssetRow => ({
            item: asset.item,
            value: asset.value.amount,
        })),
    });
    const assets = form.data.assets;

    const change = (index: number, field: keyof AssetRow, value: string) =>
        form.setData(
            'assets',
            assets.map((asset, at) =>
                at === index ? { ...asset, [field]: value } : asset,
            ),
        );

    const send = (event: FormEvent) =>
        submit(event, {
            premises_tenure: form.data.premises_tenure,
            assets: assets.map((asset) => ({
                item: asset.item.trim(),
                value: asset.value,
            })),
        });

    return (
        <form id={STEP_FORM} onSubmit={send} noValidate>
            <StepHeading
                title={t('auditor.premises.title')}
                lead={t('auditor.premises.lead')}
            />
            <fieldset
                aria-invalid={errors.premises_tenure ? true : undefined}
                aria-describedby={
                    errors.premises_tenure
                        ? 'auditor-premises-error'
                        : undefined
                }
                className="mt-4 flex flex-col gap-2"
            >
                <legend className="mb-2 text-[12.5px] font-semibold text-rz-ink">
                    {t('auditor.premises.tenure')}
                </legend>
                {stage.tenures.map((tenure) => (
                    <label
                        key={tenure}
                        className="flex items-center gap-2.5 rounded-[10px] border border-rz-border bg-rz-surface px-3 py-2.5 text-[12.5px] text-rz-ink"
                    >
                        <input
                            type="radio"
                            name="premises-tenure"
                            checked={form.data.premises_tenure === tenure}
                            onChange={() =>
                                form.setData('premises_tenure', tenure)
                            }
                        />
                        {t(`auditor.premises.tenure.${tenure}`)}
                    </label>
                ))}
            </fieldset>
            <FieldError id="auditor-premises-error">
                {errors.premises_tenure}
            </FieldError>
            <h3 className="mt-4 text-[12.5px] font-semibold text-rz-ink">
                {t('auditor.premises.assets')}
            </h3>
            {assets.length === 0 ? (
                <p className="mt-2 rounded-xl bg-rz-surface-muted px-3.5 py-2.5 text-[12px] text-rz-secondary">
                    {t('auditor.premises.none')}
                </p>
            ) : (
                <ol className="mt-2 flex flex-col gap-2">
                    {assets.map((asset, index) => (
                        <li
                            key={index}
                            aria-label={t('auditor.premises.item', {
                                number: index + 1,
                            })}
                            className="grid grid-cols-2 gap-2 rounded-[10px] border border-rz-border bg-rz-surface p-3"
                        >
                            <label className="flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary">
                                {t('auditor.premises.asset')}
                                <input
                                    value={asset.item}
                                    maxLength={100}
                                    aria-invalid={
                                        errors.assets ? true : undefined
                                    }
                                    aria-describedby={
                                        errors.assets
                                            ? 'auditor-assets-error'
                                            : undefined
                                    }
                                    onChange={(event) =>
                                        change(
                                            index,
                                            'item',
                                            event.target.value,
                                        )
                                    }
                                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-[13px] font-semibold text-rz-ink"
                                />
                            </label>
                            <label className="flex flex-col gap-1 text-[11px] font-semibold text-rz-secondary">
                                {t('auditor.premises.value')}
                                <input
                                    inputMode="numeric"
                                    aria-invalid={
                                        errors.assets ? true : undefined
                                    }
                                    aria-describedby={
                                        errors.assets
                                            ? 'auditor-assets-error'
                                            : undefined
                                    }
                                    value={groupDigits(asset.value)}
                                    onChange={(event) =>
                                        change(
                                            index,
                                            'value',
                                            onlyDigits(event.target.value),
                                        )
                                    }
                                    placeholder="0"
                                    className="rounded-lg border border-rz-border bg-rz-page px-2 py-1.5 text-right text-[13px] font-bold text-rz-ink tabular-nums"
                                />
                            </label>
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData(
                                        'assets',
                                        assets.filter((_, at) => at !== index),
                                    )
                                }
                                className="col-span-2 justify-self-end text-[12px] font-semibold text-rz-secondary underline"
                            >
                                {t('auditor.premises.remove', {
                                    number: index + 1,
                                })}
                            </button>
                        </li>
                    ))}
                </ol>
            )}
            <button
                type="button"
                disabled={assets.length >= stage.max}
                onClick={() => form.setData('assets', [...assets, BLANK])}
                className="mt-3 rounded-lg border border-rz-border px-3 py-2 text-[12.5px] font-semibold text-rz-ink disabled:opacity-50"
            >
                {t('auditor.premises.add')}
            </button>
            <FieldError id="auditor-assets-error">{errors.assets}</FieldError>
        </form>
    );
}
