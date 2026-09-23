import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { ErrorBanner } from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    EntityType,
    FundsSource,
    IdDocument,
    InvestorVerificationProps,
    UploadState,
} from '@/types/investor';

const LABEL = 'mb-1.5 block text-xs font-semibold text-rz-label uppercase';
const FIELD =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border';

const PERSONAL_STEPS = ['personal', 'document', 'liveness'] as const;
const ENTITY_STEPS = ['entity', 'representative', 'declarations'] as const;

/**
 * A dashed design tile that is a file picker (design L3576, L3590, L3638). The file goes straight to
 * the server, which stores it and re-renders the page with the slot's new state.
 */
function Upload({
    slot,
    action,
    state,
    label,
    capture,
    className,
    children,
}: {
    slot: string;
    action: RouteAction;
    state: UploadState;
    label: string;
    capture?: 'user';
    className: string;
    children: ReactNode;
}) {
    return (
        <label
            className={cn(
                'cursor-pointer border-[1.5px] border-dashed',
                state.status === 'missing'
                    ? 'border-rz-border bg-[#f3f6fc] dark:bg-rz-surface-muted'
                    : 'border-[#17795a] bg-rz-page dark:border-[#3fcda0]',
                className,
            )}
        >
            <input
                type="file"
                accept="image/*,application/pdf"
                capture={capture}
                aria-label={label}
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        router.post(
                            action.url,
                            { slot, file },
                            { forceFormData: true, preserveScroll: true },
                        );
                    }
                }}
            />
            {children}
        </label>
    );
}

function Chips<T extends string>({
    label,
    options,
    value,
    onChange,
    render,
    stretch = false,
}: {
    label: string;
    options: readonly T[];
    value: T | null;
    onChange: (value: T) => void;
    render: (value: T) => string;
    stretch?: boolean;
}) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className={cn(
                'flex gap-2',
                stretch ? '' : 'rz-hscroll overflow-x-auto',
            )}
        >
            {options.map((option) => (
                <button
                    key={option}
                    type="button"
                    role="radio"
                    aria-checked={value === option}
                    onClick={() => onChange(option)}
                    className={cn(
                        'rounded-[10px] border font-semibold whitespace-nowrap',
                        stretch
                            ? 'flex-1 py-[9px] text-xs'
                            : 'shrink-0 px-3.5 py-[9px] text-[13px]',
                        value === option
                            ? 'border-[#d6e4ff] bg-rz-accent-fill text-white dark:border-rz-investor'
                            : 'border-rz-border bg-rz-surface text-rz-slate',
                    )}
                >
                    {render(option)}
                </button>
            ))}
        </div>
    );
}

function Check({
    checked,
    onChange,
    children,
}: {
    checked: boolean;
    onChange: (checked: boolean) => void;
    children: ReactNode;
}) {
    return (
        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-rz-border bg-rz-surface p-[15px]">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="peer sr-only"
            />
            <span
                aria-hidden
                className="mt-px flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-2 border-rz-border bg-rz-surface text-[13px] text-white peer-checked:border-[#17795a] peer-checked:bg-[#17795a] peer-focus-visible:ring-2 peer-focus-visible:ring-rz-focus-border"
            >
                {checked ? '✓' : ''}
            </span>
            <span className="text-[12.5px] leading-normal text-rz-slate">
                {children}
            </span>
        </label>
    );
}

/**
 * Investor verification (design L3553–3656) — the MVP "investor verification" screens. Individuals
 * give their date of birth, an identity document (front and back) and a liveness selfie; institutions
 * give the entity (RDB company code — the design's TIN field is removed, BRS AC-9), the authorised
 * representative with a board resolution, and the source of funds and declarations. Unverified
 * investors have no transaction capacity (CFG-01), so this gates buying.
 */
