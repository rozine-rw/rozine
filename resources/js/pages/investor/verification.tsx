import { Link, router, useForm } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useState } from 'react';
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
    KycSubmission,
    UploadState,
} from '@/types/investor';

const LABEL = 'mb-1.5 block text-xs font-semibold text-rz-label uppercase';
const FIELD =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border';

type Step = InvestorVerificationProps['step'];

const PERSONAL_STEPS = ['personal', 'document', 'liveness'] as const;
const ENTITY_STEPS = ['entity', 'representative', 'declarations'] as const;

/**
 * A dashed design tile that is a file picker (design L3576, L3590, L3638). The file goes straight to
 * the server, which stores it and re-renders the page with the slot's new state.
 */
/**
 * A live submission's commands carry a fresh `request_id`, the identity context and the revision
 * they were drawn from; a preview fixture has no submission and sends none.
 */
function envelope(submission: Partial<KycSubmission>) {
    return submission.revision === undefined ||
        submission.identity_context_revision === undefined
        ? {}
        : {
              request_id: crypto.randomUUID(),
              identity_context_revision: submission.identity_context_revision,
              expected_revision: submission.revision,
          };
}

function Upload({
    slot,
    action,
    submission,
    state,
    label,
    capture,
    className,
    onError,
    children,
}: {
    slot: string;
    action: RouteAction;
    submission: Partial<KycSubmission>;
    state: UploadState;
    label: string;
    capture?: 'user';
    className: string;
    /** The upload is its own visit, so its validation errors never reach the step form. */
    onError: (message: string | null) => void;
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
                disabled={submission.status === 'submitted'}
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (file) {
                        router.post(
                            action.url,
                            { slot, file, ...envelope(submission) },
                            {
                                forceFormData: true,
                                preserveScroll: true,
                                onError: (errors: Record<string, string>) =>
                                    onError(Object.values(errors).join(' ')),
                                onSuccess: () => onError(null),
                            },
                        );
                    }
                }}
            />
            {children}
        </label>
    );
}

/**
 * The server reads and keeps a date of birth day first (DD / MM / YYYY); the date control speaks
 * YYYY-MM-DD. These two convert between them.
 */
