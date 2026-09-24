import { useForm } from '@inertiajs/react';
import { FieldError } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { LinkedBankAccount } from '@/types/business';

type StepBankProps = {
    companyName: string;
    banks: { code: string; name: string }[];
    account: LinkedBankAccount | null;
    signatories: { id: string; name: string; role: string }[];
    required: number;
    action: RouteAction;
};

type BankFields = {
    bank: string;
    business_account: boolean;
    account_name: string;
    account_number: string;
    signatories: string[];
};

/** The design's rounded tick box: green for the account attestation, blue for a signatory. */
function Box({ on, tone }: { on: boolean; tone: 'green' | 'blue' }) {
    return (
        <span
            aria-hidden
            className={cn(
                'flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-2 text-white',
                tone === 'green' ? 'text-[13px]' : 'text-xs',
                !on && 'border-rz-border',
                on &&
                    tone === 'green' &&
                    'border-rz-accent-fill bg-rz-accent-fill',
                on &&
                    tone === 'blue' &&
                    'border-[#d6e4ff] bg-[#1e3aff] dark:border-[#3d57ff]',
            )}
        >
            {on ? '✓' : ''}
        </span>
    );
}

/**
 * Step 3 of 4 — "Business bank account" (design L1650–1694). Where raised funds are paid out. The
 * number of signatories comes from the company's verified mandate, not a fixed two.
 */