export default function InvestorVerification(props: InvestorVerificationProps) {
    const { t } = useTranslation();
    const steps: readonly string[] =
        props.investor_type === 'individual' ? PERSONAL_STEPS : ENTITY_STEPS;
    const index = steps.indexOf(props.step);
    const last = index === steps.length - 1;
    const percent = Math.round((index / 3) * 100);
    const form = useForm<Record<string, string | boolean | null>>(
        props.investor_type === 'individual'
            ? {
                  date_of_birth: props.date_of_birth,
                  id_type: props.id_type,
                  id_number: props.id_number,
              }
            : {
                  entity_type: props.entity_type,
                  company_code: props.company_code,
                  incorporated: props.incorporated,
                  representative_name: props.representative.name,
                  representative_role: props.representative.role,
                  representative_id: props.representative.id_number,
                  funds_source: props.funds_source,
                  annual_commitment: props.annual_commitment,
                  aml: false,
                  target: false,
              },
    );
    const data = form.data;
    const set = (key: string, value: string | boolean) =>
        form.setData(key, value);
    const failure = Object.values(form.errors)[0];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((values) => ({ ...values, step: props.step }));
        form.post(last ? props.actions.submit.url : props.actions.save.url, {
            preserveScroll: true,
        });
    };

    const text = (
        key: string,
        label: string,
        placeholder: string,
        inputMode?: 'numeric',
    ) => (
        <div className="min-w-0 flex-1">
            <label htmlFor={key} className={LABEL}>
                {label}
            </label>
            <input
                id={key}
                value={String(data[key])}
                onChange={(event) => set(key, event.target.value)}
                inputMode={inputMode}
                placeholder={placeholder}
                className={FIELD}
            />
        </div>
    );

    return (
        <InvestorAuthFrame
            title={t(`investor.kyc.${props.step}.title`)}
            layout="column"
        >
            <form
                onSubmit={submit}
                className="px-5 pt-[calc(env(safe-area-inset-top)+14px)] pb-10 lg:pt-14"
            >
                <div className="flex items-center gap-3">
                    <Link
                        href={props.links.back}
                        aria-label={t('investor.common.back')}
                        className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <div
                        role="progressbar"
                        aria-label={t('investor.kyc.progress')}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent}
                        className="h-1.5 flex-1 overflow-hidden rounded-[4px] bg-rz-border"
                    >
                        <div
                            className="h-full bg-[linear-gradient(90deg,#1e3aff,#17795a)] transition-[width] duration-400"
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                    <span
                        aria-hidden
                        className="text-[12.5px] font-semibold text-rz-secondary"
                    >
                        {percent}%
                    </span>
                </div>
                <p className="mt-2 text-[12.5px] font-semibold tracking-[.05em] text-rz-accent-app-text uppercase">
                    {t(
                        props.investor_type === 'individual'
                            ? 'investor.kyc.kicker_identity'
                            : 'investor.kyc.kicker_entity',
                    )}
                </p>
                <h1 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                    {t(`investor.kyc.${props.step}.title`)}
                </h1>
                <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                    {t(`investor.kyc.${props.step}.subtitle`)}
                </p>

                {props.investor_type === 'individual' &&
                    props.step === 'personal' && (
                        <div className="mt-5 flex flex-col gap-[13px]">
                            <div>
                                <p className={LABEL}>
                                    {t('investor.kyc.country')}
                                </p>
                                <p className="rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink">
                                    {props.country}
                                </p>
                            </div>
                            {text(
                                'date_of_birth',
                                t('investor.kyc.dob'),
                                t('investor.kyc.dob_placeholder'),
                            )}
                        </div>
                    )}

                {props.investor_type === 'individual' &&
                    props.step === 'document' && (
                        <>
                            <div className="mt-4">
                                <Chips<IdDocument>
                                    label={t(
                                        'investor.signup.identity.id_type',
                                    )}
                                    options={[
                                        'national_id',
                                        'passport',
                                        'drivers_license',
                                    ]}
                                    value={data.id_type as IdDocument}
                                    onChange={(value) => set('id_type', value)}
                                    render={(value) =>
                                        t(`investor.signup.id.${value}`)
                                    }
                                    stretch
                                />
                            </div>
                            <div className="mt-3.5">
                                {text(
                                    'id_number',
                                    t('investor.kyc.id_number'),
                                    '1 1990 8 0012345 6 78',
                                )}
                            </div>
                            <div className="mt-3.5 flex gap-3">
                                {(['front', 'back'] as const).map((side) => {
                                    const state = props.uploads[side];

                                    return (
                                        <Upload
                                            key={side}
                                            slot={`id_${side}`}
                                            action={props.actions.upload}
                                            state={state}
                                            label={t(
                                                `investor.kyc.upload_${side}`,
                                            )}
                                            className="flex-1 rounded-2xl px-2.5 py-[22px] text-center"
                                        >
                                            <span className="text-[22px]">
                                                <Icon
                                                    name={
                                                        state.status ===
                                                        'missing'
                                                            ? 'camera'
                                                            : 'check-badge'
                                                    }
                                                    tone="green"
                                                />
                                            </span>
                                            <span className="mt-1.5 block text-xs font-semibold text-rz-slate">
                                                {t(
                                                    state.status === 'missing'
                                                        ? `investor.kyc.upload_${side}`
                                                        : `investor.kyc.uploaded_${side}`,
                                                )}
                                            </span>
                                        </Upload>
                                    );
                                })}
                            </div>
                        </>
                    )}

                {props.investor_type === 'individual' &&
                    props.step === 'liveness' && (
                        <Upload
                            slot="selfie"
                            action={props.actions.upload}
                            state={props.uploads.selfie}
                            label={t('investor.kyc.selfie')}
                            capture="user"
                            className="mx-auto mt-[22px] flex size-[200px] flex-col items-center justify-center rounded-full border-2"
                        >
                            <span className="text-[33px]">
                                <Icon
                                    name={
                                        props.uploads.selfie.status ===
                                        'missing'
                                            ? 'camera'
                                            : 'check-badge'
                                    }
                                />
                            </span>
                            <span className="mt-2.5 max-w-[140px] text-center text-[13px] font-semibold text-rz-slate">
                                {t(
                                    props.uploads.selfie.status === 'missing'
                                        ? 'investor.kyc.selfie'
                                        : 'investor.kyc.selfie_done',
                                )}
                            </span>
                        </Upload>
                    )}

                {props.investor_type === 'institution' &&
                    props.step === 'entity' && (
                        <div className="mt-5 flex flex-col gap-[13px]">
                            <div>
                                <p className={LABEL}>
                                    {t('investor.kyc.entity_type')}
                                </p>
                                <Chips<EntityType>
                                    label={t('investor.kyc.entity_type')}
                                    options={[
                                        'fund',
                                        'sacco',
                                        'treasury',
                                        'insurer',
                                        'pension',
                                        'other',
                                    ]}
                                    value={
                                        data.entity_type as EntityType | null
                                    }
                                    onChange={(value) =>
                                        set('entity_type', value)
                                    }
                                    render={(value) =>
                                        t(`investor.kyc.entity.${value}`)
                                    }
                                />
                            </div>
                            {text(
                                'company_code',
                                t('investor.kyc.company_code'),
                                '103847291',
                                'numeric',
                            )}
                            {text(
                                'incorporated',
                                t('investor.kyc.incorporated'),
                                t('investor.kyc.incorporated_placeholder'),
                            )}
                            <Upload
                                slot="certificate"
                                action={props.actions.upload}
                                state={props.uploads.certificate}
                                label={t('investor.kyc.certificate')}
                                className="rounded-2xl px-3.5 py-5 text-center"
                            >
                                <span className="text-[22px]">
                                    <Icon
                                        name={
                                            props.uploads.certificate.status ===
                                            'missing'
                                                ? 'document'
                                                : 'check-badge'
                                        }
                                    />
                                </span>
                                <span className="mt-1.5 block text-[12.5px] font-semibold text-rz-slate">
                                    {t(
                                        props.uploads.certificate.status ===
                                            'missing'
                                            ? 'investor.kyc.certificate'
                                            : 'investor.kyc.certificate_done',
                                    )}
                                </span>
                                <span className="mt-0.5 block text-[11px] text-rz-secondary">
                                    {t('investor.kyc.certificate_hint')}
                                </span>
                            </Upload>
                        </div>
                    )}

                {props.investor_type === 'institution' &&
                    props.step === 'representative' && (
                        <div className="mt-5 flex flex-col gap-[13px]">
                            <div className="flex gap-3">
                                {text(
                                    'representative_name',
                                    t('investor.kyc.rep_name'),
                                    t(
                                        'investor.auth.representative_placeholder',
                                    ),
                                )}
                                {text(
                                    'representative_role',
                                    t('investor.kyc.rep_role'),
                                    t('investor.kyc.rep_role_placeholder'),
                                )}
                            </div>
                            {text(
                                'representative_id',
                                t('investor.kyc.rep_id'),
                                '1 1990 8 0012345 6 78',
                            )}
                            <Upload
                                slot="resolution"
                                action={props.actions.upload}
                                state={props.uploads.resolution}
                                label={t('investor.kyc.resolution')}
                                className="rounded-2xl px-3.5 py-5 text-center"
                            >
                                <span className="text-[22px]">
                                    <Icon
                                        name={
                                            props.uploads.resolution.status ===
                                            'missing'
                                                ? 'document'
                                                : 'check-badge'
                                        }
                                    />
                                </span>
                                <span className="mt-1.5 block text-[12.5px] font-semibold text-rz-slate">
                                    {t(
                                        props.uploads.resolution.status ===
                                            'missing'
                                            ? 'investor.kyc.resolution'
                                            : 'investor.kyc.resolution_done',
                                    )}
                                </span>
                                <span className="mt-0.5 block text-[11px] text-rz-secondary">
                                    {t('investor.kyc.resolution_hint')}
                                </span>
                            </Upload>
                            <Upload
                                slot="selfie"
                                action={props.actions.upload}
                                state={props.uploads.selfie}
                                label={t('investor.kyc.selfie')}
                                capture="user"
                                className="flex items-center gap-[13px] rounded-2xl p-[15px]"
                            >
                                <span className="flex size-[42px] shrink-0 items-center justify-center rounded-full bg-rz-surface text-[19px]">
                                    <Icon
                                        name={
                                            props.uploads.selfie.status ===
                                            'missing'
                                                ? 'camera'
                                                : 'check-badge'
                                        }
                                    />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block text-[13px] font-semibold text-rz-slate">
                                        {t(
                                            props.uploads.selfie.status ===
                                                'missing'
                                                ? 'investor.kyc.selfie'
                                                : 'investor.kyc.selfie_done',
                                        )}
                                    </span>
                                    <span className="mt-0.5 block text-[11px] text-rz-secondary">
                                        {t('investor.kyc.rep_liveness')}
                                    </span>
                                </span>
                            </Upload>
                        </div>
                    )}

                {props.investor_type === 'institution' &&
                    props.step === 'declarations' && (
                        <div className="mt-5 flex flex-col gap-[13px]">
                            <div>
                                <p className={LABEL}>
                                    {t('investor.kyc.source')}
                                </p>
                                <Chips<FundsSource>
                                    label={t('investor.kyc.source')}
                                    options={[
                                        'operations',
                                        'member_savings',
                                        'investment_returns',
                                        'premiums',
                                        'contributions',
                                        'other',
                                    ]}
                                    value={
                                        data.funds_source as FundsSource | null
                                    }
                                    onChange={(value) =>
                                        set('funds_source', value)
                                    }
                                    render={(value) =>
                                        t(`investor.kyc.funds.${value}`)
                                    }
                                />
                            </div>
                            <div>
                                {text(
                                    'annual_commitment',
                                    t('investor.kyc.commitment'),
                                    '500,000,000',
                                    'numeric',
                                )}
                                <p className="mt-1.5 text-[11.5px] text-rz-secondary">
                                    {t('investor.kyc.commitment_hint')}
                                </p>
                            </div>
                            <Check
                                checked={data.aml === true}
                                onChange={(checked) => set('aml', checked)}
                            >
                                {t('investor.kyc.aml')}
                            </Check>
                            <Check
                                checked={data.target === true}
                                onChange={(checked) => set('target', checked)}
                            >
                                {t('investor.kyc.target')}
                            </Check>
                        </div>
                    )}

                {failure !== undefined && (
                    <div className="mt-4">
                        <ErrorBanner>{failure}</ErrorBanner>
                    </div>
                )}
                {form.processing && last && (
                    <p
                        role="status"
                        className="mt-[22px] flex items-center justify-center gap-2.5 text-[13px] font-semibold text-rz-secondary"
                    >
                        <span className="size-[18px] animate-spin rounded-full border-2 border-rz-accent-fill border-t-transparent" />
                        {t(
                            props.investor_type === 'individual'
                                ? 'investor.kyc.verifying'
                                : 'investor.kyc.verifying_entity',
                        )}
                    </p>
                )}
                <button
                    type="submit"
                    disabled={form.processing}
                    aria-busy={form.processing || undefined}
                    className="mt-6 h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-70"
                >
                    {t(last ? 'investor.kyc.submit' : 'investor.auth.continue')}
                </button>
            </form>
        </InvestorAuthFrame>
    );
}
