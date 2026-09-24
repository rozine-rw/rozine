import { useState } from 'react';
import { KeyValues } from '@/components/business/onboarding/key-values';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RegistryRecord } from '@/types/business';

type StepConfirmProps = {
    registry: RegistryRecord;
    industry: string;
    industries: { value: string; label: string }[];
    onIndustry: (value: string) => void;
};

/** Step 1 of 4 — "Confirm your business" (design L1579–1623), pulled from the RDB record. */
export function StepConfirm({
    registry,
    industry,
    industries,
    onIndustry,
}: StepConfirmProps) {
    const { t, locale } = useTranslation();
    const [open, setOpen] = useState(false);
    const current = industries.find((option) => option.value === industry);

    return (
        <>
            <h2 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                {t('business.onboarding.confirm.title')}
            </h2>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                {t('business.onboarding.confirm.subtitle')}
            </p>
            <div className="mt-4">
                <KeyValues
                    rows={[
                        {
                            label: t('business.onboarding.confirm.name'),
                            value: registry.name,
                        },
                        {
                            label: t(
                                'business.onboarding.confirm.company_code',
                            ),
                            value: registry.company_code,
                        },
                        {
                            label: t('business.onboarding.confirm.legal_form'),
                            value: registry.legal_form,
                        },
                        {
                            label: t('business.onboarding.confirm.registered'),
                            value: formatDate(registry.registered_on, locale),
                        },
                        {
                            label: t('business.onboarding.confirm.status'),
                            value: (
                                <span className="text-rz-accent-app-text">
                                    ●{' '}
                                    {t(
                                        `business.onboarding.confirm.status_${registry.status}`,
                                    )}
                                </span>
                            ),
                        },
                        {
                            label: t('business.onboarding.confirm.staff'),
                            value:
                                registry.staff === null
                                    ? '—'
                                    : t(
                                          'business.onboarding.confirm.staff_count',
                                          { count: registry.staff },
                                      ),
                        },
                        {
                            label: t('business.onboarding.confirm.address'),
                            value: (
                                <span className="text-[12.5px]">
                                    {registry.address}
                                </span>
                            ),
                        },
                    ]}
                />
            </div>

            <div className="mt-4 flex items-center justify-between gap-2">
                <span
                    id="industry-label"
                    className="text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase"
                >
                    {t('business.onboarding.confirm.industry')}
                </span>
                <span className="inline-flex shrink-0 items-center gap-[5px] rounded-[10px] border border-[#cdeddb] bg-[#e7f7ee] px-2 py-[3px] text-[10.5px] font-bold text-rz-accent-app-text uppercase dark:border-transparent dark:bg-rz-accent-soft">
                    {t('business.onboarding.confirm.auto_detected')}
                </span>
            </div>
            <div className="relative mt-[9px]">
                <button
                    type="button"
                    aria-haspopup="listbox"
                    aria-expanded={open}
                    aria-labelledby="industry-label"
                    onClick={() => setOpen((value) => !value)}
                    className={cn(
                        'flex w-full items-center gap-2.5 rounded-xl border bg-rz-surface p-3.5 text-left',
                        open ? 'border-rz-accent-fill' : 'border-rz-border',
                    )}
                >
                    <span className="flex-1 text-[14.5px] font-semibold text-rz-ink">
                        {current?.label ?? industry}
                    </span>
                    <span
                        aria-hidden
                        className={cn(
                            'shrink-0 text-[10px] text-rz-secondary transition-transform duration-200',
                            open && 'rotate-180',
                        )}
                    >
                        ▼
                    </span>
                </button>
                {open && (
                    <ul
                        role="listbox"
                        aria-labelledby="industry-label"
                        className="rz-scroll absolute inset-x-0 top-[calc(100%+6px)] z-40 max-h-[230px] animate-[rz-fade_.16s_ease] overflow-y-auto rounded-2xl border border-rz-border bg-rz-surface p-1.5 shadow-[0_20px_44px_-16px_rgba(20,45,95,.34)]"
                    >
                        {industries.map((option) => {
                            const on = option.value === industry;

                            return (
                                <li
                                    key={option.value}
                                    role="option"
                                    aria-selected={on}
                                >
                                    <button
                                        type="button"
                                        onClick={() => {
                                            onIndustry(option.value);
                                            setOpen(false);
                                        }}
                                        className={cn(
                                            'flex w-full items-center justify-between gap-2 rounded-[10px] px-3 py-[11px] text-left text-[13.5px]',
                                            on
                                                ? 'bg-[rgba(30,58,255,.08)] font-semibold text-rz-investor-text'
                                                : 'font-medium text-rz-slate',
                                        )}
                                    >
                                        <span>{option.label}</span>
                                        {on && (
                                            <span
                                                aria-hidden
                                                className="text-[13px] text-rz-accent-app-text"
                                            >
                                                ✓
                                            </span>
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
            <p className="mt-2 text-[11px] leading-[1.45] text-rz-secondary">
                {t('business.onboarding.confirm.industry_help', {
                    category: registry.category,
                })}
            </p>

            <p className="mt-3.5 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {t('business.onboarding.confirm.management')}
            </p>
            <ul className="mt-[9px] flex flex-col gap-[9px]">
                {registry.management.map((person) => (
                    <li
                        key={`${person.name}-${person.role}`}
                        className="flex items-center gap-[11px] rounded-xl border border-rz-border bg-rz-surface px-[13px] py-[11px]"
                    >
                        <span className="flex size-[34px] items-center justify-center rounded-full bg-rz-page text-[13px]">
                            <Icon name="person" />
                        </span>
                        <span className="flex-1">
                            <span className="block text-[13.5px] font-semibold text-rz-ink">
                                {person.name}
                            </span>
                            <span className="block text-[11.5px] text-rz-secondary">
                                {person.role}
                            </span>
                        </span>
                    </li>
                ))}
            </ul>

            <p className="mt-3.5 text-[11px] font-bold tracking-[.05em] text-rz-slate uppercase">
                {t('business.onboarding.confirm.shareholders')}
            </p>
            <ul className="mt-[9px] flex flex-col gap-[11px] rounded-2xl border border-rz-border bg-rz-surface p-3.5">
                {registry.shareholders.map((holder, index) => (
                    <li key={holder.name} className="flex items-center gap-2.5">
                        <span className="w-[150px] text-[12.5px] text-rz-ink">
                            {holder.name}
                        </span>
                        <span className="h-[7px] flex-1 rounded-[4px] bg-rz-border">
                            <span
                                className={cn(
                                    'block h-full rounded-[4px]',
                                    [
                                        'bg-[#1e3aff]',
                                        'bg-rz-accent-fill',
                                        'bg-rz-ink',
                                    ][index % 3],
                                )}
                                style={{ width: `${holder.share_pct}%` }}
                            />
                        </span>
                        <span className="w-[34px] text-right text-xs text-rz-secondary">
                            {holder.share_pct}%
                        </span>
                    </li>
                ))}
            </ul>
        </>
    );
}