export function StepBank({
    companyName,
    banks,
    account,
    signatories,
    required,
    action,
}: StepBankProps) {
    const { t } = useTranslation();
    const form = useForm<BankFields>({
        bank: '',
        business_account: false,
        account_name: '',
        account_number: '',
        signatories: [],
    });
    const { data } = form;
    const complete =
        data.bank !== '' &&
        data.business_account &&
        data.account_name.trim().length >= 2 &&
        data.account_number.length >= 8 &&
        data.signatories.length >= required;

    const subtitle = (
        <>
            <h2 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                {t('business.onboarding.bank.title')}
            </h2>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                {t('business.onboarding.bank.subtitle', { count: required })}
            </p>
        </>
    );

    if (account !== null) {
        return (
            <>
                {subtitle}
                <div className="mt-4 animate-[rz-fade_.3s_ease] rounded-2xl border border-[#e7f7ee] bg-rz-surface p-4 dark:border-rz-accent-fill">
                    <span className="inline-flex items-center gap-1.5 rounded-[10px] bg-rz-accent-soft px-2.5 py-[5px] text-[11.5px] font-semibold text-rz-accent-app-text">
                        {t('business.onboarding.bank.linked')}
                    </span>
                    <div className="mt-3.5 flex items-center gap-3">
                        <span className="flex size-[38px] items-center justify-center rounded-[10px] bg-rz-page text-[17px]">
                            <Icon name="bank" />
                        </span>
                        <div className="flex-1">
                            <p className="text-[13.5px] font-semibold text-rz-ink">
                                {account.bank_name}
                            </p>
                            <p className="text-[11.5px] text-rz-secondary">
                                {account.masked_number}
                            </p>
                        </div>
                    </div>
                    <p className="mt-[13px] flex items-start gap-2 border-t border-[#eef2f9] pt-3 text-[11px] leading-normal text-rz-secondary dark:border-rz-divider">
                        <span className="shrink-0">
                            <Icon name="lock" />
                        </span>
                        <span>{t('business.onboarding.bank.locked')}</span>
                    </p>
                </div>
            </>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(action.url, { preserveScroll: true });
            }}
        >
            {subtitle}
            <label
                htmlFor="bank"
                className="mt-4 mb-1.5 block text-xs font-semibold text-rz-label uppercase"
            >
                {t('business.onboarding.bank.bank')}
            </label>
            <span className="relative block">
                <span className="pointer-events-none absolute top-1/2 left-[13px] -translate-y-1/2 text-base leading-none">
                    <Icon name="bank" />
                </span>
                <select
                    id="bank"
                    value={data.bank}
                    onChange={(event) =>
                        form.setData('bank', event.target.value)
                    }
                    className={cn(
                        'w-full cursor-pointer appearance-none rounded-xl border border-rz-border bg-rz-field py-[13px] pr-9 pl-[42px] text-sm font-semibold outline-none',
                        data.bank === '' ? 'text-rz-secondary' : 'text-rz-ink',
                    )}
                >
                    <option value="">
                        {t('business.onboarding.bank.select')}
                    </option>
                    {banks.map((bank) => (
                        <option key={bank.code} value={bank.code}>
                            {bank.name}
                        </option>
                    ))}
                </select>
                <svg
                    viewBox="0 0 10 6"
                    fill="none"
                    aria-hidden
                    className="pointer-events-none absolute top-1/2 right-[13px] h-1.5 w-2.5 -translate-y-1/2"
                >
                    <path
                        d="M1 1l4 4 4-4"
                        stroke="currentColor"
                        className="text-rz-secondary"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </span>
            <FieldError id="bank-error">{form.errors.bank}</FieldError>

            <button
                type="button"
                role="checkbox"
                aria-checked={data.business_account}
                onClick={() =>
                    form.setData('business_account', !data.business_account)
                }
                className="mt-3.5 flex w-full items-center gap-3 rounded-xl border border-rz-border bg-rz-surface p-3.5 text-left"
            >
                <Box on={data.business_account} tone="green" />
                <span className="text-[13px] leading-[1.45] text-rz-ink">
                    {t('business.onboarding.bank.business_account_prefix')}{' '}
                    <b>{t('business.onboarding.bank.business_account')}</b>
                    {t('business.onboarding.bank.business_account_suffix')}
                </span>
            </button>

            <div className="mt-3.5 flex items-center justify-between">
                <label
                    htmlFor="account_name"
                    className="text-xs font-semibold text-rz-label uppercase"
                >
                    {t('business.onboarding.bank.account_name')}
                </label>
                <button
                    type="button"
                    onClick={() => form.setData('account_name', companyName)}
                    className="text-[11.5px] font-semibold text-rz-accent-app-text"
                >
                    {t('business.onboarding.bank.use_company_name')}
                </button>
            </div>
            <input
                id="account_name"
                value={data.account_name}
                onChange={(event) =>
                    form.setData('account_name', event.target.value)
                }
                placeholder={companyName}
                aria-invalid={
                    form.errors.account_name !== undefined || undefined
                }
                className="mt-1.5 w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
            />
            <FieldError id="account-name-error">
                {form.errors.account_name}
            </FieldError>

            <label
                htmlFor="account_number"
                className="mt-3.5 mb-1.5 block text-xs font-semibold text-rz-label uppercase"
            >
                {t('business.onboarding.bank.account_number')}
            </label>
            <input
                id="account_number"
                inputMode="numeric"
                maxLength={16}
                value={data.account_number}
                onChange={(event) =>
                    form.setData(
                        'account_number',
                        event.target.value.replace(/\D/gu, ''),
                    )
                }
                placeholder={t(
                    'business.onboarding.bank.account_number_placeholder',
                )}
                className="w-full rounded-xl border border-rz-field-border bg-rz-field px-3.5 py-[13px] text-sm tracking-[.05em] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border"
            />
            <FieldError id="account-number-error">
                {form.errors.account_number}
            </FieldError>

            <div className="mt-4 flex items-center justify-between">
                <p className="text-xs font-semibold text-rz-label uppercase">
                    {t('business.onboarding.bank.signatories')}
                </p>
                <span
                    className={cn(
                        'text-[11px] font-semibold',
                        data.signatories.length >= required
                            ? 'text-rz-accent-app-text'
                            : 'text-rz-ink',
                    )}
                >
                    {t('business.onboarding.bank.signatories_count', {
                        selected: data.signatories.length,
                        required,
                    })}
                </span>
            </div>
            <div className="mt-[9px] flex flex-col gap-[9px]">
                {signatories.map((person) => {
                    const on = data.signatories.includes(person.id);

                    return (
                        <button
                            key={person.id}
                            type="button"
                            role="checkbox"
                            aria-checked={on}
                            onClick={() =>
                                form.setData(
                                    'signatories',
                                    on
                                        ? data.signatories.filter(
                                              (id) => id !== person.id,
                                          )
                                        : [...data.signatories, person.id],
                                )
                            }
                            className="flex items-center gap-3 rounded-xl border border-rz-border bg-rz-surface p-[13px] text-left"
                        >
                            <Box on={on} tone="blue" />
                            <span className="flex-1">
                                <span className="block text-[13.5px] font-semibold text-rz-ink">
                                    {person.name}
                                </span>
                                <span className="block text-[11.5px] text-rz-secondary">
                                    {person.role}
                                </span>
                            </span>
                        </button>
                    );
                })}
            </div>
            <FieldError id="signatories-error">
                {form.errors.signatories}
            </FieldError>

            <button
                type="submit"
                disabled={!complete || form.processing}
                className={cn(
                    'mt-5 h-[52px] w-full rounded-2xl border-[1.5px] bg-rz-surface text-[15px] font-semibold',
                    complete
                        ? 'border-rz-accent-fill text-rz-accent-app-text'
                        : 'cursor-not-allowed border-rz-secondary text-rz-secondary',
                )}
            >
                {form.processing
                    ? t('business.onboarding.bank.linking')
                    : t('business.onboarding.bank.link')}
            </button>
        </form>
    );
}