function isoDate(value: string): string {
    const parts =
        /^\s*(\d{1,2})\s*[/.-]\s*(\d{1,2})\s*[/.-]\s*(\d{4})\s*$/u.exec(value);

    return parts === null
        ? ''
        : `${parts[3]}-${parts[2].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
}

function dayFirst(iso: string): string {
    const [year, month, day] = iso.split('-');

    return iso === '' ? '' : `${day} / ${month} / ${year}`;
}

/** The latest birthday that is at least 18 years before today, as YYYY-MM-DD. */
function adultBy(today: Date): string {
    const year = String(today.getFullYear() - 18).padStart(4, '0');
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const EARLIEST_BIRTH = '1900-01-01';

/**
 * The same refusals the server makes, shown as soon as a date is picked: an impossible or
 * pre-1900 date, or one less than 18 years ago. The server stays the authority.
 */
function birthDateProblem(
    iso: string,
    latest: string,
): 'dob_invalid' | 'dob_minor' | null {
    if (iso === '') {
        return null;
    }

    const [year, month, day] = iso.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day));

    if (
        date.getUTCFullYear() !== year ||
        date.getUTCMonth() !== month - 1 ||
        date.getUTCDate() !== day ||
        iso < EARLIEST_BIRTH
    ) {
        return 'dob_invalid';
    }

    return iso > latest ? 'dob_minor' : null;
}

/** The server's number rules (16 digits on a national ID, 6 to 12 letters or digits on a passport). */
const ID_NUMBER: Record<'national_id' | 'passport', RegExp> = {
    national_id: /^[0-9]{16}$/u,
    passport: /^[A-Z0-9]{6,12}$/u,
};

/**
 * What the person sent for one slot, shown back to them: the image itself, or the file name for
 * a PDF. The file comes from their own no-store route, never a public URL.
 */
function Preview({
    state,
    round = false,
}: {
    state: UploadState;
    round?: boolean;
}) {
    if (state.link === undefined) {
        return null;
    }

    return state.media_type?.startsWith('image/') ? (
        <img
            src={state.link.url}
            alt=""
            className={
                round
                    ? 'absolute inset-0 size-full rounded-full object-cover opacity-90'
                    : 'mx-auto mb-1.5 h-20 w-full rounded-lg object-cover'
            }
        />
    ) : (
        <span className="mx-auto mb-1.5 flex max-w-full items-center justify-center gap-1.5 text-rz-slate">
            <FileText aria-hidden="true" className="size-5 shrink-0" />
            <span className="truncate text-[11.5px]">{state.filename}</span>
        </span>
    );
}

/** Opens what was sent for one slot in a new tab, outside the tile so it never opens the picker. */
function ViewUpload({ state, label }: { state: UploadState; label: string }) {
    const { t } = useTranslation();

    return state.link === undefined ? null : (
        <a
            href={state.link.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-1.5 block text-center text-[12px] font-semibold text-rz-accent-app-text underline-offset-2 hover:underline"
        >
            {t('investor.kyc.view_upload', { slot: label })}
        </a>
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
 * The document type picked before the document step is saved. It lives on this device only, so a
 * reload after uploading a passport page keeps Passport instead of falling back to National ID.
 */
const ID_TYPE_DRAFT = 'rozine.kyc.id_type';

const readIdTypeDraft = (): string | null => {
    try {
        const stored = window.localStorage.getItem(ID_TYPE_DRAFT);

        return stored === 'passport' || stored === 'national_id'
            ? stored
            : null;
    } catch {
        return null;
    }
};

const writeIdTypeDraft = (value: string | null): void => {
    try {
        if (value === null) {
            window.localStorage.removeItem(ID_TYPE_DRAFT);
        } else {
            window.localStorage.setItem(ID_TYPE_DRAFT, value);
        }
    } catch {
        /* Storage can be blocked; the choice then lasts for this visit only. */
    }
};

/**
 * Investor verification (design L3553–3656) — the MVP "investor verification" screens. Individuals
 * give their date of birth, an identity document (front and back) and a liveness selfie; institutions
 * give the entity (RDB company code — the design's TIN field is removed, BRS AC-9), the authorised
 * representative with a board resolution, and the source of funds and declarations. Unverified
 * investors have no transaction capacity (CFG-01), so this gates buying.
 */
export default function InvestorVerification(props: InvestorVerificationProps) {
    const { t } = useTranslation();
    // A participant can step back to correct an earlier answer (for example after a rejection); the
    // server's step returns once that earlier step is saved.
    const [viewing, setViewing] = useState<Step | null>(null);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [idNumberTouched, setIdNumberTouched] = useState(false);
    const [latestBirth] = useState(() => adultBy(new Date()));
    const steps: readonly Step[] =
        props.investor_type === 'individual' ? PERSONAL_STEPS : ENTITY_STEPS;
    const step: Step = viewing ?? props.step;
    const index = steps.indexOf(step);
    const last = index === steps.length - 1;
    const submitted =
        props.investor_type === 'individual' && props.status === 'submitted';
    // Sent for review, every step is done: the bar is full and nothing is left to submit.
    const percent = submitted ? 100 : Math.round((index / 3) * 100);
    const heading: `investor.kyc.${Step | 'review'}` = submitted
        ? 'investor.kyc.review'
        : `investor.kyc.${step}`;
    const form = useForm<Record<string, string | boolean | null>>(
        props.investor_type === 'individual'
            ? {
                  date_of_birth: props.date_of_birth,
                  id_type:
                      props.id_number === ''
                          ? (readIdTypeDraft() ?? props.id_type)
                          : props.id_type,
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
    const submission: Partial<KycSubmission> =
        props.investor_type === 'individual' ? props : {};
    // An upload error from the last Continue stops showing once the files it asked for are in.
    const answered = (field: string) =>
        props.investor_type === 'individual' &&
        ((field === 'id_front' &&
            props.uploads.front.status !== 'missing' &&
            (data.id_type === 'passport' ||
                props.uploads.back.status !== 'missing')) ||
            (field === 'selfie' && props.uploads.selfie.status !== 'missing'));
    const failure =
        uploadError ??
        Object.entries(form.errors).find(([field]) => !answered(field))?.[1];
    const locked = submitted;
    const birth = isoDate(String(data.date_of_birth ?? ''));
    const birthProblem =
        props.investor_type === 'individual'
            ? birthDateProblem(birth, latestBirth)
            : null;
    const idType: 'national_id' | 'passport' =
        data.id_type === 'passport' ? 'passport' : 'national_id';
    const idNumber = String(data.id_number ?? '')
        .replace(/\s+/gu, '')
        .toUpperCase();
    const idNumberProblem =
        idNumberTouched && idNumber !== '' && !ID_NUMBER[idType].test(idNumber);
    // A passport has one photo page; a national ID has a front and a back.
    const sides =
        idType === 'passport'
            ? (['front'] as const)
            : (['front', 'back'] as const);
    const sideLabel = (side: 'front' | 'back', uploaded: boolean) =>
        t(
            idType === 'passport'
                ? `investor.kyc.${uploaded ? 'uploaded' : 'upload'}_passport`
                : `investor.kyc.${uploaded ? 'uploaded' : 'upload'}_${side}`,
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (step === 'personal' && birthProblem !== null) {
            return;
        }

        form.transform((values) => ({
            ...values,
            step,
            ...envelope(submission),
        }));
        form.post(last ? props.actions.submit.url : props.actions.save.url, {
            preserveScroll: true,
            onSuccess: () => {
                if (step === 'document') {
                    writeIdTypeDraft(null);
                }

                setViewing(null);
            },
        });
    };

    const text = (
        key: string,
        label: string,
        placeholder: string,
        inputMode?: 'numeric',
        format: (value: string) => string = (value) => value,
    ) => (
        <div className="min-w-0 flex-1">
            <label htmlFor={key} className={LABEL}>
                {label}
            </label>
            <input
                id={key}
                value={String(data[key])}
                onChange={(event) => set(key, format(event.target.value))}
                inputMode={inputMode}
                placeholder={placeholder}
                className={FIELD}
            />
        </div>
    );

    return (
        <InvestorAuthFrame title={t(`${heading}.title`)} layout="column">
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
                    {t(`${heading}.title`)}
                </h1>
                <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                    {t(`${heading}.subtitle`)}
                </p>

                {props.investor_type === 'individual' &&
                    step === 'personal' && (
                        <div className="mt-5 flex flex-col gap-[13px]">
                            <div>
                                <p className={LABEL}>
                                    {t('investor.kyc.country')}
                                </p>
                                <p className="rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink">
                                    {props.country}
                                </p>
                            </div>
                            <div>
                                <label
                                    htmlFor="date_of_birth"
                                    className={LABEL}
                                >
                                    {t('investor.kyc.dob')}
                                </label>
                                <input
                                    id="date_of_birth"
                                    type="date"
                                    min={EARLIEST_BIRTH}
                                    max={latestBirth}
                                    value={birth}
                                    onChange={(event) =>
                                        set(
                                            'date_of_birth',
                                            dayFirst(event.target.value),
                                        )
                                    }
                                    aria-invalid={birthProblem !== null}
                                    aria-describedby="date_of_birth_hint"
                                    className={FIELD}
                                />
                                <p
                                    id="date_of_birth_hint"
                                    role={
                                        birthProblem === null
                                            ? undefined
                                            : 'alert'
                                    }
                                    className={cn(
                                        'mt-1.5 text-[11.5px]',
                                        birthProblem === null
                                            ? 'text-rz-secondary'
                                            : 'text-rz-danger-text',
                                    )}
                                >
                                    {t(
                                        `investor.kyc.${birthProblem ?? 'dob_hint'}`,
                                    )}
                                </p>
                            </div>
                        </div>
                    )}

                {props.investor_type === 'individual' &&
                    step === 'document' && (
                        <>
                            <div className="mt-4">
                                <Chips<IdDocument>
                                    label={t(
                                        'investor.signup.identity.id_type',
                                    )}
                                    options={['national_id', 'passport']}
                                    value={data.id_type as IdDocument}
                                    onChange={(value) => {
                                        set('id_type', value);
                                        writeIdTypeDraft(value);
                                    }}
                                    render={(value) =>
                                        t(`investor.signup.id.${value}`)
                                    }
                                    stretch
                                />
                            </div>
                            <div className="mt-3.5">
                                <label htmlFor="id_number" className={LABEL}>
                                    {t('investor.kyc.id_number')}
                                </label>
                                <input
                                    id="id_number"
                                    value={String(data.id_number ?? '')}
                                    onChange={(event) =>
                                        set('id_number', event.target.value)
                                    }
                                    onBlur={() => setIdNumberTouched(true)}
                                    inputMode={
                                        idType === 'national_id'
                                            ? 'numeric'
                                            : 'text'
                                    }
                                    autoCapitalize="characters"
                                    placeholder={
                                        idType === 'national_id'
                                            ? '1 1990 8 0012345 6 78'
                                            : 'PC1234567'
                                    }
                                    aria-invalid={idNumberProblem}
                                    aria-describedby="id_number_hint"
                                    className={FIELD}
                                />
                                <p
                                    id="id_number_hint"
                                    role={idNumberProblem ? 'alert' : undefined}
                                    className={cn(
                                        'mt-1.5 text-[11.5px]',
                                        idNumberProblem
                                            ? 'text-rz-danger-text'
                                            : 'text-rz-secondary',
                                    )}
                                >
                                    {t(
                                        idNumberProblem
                                            ? `investor.kyc.id_invalid.${idType}`
                                            : `investor.kyc.id_hint.${idType}`,
                                    )}
                                </p>
                            </div>
                            <div className="mt-3.5 flex gap-3">
                                {sides.map((side) => {
                                    const state = props.uploads[side];
                                    const uploaded = state.status !== 'missing';

                                    return (
                                        <div
                                            key={side}
                                            className="min-w-0 flex-1"
                                        >
                                            <Upload
                                                onError={setUploadError}
                                                slot={`id_${side}`}
                                                action={props.actions.upload}
                                                submission={submission}
                                                state={state}
                                                label={sideLabel(side, false)}
                                                className="block rounded-2xl px-2.5 py-[22px] text-center"
                                            >
                                                {state.link === undefined ? (
                                                    <span className="text-[22px]">
                                                        <Icon
                                                            name={
                                                                uploaded
                                                                    ? 'check-badge'
                                                                    : 'camera'
                                                            }
                                                            tone="green"
                                                        />
                                                    </span>
                                                ) : (
                                                    <Preview state={state} />
                                                )}
                                                <span className="mt-1.5 block text-xs font-semibold text-rz-slate">
                                                    {sideLabel(side, uploaded)}
                                                </span>
                                                {uploaded && !locked && (
                                                    <span className="mt-0.5 block text-[11px] text-rz-secondary">
                                                        {t(
                                                            'investor.kyc.replace',
                                                        )}
                                                    </span>
                                                )}
                                            </Upload>
                                            <ViewUpload
                                                state={state}
                                                label={t(
                                                    `investor.kyc.slot.${idType === 'passport' ? 'passport' : side}`,
                                                )}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        </>
                    )}

                {props.investor_type === 'individual' &&
                    step === 'liveness' && (
                        <Upload
                            onError={setUploadError}
                            slot="selfie"
                            action={props.actions.upload}
                            submission={submission}
                            state={props.uploads.selfie}
                            label={t('investor.kyc.selfie')}
                            capture="user"
                            className="relative mx-auto mt-[22px] flex size-[200px] flex-col items-center justify-center overflow-hidden rounded-full border-2"
                        >
                            <Preview state={props.uploads.selfie} round />
                            <span className="relative text-[33px]">
                                <Icon
                                    name={
                                        props.uploads.selfie.status ===
                                        'missing'
                                            ? 'camera'
                                            : 'check-badge'
                                    }
                                />
                            </span>
                            <span className="relative mt-2.5 max-w-[140px] rounded-md bg-rz-page/80 px-1.5 text-center text-[13px] font-semibold text-rz-slate">
                                {t(
                                    props.uploads.selfie.status === 'missing'
                                        ? 'investor.kyc.selfie'
                                        : 'investor.kyc.selfie_done',
                                )}
                            </span>
                        </Upload>
                    )}
                {props.investor_type === 'individual' &&
                    step === 'liveness' && (
                        <ViewUpload
                            state={props.uploads.selfie}
                            label={t('investor.kyc.selfie_slot')}
                        />
                    )}

                {props.investor_type === 'institution' && step === 'entity' && (
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
                                value={data.entity_type as EntityType | null}
                                onChange={(value) => set('entity_type', value)}
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
                            onError={setUploadError}
                            slot="certificate"
                            action={props.actions.upload}
                            submission={submission}
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
                    step === 'representative' && (
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
                                onError={setUploadError}
                                slot="resolution"
                                action={props.actions.upload}
                                submission={submission}
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
                                onError={setUploadError}
                                slot="selfie"
                                action={props.actions.upload}
                                submission={submission}
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
                    step === 'declarations' && (
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

                {locked && (
                    <p
                        role="note"
                        className="mt-4 rounded-xl border border-rz-border bg-rz-surface p-[15px] text-[13px] text-rz-slate"
                    >
                        {t('investor.kyc.submitted_notice')}
                    </p>
                )}
                {submission.status === 'rejected' &&
                    submission.decision_reason && (
                        <div className="mt-4">
                            <ErrorBanner>
                                {t('investor.kyc.rejected_notice', {
                                    reason: submission.decision_reason,
                                })}
                            </ErrorBanner>
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
                {!locked && (
                    <button
                        type="submit"
                        disabled={form.processing}
                        aria-busy={form.processing || undefined}
                        className="mt-6 h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-70"
                    >
                        {t(
                            last
                                ? 'investor.kyc.submit'
                                : 'investor.auth.continue',
                        )}
                    </button>
                )}
                {index > 0 && !locked && (
                    <button
                        type="button"
                        onClick={() => setViewing(steps[index - 1])}
                        className="mt-3 h-[46px] w-full rounded-2xl border border-rz-border bg-rz-surface text-[14px] font-semibold text-rz-ink"
                    >
                        {t('investor.kyc.previous')}
                    </button>
                )}
            </form>
        </InvestorAuthFrame>
    );
}
