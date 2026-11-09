import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EXPLAIN } from '@/components/admin/ui';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { BusinessOnboarded, OnboardBusinessAction } from '@/types/admin';

const KINDS = ['organization', 'person'] as const;

const FIELD =
    'w-full rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] py-[10px] text-[13px] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field';

type TextField =
    | 'rdb_code'
    | 'name'
    | 'established_year'
    | 'representative_email'
    | 'evidence_reference';

type ChoiceField = 'industry' | 'district';

/** The earliest year the server accepts for when a business was established. */
const EARLIEST_YEAR = 1900;

const blank = (effectiveAt: string) => ({
    request_id: crypto.randomUUID(),
    mandate_request_id: crypto.randomUUID(),
    kind: 'organization' as (typeof KINDS)[number],
    rdb_code: '',
    name: '',
    industry: '',
    district: '',
    established_year: '',
    representative_email: '',
    evidence_reference: '',
    reason: '',
    attested: false,
    effective_at: effectiveAt,
});

/**
 * "+ Onboard a business" (#375): a staffer who verifies Businesses records a company from its RDB code,
 * or a sole trader, with its profile and its official representative, who must already be a verified
 * person on Rozine. The representative gets every role and permission on the first mandate; more people
 * are added later through a new mandate version.
 */
export function OnboardBusiness({ action }: { action: OnboardBusinessAction }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm(blank(action.effective_at));
    const company = form.data.kind === 'organization';
    const required: (TextField | ChoiceField)[] = [
        ...(company ? (['rdb_code'] as const) : []),
        'name',
        'industry',
        'district',
        'representative_email',
        'evidence_reference',
    ];
    const year = form.data.established_year;
    // Said under the field before the server is asked, so Submit is never disabled without a reason.
    const hints: Partial<Record<TextField, string>> = {
        rdb_code:
            company &&
            form.data.rdb_code !== '' &&
            form.data.rdb_code.length !== 9
                ? t('admin.businesses.onboard.hint.rdb_code')
                : undefined,
        established_year:
            year !== '' &&
            (year.length !== 4 ||
                Number(year) < EARLIEST_YEAR ||
                Number(year) > action.latest_year)
                ? t('admin.businesses.onboard.hint.established_year', {
                      year: action.latest_year,
                  })
                : undefined,
    };
    const complete =
        required.every((key) => form.data[key].trim() !== '') &&
        Object.values(hints).every((hint) => hint === undefined) &&
        form.data.reason.trim() !== '' &&
        form.data.attested;

    const close = () => {
        form.reset();
        form.setData('request_id', crypto.randomUUID());
        form.setData('mandate_request_id', crypto.randomUUID());
        form.setData('effective_at', action.effective_at);
        setOpen(false);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            rdb_code: company ? data.rdb_code : null,
            established_year:
                data.established_year.trim() === ''
                    ? null
                    : data.established_year,
        }));
        form.post(action.url, { preserveScroll: true, onSuccess: close });
    };

    const input = (
        key: TextField,
        props: {
            type?: 'email';
            inputMode?: 'numeric';
            maxLength?: number;
            pattern?: string;
        } = {},
    ) => (
        <div>
            <label className="block">
                <span className="text-[11.5px] font-semibold text-rz-muted">
                    {t(`admin.businesses.onboard.field.${key}`)}
                </span>
                <input
                    {...props}
                    value={form.data[key]}
                    onChange={(event) =>
                        form.setData(
                            key,
                            props.inputMode === 'numeric'
                                ? event.target.value.replace(/\D/g, '')
                                : event.target.value,
                        )
                    }
                    placeholder={t(
                        `admin.businesses.onboard.placeholder.${key}`,
                    )}
                    aria-invalid={
                        (form.errors[key] ?? hints[key]) !== undefined ||
                        undefined
                    }
                    aria-describedby={`onboard-${key}-error`}
                    className={cn('mt-1', FIELD)}
                />
            </label>
            <FieldError id={`onboard-${key}-error`}>
                {form.errors[key] ?? hints[key]}
            </FieldError>
        </div>
    );

    const choice = (key: ChoiceField, options: Record<string, string[]>) => (
        <div>
            <label className="block">
                <span className="text-[11.5px] font-semibold text-rz-muted">
                    {t(`admin.businesses.onboard.field.${key}`)}
                </span>
                <select
                    value={form.data[key]}
                    onChange={(event) => form.setData(key, event.target.value)}
                    aria-invalid={form.errors[key] !== undefined || undefined}
                    aria-describedby={`onboard-${key}-error`}
                    className={cn('mt-1', FIELD)}
                >
                    <option value="" disabled>
                        {t(`admin.businesses.onboard.placeholder.${key}`)}
                    </option>
                    {Object.entries(options).map(([group, values]) => {
                        const items = values.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ));

                        return group === '' ? (
                            items
                        ) : (
                            <optgroup key={group} label={group}>
                                {items}
                            </optgroup>
                        );
                    })}
                </select>
            </label>
            <FieldError id={`onboard-${key}-error`}>
                {form.errors[key]}
            </FieldError>
        </div>
    );

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="h-[34px] shrink-0 rounded-[9px] bg-[#1e3aff] px-3.5 text-[12.5px] font-bold text-white"
            >
                {t('admin.businesses.onboard.open')}
            </button>
        );
    }

    return (
        <form
            onSubmit={submit}
            aria-label={t('admin.businesses.onboard.title')}
            className="mb-4 w-full rounded-[15px] border-[1.5px] border-[#c9d6ff] bg-rz-surface p-[18px] dark:border-rz-border"
        >
            <div className="text-[15px] font-bold text-rz-ink">
                {t('admin.businesses.onboard.title')}
            </div>
            <p className={cn('mt-[5px] text-[12.5px] leading-[1.55]', EXPLAIN)}>
                {t('admin.businesses.onboard.body')}
            </p>
            <fieldset className="mt-3" aria-describedby="onboard-kind-error">
                <legend className="text-[11.5px] font-semibold text-rz-muted">
                    {t('admin.businesses.onboard.field.kind')}
                </legend>
                <div className="mt-1 flex gap-2">
                    {KINDS.map((kind) => (
                        <label
                            key={kind}
                            className={cn(
                                'cursor-pointer rounded-[10px] border px-3.5 py-2 text-[12.5px] font-semibold',
                                form.data.kind === kind
                                    ? 'border-[#1e3aff] text-[#1e3aff]'
                                    : 'border-rz-hairline text-rz-body',
                            )}
                        >
                            <input
                                type="radio"
                                name="onboard-kind"
                                value={kind}
                                checked={form.data.kind === kind}
                                onChange={() => form.setData('kind', kind)}
                                className="sr-only"
                            />
                            {t(`admin.businesses.onboard.kind.${kind}`)}
                        </label>
                    ))}
                </div>
                <FieldError id="onboard-kind-error">
                    {form.errors.kind}
                </FieldError>
            </fieldset>
            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                {company &&
                    input('rdb_code', {
                        inputMode: 'numeric',
                        maxLength: 9,
                        pattern: '[0-9]{9}',
                    })}
                {input('name', { maxLength: 180 })}
                {choice('industry', { '': action.sectors })}
                {choice('district', action.districts)}
                {input('established_year', {
                    inputMode: 'numeric',
                    maxLength: 4,
                })}
                {input('representative_email', {
                    type: 'email',
                    maxLength: 255,
                })}
                {input('evidence_reference', { maxLength: 255 })}
            </div>
            <label className="mt-3 block">
                <span className="text-[11.5px] font-semibold text-rz-muted">
                    {t('admin.stage.reason_label')}
                </span>
                <textarea
                    value={form.data.reason}
                    maxLength={2000}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    aria-invalid={
                        (form.errors.reason ?? form.errors.effective_at) !==
                            undefined || undefined
                    }
                    aria-describedby="onboard-reason-error"
                    className={cn('mt-1 min-h-[72px] resize-y', FIELD)}
                />
            </label>
            <FieldError id="onboard-reason-error">
                {form.errors.reason ?? form.errors.effective_at}
            </FieldError>
            <label className="mt-3 flex items-start gap-2 text-[12.5px] text-rz-body">
                <input
                    type="checkbox"
                    checked={form.data.attested}
                    onChange={(event) =>
                        form.setData('attested', event.target.checked)
                    }
                    aria-describedby="onboard-attested-error onboard-attested-hint"
                    className="mt-0.5"
                />
                {t('admin.businesses.onboard.attest')}
            </label>
            <FieldError id="onboard-attested-error">
                {form.errors.attested}
            </FieldError>
            {!form.data.attested && form.errors.attested === undefined && (
                <p
                    id="onboard-attested-hint"
                    className="mt-1 text-[11.5px] text-rz-muted"
                >
                    {t('admin.businesses.onboard.hint.attest')}
                </p>
            )}
            <div className="mt-3.5 flex justify-end gap-2">
                <button
                    type="button"
                    onClick={close}
                    className="rounded-[10px] border border-rz-hairline px-3.5 py-2 text-[12.5px] font-semibold text-rz-body"
                >
                    {t('admin.stage.cancel')}
                </button>
                <button
                    type="submit"
                    disabled={!complete || form.processing}
                    aria-busy={form.processing || undefined}
                    className="rounded-[10px] bg-[#1e3aff] px-3.5 py-2 text-[12.5px] font-bold text-white disabled:opacity-50"
                >
                    {t('admin.businesses.onboard.submit')}
                </button>
            </div>
        </form>
    );
}

/**
 * Said once after onboarding: the business is recorded, and whether its representative can open the
 * Business app yet. Business access is granted by an identity operator, not by the mandate.
 */
export function OnboardedNotice({
    onboarded,
}: {
    onboarded: BusinessOnboarded;
}) {
    const { t } = useTranslation();

    return (
        <div
            role="status"
            className={cn(
                'mb-4 rounded-[13px] border px-4 py-3 text-[12.5px] leading-[1.55]',
                onboarded.business_access
                    ? 'border-[#bfe6d6] bg-[#effaf5] text-[#0f5c45]'
                    : 'border-[#f5d9a8] bg-[#fff8eb] text-[#7a4b00]',
            )}
        >
            <p className="font-bold">{t('admin.businesses.onboarded.title')}</p>
            <p className="mt-0.5">
                {t(
                    onboarded.business_access
                        ? 'admin.businesses.onboarded.ready'
                        : 'admin.businesses.onboarded.access_needed',
                    { name: onboarded.representative },
                )}
            </p>
        </div>
    );
}
