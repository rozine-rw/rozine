import { useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FieldError } from '@/components/rozine/form';
import { useToast } from '@/components/rozine/toast';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteAction } from '@/types';
import type { BusinessProfileProps, CompanyProfile } from '@/types/business';

const INPUT =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border disabled:text-rz-secondary';

function Field({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex-1">
            <label
                htmlFor={id}
                className="mb-1.5 block text-xs font-semibold text-rz-label uppercase"
            >
                {label}
            </label>
            {children}
            <FieldError id={`${id}-error`}>{error}</FieldError>
        </div>
    );
}

function Select({
    id,
    value,
    onChange,
    options,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
}) {
    return (
        <span className="relative block">
            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={`${INPUT} cursor-pointer appearance-none pr-[38px]`}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <svg
                viewBox="0 0 10 6"
                fill="none"
                aria-hidden
                className="pointer-events-none absolute top-1/2 right-3.5 h-1.5 w-2.5 -translate-y-1/2"
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
    );
}

type CompanyFormProps = {
    company: CompanyProfile;
    provinces: BusinessProfileProps['provinces'];
    action: RouteAction;
};

/**
 * "Company information" (design L1726–1743). The registered name comes from RDB and is shown
 * read-only; contact details and the address are the business's to keep current.
 */
export function CompanyForm({ company, provinces, action }: CompanyFormProps) {
    const { t } = useTranslation();
    const { toast, show } = useToast();
    const form = useForm({
        email: company.email,
        phone: company.phone,
        province: company.address.province,
        district: company.address.district,
        sector: company.address.sector,
        cell: company.address.cell,
        street: company.address.street,
    });
    const { data, errors } = form;
    const districts =
        provinces.find((province) => province.value === data.province)
            ?.districts ?? [];

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put(action.url, {
                    preserveScroll: true,
                    onSuccess: () => show(t('business.profile.company.saved')),
                });
            }}
        >
            <div className="mt-5 flex flex-col gap-3.5">
                <Field
                    id="company-name"
                    label={t('business.profile.company.name')}
                >
                    <input
                        id="company-name"
                        value={company.name}
                        disabled
                        className={INPUT}
                    />
                </Field>
                <Field
                    id="company-email"
                    label={t('business.profile.company.email')}
                    error={errors.email}
                >
                    <input
                        id="company-email"
                        type="email"
                        value={data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        aria-invalid={errors.email !== undefined || undefined}
                        className={INPUT}
                    />
                </Field>
                <Field
                    id="company-phone"
                    label={t('business.profile.company.phone')}
                    error={errors.phone}
                >
                    <input
                        id="company-phone"
                        inputMode="numeric"
                        maxLength={10}
                        value={data.phone}
                        onChange={(event) =>
                            form.setData(
                                'phone',
                                event.target.value.replace(/\D/gu, ''),
                            )
                        }
                        placeholder={t(
                            'business.profile.company.phone_placeholder',
                        )}
                        aria-invalid={errors.phone !== undefined || undefined}
                        className={INPUT}
                    />
                </Field>
                <p className="mt-1.5 text-xs font-semibold tracking-[.05em] text-rz-label uppercase">
                    {t('business.profile.company.address')}
                </p>
                <div className="flex gap-3">
                    <Field
                        id="company-province"
                        label={t('business.profile.company.province')}
                    >
                        <Select
                            id="company-province"
                            value={data.province}
                            onChange={(value) =>
                                form.setData((current) => ({
                                    ...current,
                                    province: value,
                                    district: '',
                                }))
                            }
                            options={provinces}
                        />
                    </Field>
                    <Field
                        id="company-district"
                        label={t('business.profile.company.district')}
                        error={errors.district}
                    >
                        <Select
                            id="company-district"
                            value={data.district}
                            onChange={(value) =>
                                form.setData('district', value)
                            }
                            options={[
                                {
                                    value: '',
                                    label: t('business.profile.company.choose'),
                                },
                                ...districts,
                            ]}
                        />
                    </Field>
                </div>
                <div className="flex gap-3">
                    <Field
                        id="company-sector"
                        label={t('business.profile.company.sector')}
                    >
                        <input
                            id="company-sector"
                            value={data.sector}
                            onChange={(event) =>
                                form.setData('sector', event.target.value)
                            }
                            placeholder={t(
                                'business.profile.company.sector_placeholder',
                            )}
                            className={INPUT}
                        />
                    </Field>
                    <Field
                        id="company-cell"
                        label={t('business.profile.company.cell')}
                    >
                        <input
                            id="company-cell"
                            value={data.cell}
                            onChange={(event) =>
                                form.setData('cell', event.target.value)
                            }
                            placeholder={t(
                                'business.profile.company.cell_placeholder',
                            )}
                            className={INPUT}
                        />
                    </Field>
                </div>
                <Field
                    id="company-street"
                    label={t('business.profile.company.street')}
                >
                    <input
                        id="company-street"
                        value={data.street}
                        onChange={(event) =>
                            form.setData('street', event.target.value)
                        }
                        className={INPUT}
                    />
                </Field>
            </div>
            <button
                type="submit"
                disabled={form.processing}
                className="mt-5 h-[50px] w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:opacity-80"
            >
                {form.processing
                    ? t('business.profile.company.saving')
                    : t('business.profile.company.save')}
            </button>
            {toast}
        </form>
    );
}
