import type { FormDataConvertible } from '@inertiajs/core';
import { Link, router, usePage } from '@inertiajs/react';
import { createContext, use, useState } from 'react';
import type { ReactNode } from 'react';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { RailNote, StepRail } from '@/components/investor/auth/step-rail';
import { ErrorBanner } from '@/components/rozine/form';
import { UploadStatus } from '@/components/rozine/upload-status';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { answerAt, flagKey } from '@/lib/role-application-answers';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { verification as investorVerification } from '@/routes/investor';
import {
    document as ownDocument,
    save,
    show,
    submit,
    upload,
} from '@/routes/role-application';
import { edit as security } from '@/routes/security';
import type {
    RoleApplicationAddress,
    RoleApplicationAnswers,
    RoleApplicationPerson,
    RoleApplicationProps,
    SwitchRole,
} from '@/types/role-application';

const LABEL = 'mb-1.5 block text-xs font-semibold text-rz-label uppercase';
const FIELD =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border';
const CARD = 'rounded-2xl border border-rz-border bg-rz-surface px-4 py-3.5';
/** Each form field's answer, as Compliance names it when it asks for changes (24 Nov 2026). */
const FLAG_KEYS: Record<string, string> = {
    rdb_code: 'company.rdb_code',
    legal_name: 'company.legal_name',
    tin: 'company.tin',
    incorporation_date: 'company.incorporation_date',
    province: 'company.address',
    district: 'company.address',
    sector: 'company.address',
    cell: 'company.address',
    street: 'company.address',
    industry: 'company.industry',
    address_province: 'address',
    address_district: 'address',
    address_sector: 'address',
    address_cell: 'address',
    address_street: 'address',
    company_phone: 'company.phone',
    rep_title: 'representative',
    rep_phone: 'representative',
    membership_number: 'licence.membership_number',
    licence_number: 'licence.licence_number',
    expires_on: 'licence.expires_on',
    firm: 'licence.firm',
    institution_type: 'institution.type',
    monthly_commitment: 'institution.monthly_commitment',
    bank_name: 'institution.bank_account',
    account_name: 'institution.bank_account',
    account_number: 'institution.bank_account',
};
/** The form step that holds an item Compliance flagged, so "Fix now" opens where the change is made. */
/** The sector an institution may pick besides the business sectors (QA #800); the server allows it too. */
const FINANCIAL_SERVICES = 'Financial services';

function stepForItem(key: string, role: SwitchRole): string {
    const head = key.split('.')[0];

    if (head === 'documents') {
        return role === 'auditor' ? 'licence' : 'documents';
    }

    return (
        {
            company: 'company',
            representative: 'people',
            directors: 'people',
            owners: 'people',
            licence: 'licence',
            address: 'coverage',
            coverage: 'coverage',
            institution: 'institution',
        }[head] ?? 'review'
    );
}

/** The items Compliance flagged, by key, with their note; empty when nothing was sent back. */
const Flags = createContext<Record<string, string>>({});

/** Compliance's word under a flagged answer or document. */
function Flag({ id }: { id: string }) {
    const { t } = useTranslation();
    const flags = use(Flags);
    const key = FLAG_KEYS[id] ?? id;

    if (!(key in flags)) {
        return null;
    }

    return (
        <p className="mt-1.5 flex items-start gap-1.5 text-[12px] font-semibold text-rz-warning-text">
            <span aria-hidden="true">!</span>
            {flags[key] === ''
                ? t('role.apply.flag.change')
                : t('role.apply.flag.note', { note: flags[key] })}
        </p>
    );
}

const flagged = (flags: Record<string, string>, id: string): boolean =>
    (FLAG_KEYS[id] ?? id) in flags;

const STEPS: Record<SwitchRole, string[]> = {
    business: ['company', 'people', 'documents', 'review'],
    auditor: ['licence', 'coverage', 'review'],
    institution: ['company', 'people', 'institution', 'documents', 'review'],
};
const ACCENT: Record<SwitchRole, string> = {
    business: '#17795a',
    auditor: '#7d420f',
    institution: '#5b3fd6',
};
const COMPANY_TYPES = [
    'private_limited',
    'public_limited',
    'cooperative',
    'partnership',
    'other',
] as const;
const BUSINESS_DOCUMENTS = [
    'rdb_certificate',
    'tin_certificate',
    'articles',
    'shareholder_register',
    'board_resolution',
] as const;
/** An institution's documents: the business ones, its authorisation letter and its bank's letter (26 Nov 2026). */
const INSTITUTION_DOCUMENTS = [
    'rdb_certificate',
    'tin_certificate',
    'articles',
    'shareholder_register',
    'authorisation_letter',
    'bank_letter',
] as const;

/** RWF digits grouped by thousands, as typed. */
function groupDigits(value: string): string {
    const digits = value.replace(/\D/g, '');

    return digits === '' ? '' : Number(digits).toLocaleString('en-US');
}

type Errors = Record<string, string>;
type Sending = { slot: string; percent: number | null };

function blankPerson(owner: boolean): RoleApplicationPerson {
    return {
        full_name: '',
        id_type: 'national_id',
        id_number: '',
        nationality: 'Rwandan',
        ...(owner ? { ownership_pct: '', pep: false } : {}),
    };
}

/**
 * Switching from Investor to Business representative or Auditor (role switch, 23 Nov 2026). The
 * person's own KYC is already sent; here they choose the role, fill in its steps (company, people and
 * documents for a business; ICPAR licence and coverage for an Auditor) and send it to Compliance.
 * Every step saves to the server, so they can leave and come back.
 */
export default function RoleApply(props: RoleApplicationProps) {
    const { t } = useTranslation();
    const page = usePage<{ auth?: { user?: { name?: string } | null } }>();
    const errors = page.props.errors as Errors;
    const accountName = (page.props.auth?.user?.name ?? '')
        .trim()
        .toLowerCase();
    const { application } = props;
    const role = props.role;
    const editable = [
        'none',
        'draft',
        'changes_requested',
        'rejected',
    ].includes(application.status);
    const steps = role === null ? [] : STEPS[role];
    const reached =
        role !== null && application.role === role && application.step
            ? application.step
            : (steps[0] ?? 'review');
    // A sent-back application opens on the earliest step holding a flagged item, whatever order
    // Compliance listed the items in.
    const flaggedSteps =
        application.status === 'changes_requested' && role !== null
            ? (application.requested_changes?.items ?? []).map((item) =>
                  stepForItem(item.key, role),
              )
            : [];
    const [step, setStep] = useState<string>(
        () =>
            steps.find((candidate) => flaggedSteps.includes(candidate)) ??
            reached,
    );
    const [sentBack] = useState(() => ({
        answers: JSON.stringify(application.answers ?? {}),
        uploads: application.uploads,
    }));
    const [answers, setAnswers] = useState<RoleApplicationAnswers>(() =>
        application.role === role && application.answers
            ? application.answers
            : { declared: false },
    );
    const [declared, setDeclared] = useState(false);
    const [sending, setSending] = useState<Sending | null>(null);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const envelope = () => ({
        request_id: crypto.randomUUID(),
        identity_context_revision: props.identity_context_revision,
        expected_revision: application.revision,
    });
    // A field edited since the last save no longer shows the error the server gave its old value.
    const [edited, setEdited] = useState<string[]>([]);
    const error = (key: string) =>
        edited.includes(key) ? undefined : errors[key];
    const markEdited = (prefix: string, fields: string[]) =>
        setEdited((before) => [
            ...before,
            ...fields.map((field) => `${prefix}${field}`),
        ]);
    const index = Math.max(0, steps.indexOf(step));
    const percent =
        role === null ? 0 : Math.round((index / steps.length) * 100);

    if (role === null) {
        return <RoleChoice {...props} />;
    }

    const kicker = (
        {
            business: 'role.apply.kicker_business',
            auditor: 'role.apply.kicker_auditor',
            institution: 'role.apply.kicker_institution',
        } as const
    )[role];

    function saveStep(stepAnswers: Record<string, FormDataConvertible>) {
        setBusy(true);
        setEdited([]);
        router.post(
            save().url,
            { ...envelope(), role, step, answers: stepAnswers },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const next = (page.props as unknown as RoleApplicationProps)
                        .application.step;
                    // While fixing a send-back, Save and continue goes on to the next step holding a
                    // flagged item (QA #753), and to Check and send once none is left.
                    const flaggedNext = steps
                        .slice(steps.indexOf(step) + 1)
                        .find(
                            (candidate) =>
                                candidate !== 'review' &&
                                flaggedSteps.includes(candidate),
                        );
                    setStep(
                        flaggedSteps.length > 0
                            ? (flaggedNext ?? 'review')
                            : (next ?? step),
                    );
                    window.scrollTo({ top: 0 });
                },
                onFinish: () => setBusy(false),
            },
        );
    }

    function send() {
        setBusy(true);
        setEdited([]);
        router.post(
            submit().url,
            { ...envelope(), declared },
            { preserveScroll: true, onFinish: () => setBusy(false) },
        );
    }

    function uploadFile(slot: string, file: File) {
        setUploadError(null);
        setSending({ slot, percent: null });
        router.post(
            upload().url,
            { ...envelope(), slot, file },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onProgress: (progress) =>
                    setSending({ slot, percent: progress?.percentage ?? null }),
                onError: (failures: Errors) =>
                    setUploadError(Object.values(failures).join(' ')),
                onFinish: () => setSending(null),
            },
        );
    }

    const company = answers.company ?? {
        legal_name: '',
        rdb_code: '',
        tin: '',
        company_type: '',
        incorporation_date: '',
        industry: '',
        province: '',
        district: '',
        sector: '',
        cell: '',
        street: '',
        phone: '',
    };
    const address = answers.address ?? {
        province: '',
        district: '',
        sector: '',
        cell: '',
        street: '',
    };
    const representative = answers.representative ?? {
        title: '',
        phone: '',
        authorised: false,
    };
    const directors = answers.directors ?? [blankPerson(false)];
    const owners = answers.owners ?? [];
    const noMajorOwner =
        role === 'institution' && answers.no_major_owner === true;
    const licence = answers.licence ?? {
        membership_number: '',
        licence_number: '',
        expires_on: '',
        firm: '',
    };
    const coverage = answers.coverage ?? {
        districts: [],
        no_conflicts: false,
        will_declare: false,
        ethics: false,
    };
    const institution = answers.institution ?? {
        type: '',
        monthly_commitment: '',
        bank_name: '',
        account_name: '',
        account_number: '',
        automates: false,
        own_rules: false,
    };
    const setInstitution = (change: Partial<typeof institution>) =>
        setAnswers({ ...answers, institution: { ...institution, ...change } });
    const people = distinctPeople(directors, owners);
    const tile = (slot: string, label: string, hint?: string) => (
        <UploadTile
            key={slot}
            slot={slot}
            label={label}
            hint={hint}
            accent={ACCENT[role]}
            uploaded={application.uploads[slot]}
            sending={sending}
            disabled={!editable}
            error={error(`documents.${slot}`)}
            onPick={(file) => uploadFile(slot, file)}
        />
    );

    const requested =
        application.status === 'changes_requested'
            ? (application.requested_changes ?? null)
            : null;
    const initialAnswers: object =
        requested?.before?.answers ??
        (JSON.parse(sentBack.answers) as Record<string, unknown>);
    const unchanged = (requested?.items ?? []).filter((item) => {
        if (item.key.startsWith('documents.')) {
            const slot = item.key.slice('documents.'.length);
            const before = requested?.before?.documents?.[slot];

            // Compare the stored document, not the name: the page may have reloaded since the upload,
            // and a replacement can carry the same filename.
            if (before !== undefined) {
                return application.uploads[slot]?.id === before;
            }

            return (
                application.uploads[slot]?.filename ===
                sentBack.uploads[slot]?.filename
            );
        }

        return (
            JSON.stringify(answerAt(answers, item.key)) ===
            JSON.stringify(answerAt(initialAnswers, item.key))
        );
    });
    // A document flagged for change loses its flag once it is replaced; a typed answer keeps its note
    // in view while it is being edited.
    const flags: Record<string, string> = Object.fromEntries(
        (requested?.items ?? [])
            .filter(
                (item) =>
                    !item.key.startsWith('documents.') ||
                    unchanged.includes(item),
            )
            .map((item) => [flagKey(item.key), item.note]),
    );

    return (
        <Flags value={flags}>
            <InvestorAuthFrame
                title={t(`role.apply.step.${step}.title` as MessageCode)}
                layout="workspace"
                aside={
                    <StepRail
                        kicker={t(kicker)}
                        accent={ACCENT[role]}
                        steps={steps.map((key) => ({
                            key,
                            label: t(
                                `role.apply.step.${key}.title` as MessageCode,
                            ),
                        }))}
                        current={
                            application.status === 'submitted'
                                ? steps.length
                                : index
                        }
                        onOpen={
                            editable
                                ? (key) => setStep(key as typeof step)
                                : undefined
                        }
                    >
                        <RailNote
                            title={t('kyc.rail.next_title')}
                            body={t('kyc.rail.next_body')}
                        />
                        <RailNote
                            title={t('kyc.rail.privacy_title')}
                            body={t('kyc.rail.privacy_body')}
                        />
                    </StepRail>
                }
            >
                <div
                    data-audience={role}
                    className="px-5 pt-[calc(env(safe-area-inset-top)+14px)] pb-10 lg:px-0 lg:pt-10"
                >
                    <Header
                        back={
                            index > 0 && editable
                                ? () => setStep(steps[index - 1])
                                : show().url
                        }
                        percent={
                            application.status === 'submitted' ? 100 : percent
                        }
                        accent={ACCENT[role]}
                    />
                    <p
                        className="mt-2 text-[12.5px] font-semibold tracking-[.05em] uppercase lg:hidden"
                        style={{ color: ACCENT[role] }}
                    >
                        {t(kicker)}
                    </p>

                    {application.status === 'submitted' ? (
                        <UnderReview role={role} kyc={props.kyc} />
                    ) : (
                        <>
                            <h1 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                                {t(
                                    `role.apply.step.${step}.title` as MessageCode,
                                )}
                            </h1>
                            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                                {t(
                                    `role.apply.step.${step}.subtitle` as MessageCode,
                                )}
                            </p>
                            {application.note !== null &&
                                ['changes_requested', 'rejected'].includes(
                                    application.status,
                                ) && (
                                    <div className="mt-4 rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                                        <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                            {t(
                                                application.status ===
                                                    'rejected'
                                                    ? 'role.apply.rejected'
                                                    : 'role.apply.asked_for_more',
                                            )}
                                        </p>
                                        <p className="mt-1.5 text-[13px] text-rz-body">
                                            “{application.note}”
                                        </p>
                                        {requested &&
                                            requested.items.length > 0 && (
                                                <RequestedItems
                                                    items={requested.items}
                                                />
                                            )}
                                    </div>
                                )}
                            {application.note === null &&
                                requested &&
                                requested.items.length > 0 && (
                                    <div className="mt-4 rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                                        <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                            {t('role.apply.asked_for_more')}
                                        </p>
                                        <RequestedItems
                                            items={requested.items}
                                        />
                                    </div>
                                )}
                            {error('form') && (
                                <div className="mt-4">
                                    <ErrorBanner>{error('form')}</ErrorBanner>
                                </div>
                            )}
                            {uploadError && (
                                <div className="mt-4">
                                    <ErrorBanner>{uploadError}</ErrorBanner>
                                </div>
                            )}
                            {role === 'auditor' && !props.two_factor && (
                                <div className="mt-4 rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                                    <p className="text-[13px] font-semibold text-rz-ink">
                                        {t('role.apply.two_factor.title')}
                                    </p>
                                    <p className="mt-1 text-[12.5px] text-rz-body">
                                        {t('role.apply.two_factor.body')}
                                    </p>
                                    <Link
                                        href={security()}
                                        className="mt-2 inline-block text-[13px] font-semibold text-rz-accent-text"
                                    >
                                        {t('role.apply.two_factor.action')}
                                    </Link>
                                </div>
                            )}

                            {step === 'company' && (
                                <div className="mt-5 flex flex-col gap-[13px] lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-6">
                                    <Field
                                        id="rdb_code"
                                        label={t('role.apply.company.rdb_code')}
                                        hint={t(
                                            'role.apply.company.rdb_code_hint',
                                        )}
                                        value={company.rdb_code}
                                        inputMode="numeric"
                                        error={error('company.rdb_code')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    rdb_code: value,
                                                },
                                            })
                                        }
                                    />
                                    <Field
                                        id="legal_name"
                                        label={t(
                                            'role.apply.company.legal_name',
                                        )}
                                        value={company.legal_name}
                                        error={error('company.legal_name')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    legal_name: value,
                                                },
                                            })
                                        }
                                    />
                                    <Field
                                        id="tin"
                                        label={t('role.apply.company.tin')}
                                        hint={t('role.apply.company.tin_hint')}
                                        value={company.tin}
                                        inputMode="numeric"
                                        error={error('company.tin')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    tin: value,
                                                },
                                            })
                                        }
                                    />
                                    <div>
                                        <p className={LABEL}>
                                            {t(
                                                'role.apply.company.company_type',
                                            )}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {COMPANY_TYPES.map((type) => (
                                                <Chip
                                                    key={type}
                                                    accent={ACCENT[role]}
                                                    active={
                                                        company.company_type ===
                                                        type
                                                    }
                                                    onClick={() =>
                                                        setAnswers({
                                                            ...answers,
                                                            company: {
                                                                ...company,
                                                                company_type:
                                                                    type,
                                                            },
                                                        })
                                                    }
                                                >
                                                    {t(
                                                        `role.apply.company_type.${type}`,
                                                    )}
                                                </Chip>
                                            ))}
                                        </div>
                                        <FieldError
                                            message={error(
                                                'company.company_type',
                                            )}
                                        />
                                    </div>
                                    <Field
                                        id="incorporation_date"
                                        type="date"
                                        label={t(
                                            'role.apply.company.incorporated',
                                        )}
                                        value={company.incorporation_date}
                                        error={error(
                                            'company.incorporation_date',
                                        )}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    incorporation_date: value,
                                                },
                                            })
                                        }
                                    />
                                    <Select
                                        id="industry"
                                        label={t('role.apply.company.sector')}
                                        value={company.industry}
                                        options={
                                            role === 'institution'
                                                ? [
                                                      ...props.catalog
                                                          .industries,
                                                      FINANCIAL_SERVICES,
                                                  ]
                                                : props.catalog.industries
                                        }
                                        error={error('company.industry')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    industry: value,
                                                },
                                            })
                                        }
                                    />
                                    <AddressFields
                                        idPrefix=""
                                        errorPrefix="company."
                                        provinces={props.catalog.provinces}
                                        value={company}
                                        error={error}
                                        onChange={(value) => {
                                            markEdited(
                                                'company.',
                                                Object.keys(value),
                                            );
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    ...value,
                                                },
                                            });
                                        }}
                                    />
                                    <Field
                                        id="company_phone"
                                        type="tel"
                                        label={t('role.apply.company.phone')}
                                        value={company.phone}
                                        error={error('company.phone')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                company: {
                                                    ...company,
                                                    phone: value,
                                                },
                                            })
                                        }
                                    />
                                </div>
                            )}

                            {step === 'people' && (
                                <div className="mt-5 flex flex-col gap-[13px] lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-6">
                                    <div className="grid grid-cols-2 gap-2.5 lg:col-span-2 lg:gap-x-6">
                                        <Field
                                            id="rep_title"
                                            label={t(
                                                'role.apply.people.your_title',
                                            )}
                                            value={representative.title}
                                            error={error(
                                                'representative.title',
                                            )}
                                            onChange={(value) =>
                                                setAnswers({
                                                    ...answers,
                                                    representative: {
                                                        ...representative,
                                                        title: value,
                                                    },
                                                })
                                            }
                                        />
                                        <Field
                                            id="rep_phone"
                                            type="tel"
                                            label={t(
                                                'role.apply.people.your_phone',
                                            )}
                                            value={representative.phone}
                                            error={error(
                                                'representative.phone',
                                            )}
                                            onChange={(value) =>
                                                setAnswers({
                                                    ...answers,
                                                    representative: {
                                                        ...representative,
                                                        phone: value,
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="lg:col-span-2">
                                        <Check
                                            accent={ACCENT[role]}
                                            checked={representative.authorised}
                                            error={error(
                                                'representative.authorised',
                                            )}
                                            onChange={(checked) =>
                                                setAnswers({
                                                    ...answers,
                                                    representative: {
                                                        ...representative,
                                                        authorised: checked,
                                                    },
                                                })
                                            }
                                        >
                                            {t('role.apply.people.authorised')}
                                        </Check>
                                    </div>
                                    <PeopleList
                                        title={t('role.apply.people.directors')}
                                        people={directors}
                                        accent={ACCENT[role]}
                                        errors={errors}
                                        field="directors"
                                        onChange={(list) =>
                                            setAnswers({
                                                ...answers,
                                                directors: list,
                                                owners: followDirectors(
                                                    directors,
                                                    list,
                                                    owners,
                                                ),
                                            })
                                        }
                                    />
                                    {role === 'institution' && (
                                        <Check
                                            accent={ACCENT[role]}
                                            checked={noMajorOwner}
                                            onChange={(checked) =>
                                                setAnswers({
                                                    ...answers,
                                                    no_major_owner: checked,
                                                })
                                            }
                                        >
                                            {t(
                                                'role.apply.people.no_major_owner',
                                            )}
                                        </Check>
                                    )}
                                    <OwnersList
                                        owners={owners}
                                        directors={directors}
                                        accent={ACCENT[role]}
                                        errors={errors}
                                        onChange={(list) =>
                                            setAnswers({
                                                ...answers,
                                                owners: list,
                                            })
                                        }
                                    />
                                </div>
                            )}

                            {step === 'documents' && (
                                <div className="mt-5 flex flex-col gap-2.5 lg:grid lg:grid-cols-2 lg:gap-x-6 lg:gap-y-3">
                                    {(role === 'institution'
                                        ? INSTITUTION_DOCUMENTS
                                        : BUSINESS_DOCUMENTS
                                    ).map((slot) =>
                                        tile(
                                            slot,
                                            t(`role.apply.document.${slot}`),
                                            slot === 'tin_certificate'
                                                ? t(
                                                      'role.apply.document.staff_only',
                                                  )
                                                : slot === 'board_resolution'
                                                  ? t(
                                                        directors.some(
                                                            (director) =>
                                                                director.full_name
                                                                    .trim()
                                                                    .toLowerCase() ===
                                                                accountName,
                                                        )
                                                            ? 'role.apply.document.optional'
                                                            : 'role.apply.document.resolution_needed',
                                                    )
                                                  : undefined,
                                        ),
                                    )}
                                    {people.map((person) =>
                                        tile(
                                            `id:${person.id_number}`,
                                            t('role.apply.document.person_id', {
                                                name: person.full_name,
                                            }),
                                        ),
                                    )}
                                    <p className="text-[11.5px] text-rz-secondary lg:col-span-2">
                                        {t('role.apply.document.formats')}
                                    </p>
                                </div>
                            )}

                            {step === 'institution' && (
                                <div className="mt-5 flex flex-col gap-[13px] lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-6">
                                    <div className="lg:col-span-2">
                                        <p className={LABEL}>
                                            {t('role.apply.institution.type')}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {props.catalog.institution_types.map(
                                                (type) => (
                                                    <Chip
                                                        key={type}
                                                        accent={ACCENT[role]}
                                                        active={
                                                            institution.type ===
                                                            type
                                                        }
                                                        onClick={() =>
                                                            setInstitution({
                                                                type,
                                                            })
                                                        }
                                                    >
                                                        {t(
                                                            `role.apply.institution.type.${type}` as MessageCode,
                                                        )}
                                                    </Chip>
                                                ),
                                            )}
                                        </div>
                                        <Flag id="institution.type" />
                                        <FieldError
                                            message={error('institution.type')}
                                        />
                                    </div>
                                    <Field
                                        id="monthly_commitment"
                                        inputMode="numeric"
                                        label={t(
                                            'role.apply.institution.monthly_commitment',
                                        )}
                                        hint={t(
                                            'role.apply.institution.monthly_commitment_hint',
                                            {
                                                amount: props.catalog.min_monthly_commitment.toLocaleString(
                                                    'en-US',
                                                ),
                                            },
                                        )}
                                        value={groupDigits(
                                            institution.monthly_commitment,
                                        )}
                                        error={error(
                                            'institution.monthly_commitment',
                                        )}
                                        onChange={(value) =>
                                            setInstitution({
                                                monthly_commitment:
                                                    value.replace(/\D/g, ''),
                                            })
                                        }
                                    />
                                    <p
                                        className={cn(
                                            LABEL,
                                            'mt-2 lg:col-span-2',
                                        )}
                                    >
                                        {t('role.apply.institution.account')}
                                    </p>
                                    <div className="lg:col-span-2">
                                        <Flag id="institution.bank_account" />
                                    </div>
                                    <Field
                                        id="bank_name"
                                        grouped
                                        label={t(
                                            'role.apply.institution.bank_name',
                                        )}
                                        value={institution.bank_name}
                                        error={error('institution.bank_name')}
                                        onChange={(value) =>
                                            setInstitution({ bank_name: value })
                                        }
                                    />
                                    <Field
                                        id="account_number"
                                        inputMode="numeric"
                                        grouped
                                        label={t(
                                            'role.apply.institution.account_number',
                                        )}
                                        value={institution.account_number}
                                        error={error(
                                            'institution.account_number',
                                        )}
                                        onChange={(value) =>
                                            setInstitution({
                                                account_number: value,
                                            })
                                        }
                                    />
                                    <div className="lg:col-span-2">
                                        <Field
                                            id="account_name"
                                            grouped
                                            label={t(
                                                'role.apply.institution.account_name',
                                            )}
                                            hint={t(
                                                'role.apply.institution.account_name_hint',
                                                { name: company.legal_name },
                                            )}
                                            value={institution.account_name}
                                            error={error(
                                                'institution.account_name',
                                            )}
                                            onChange={(value) =>
                                                setInstitution({
                                                    account_name: value,
                                                })
                                            }
                                        />
                                    </div>
                                    <p
                                        className={cn(
                                            LABEL,
                                            'mt-2 lg:col-span-2',
                                        )}
                                    >
                                        {t(
                                            'role.apply.institution.declarations',
                                        )}
                                    </p>
                                    <div className="lg:col-span-2">
                                        <Flag id="institution.declarations" />
                                    </div>
                                    {(['automates', 'own_rules'] as const).map(
                                        (key) => (
                                            <div
                                                key={key}
                                                className="lg:col-span-2"
                                            >
                                                <Check
                                                    accent={ACCENT[role]}
                                                    checked={institution[key]}
                                                    error={error(
                                                        `institution.${key}`,
                                                    )}
                                                    onChange={(checked) =>
                                                        setInstitution({
                                                            [key]: checked,
                                                        })
                                                    }
                                                >
                                                    {t(
                                                        `role.apply.institution.${key}`,
                                                        {
                                                            amount: groupDigits(
                                                                institution.monthly_commitment ||
                                                                    String(
                                                                        props
                                                                            .catalog
                                                                            .min_monthly_commitment,
                                                                    ),
                                                            ),
                                                        },
                                                    )}
                                                </Check>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}

                            {step === 'licence' && (
                                <div className="mt-5 flex flex-col gap-[13px] lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-6">
                                    <Field
                                        id="membership_number"
                                        label={t(
                                            'role.apply.licence.membership',
                                        )}
                                        value={licence.membership_number}
                                        error={error(
                                            'licence.membership_number',
                                        )}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                licence: {
                                                    ...licence,
                                                    membership_number: value,
                                                },
                                            })
                                        }
                                    />
                                    <div className="grid grid-cols-1 gap-2.5 min-[440px]:grid-cols-2 lg:col-span-2 lg:gap-x-6">
                                        <Field
                                            id="licence_number"
                                            label={t(
                                                'role.apply.licence.certificate',
                                            )}
                                            value={licence.licence_number}
                                            error={error(
                                                'licence.licence_number',
                                            )}
                                            onChange={(value) =>
                                                setAnswers({
                                                    ...answers,
                                                    licence: {
                                                        ...licence,
                                                        licence_number: value,
                                                    },
                                                })
                                            }
                                        />
                                        <Field
                                            id="expires_on"
                                            type="date"
                                            label={t(
                                                'role.apply.licence.valid_until',
                                            )}
                                            value={licence.expires_on}
                                            error={error('licence.expires_on')}
                                            onChange={(value) =>
                                                setAnswers({
                                                    ...answers,
                                                    licence: {
                                                        ...licence,
                                                        expires_on: value,
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                    <Field
                                        id="firm"
                                        label={t('role.apply.licence.firm')}
                                        value={licence.firm}
                                        error={error('licence.firm')}
                                        onChange={(value) =>
                                            setAnswers({
                                                ...answers,
                                                licence: {
                                                    ...licence,
                                                    firm: value,
                                                },
                                            })
                                        }
                                    />
                                    {tile(
                                        'practising_certificate',
                                        t(
                                            'role.apply.document.practising_certificate',
                                        ),
                                    )}
                                </div>
                            )}

                            {step === 'coverage' && (
                                <div className="mt-5 flex flex-col gap-[13px]">
                                    <AddressFields
                                        idPrefix="address_"
                                        errorPrefix="address."
                                        provinces={props.catalog.provinces}
                                        value={address}
                                        error={error}
                                        onChange={(value) => {
                                            markEdited(
                                                'address.',
                                                Object.keys(value),
                                            );
                                            setAnswers({
                                                ...answers,
                                                address: {
                                                    ...address,
                                                    ...value,
                                                },
                                            });
                                        }}
                                    />
                                    <div>
                                        <p className={LABEL}>
                                            {t('role.apply.coverage.districts')}
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {props.catalog.districts.map(
                                                (district) => {
                                                    const on =
                                                        coverage.districts.includes(
                                                            district,
                                                        );

                                                    return (
                                                        <Chip
                                                            key={district}
                                                            accent={
                                                                ACCENT[role]
                                                            }
                                                            active={on}
                                                            onClick={() =>
                                                                setAnswers({
                                                                    ...answers,
                                                                    coverage: {
                                                                        ...coverage,
                                                                        districts:
                                                                            on
                                                                                ? coverage.districts.filter(
                                                                                      (
                                                                                          item,
                                                                                      ) =>
                                                                                          item !==
                                                                                          district,
                                                                                  )
                                                                                : [
                                                                                      ...coverage.districts,
                                                                                      district,
                                                                                  ],
                                                                    },
                                                                })
                                                            }
                                                        >
                                                            {district}
                                                        </Chip>
                                                    );
                                                },
                                            )}
                                        </div>
                                        <FieldError
                                            message={error(
                                                'coverage.districts',
                                            )}
                                        />
                                    </div>
                                    <p className={LABEL}>
                                        {t('role.apply.coverage.conflicts')}
                                    </p>
                                    {(
                                        [
                                            'no_conflicts',
                                            'will_declare',
                                            'ethics',
                                        ] as const
                                    ).map((key) => (
                                        <Check
                                            key={key}
                                            accent={ACCENT[role]}
                                            checked={coverage[key]}
                                            error={error(`coverage.${key}`)}
                                            onChange={(checked) =>
                                                setAnswers({
                                                    ...answers,
                                                    coverage: {
                                                        ...coverage,
                                                        [key]: checked,
                                                    },
                                                })
                                            }
                                        >
                                            {t(`role.apply.coverage.${key}`)}
                                        </Check>
                                    ))}
                                </div>
                            )}

                            {step === 'review' && (
                                <div className="mt-5 flex flex-col gap-2.5">
                                    {unchanged.length > 0 && (
                                        <div
                                            role="status"
                                            className="rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]"
                                        >
                                            <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                                {t(
                                                    'role.apply.review.unchanged_title',
                                                )}
                                            </p>
                                            <ul className="mt-1 list-disc pl-5 text-[12.5px] text-rz-body">
                                                {unchanged.map((item) => (
                                                    <li key={item.key}>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                role !== null &&
                                                                setStep(
                                                                    stepForItem(
                                                                        item.key,
                                                                        role,
                                                                    ),
                                                                )
                                                            }
                                                            className="text-left font-semibold underline"
                                                        >
                                                            {item.label}
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                            <p className="mt-1.5 text-[12px] text-rz-secondary">
                                                {t(
                                                    'role.apply.review.unchanged_body',
                                                )}
                                            </p>
                                        </div>
                                    )}
                                    {props.kyc !== 'approved' && (
                                        <div className="rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                                            <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                                {t(
                                                    'role.apply.review.kyc_pending_title',
                                                )}
                                            </p>
                                            <p className="mt-1 text-[12.5px] text-rz-body">
                                                {t(
                                                    'role.apply.review.kyc_pending_body',
                                                )}
                                            </p>
                                        </div>
                                    )}
                                    {props.blockers.length > 0 && (
                                        <div className="rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                                            <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                                {t('role.apply.blockers.title')}
                                            </p>
                                            <ul className="mt-1 list-disc pl-4 text-[12.5px] text-rz-body">
                                                {props.blockers.map((code) => (
                                                    <li key={code}>
                                                        {t(
                                                            `role.apply.blocker.${code}` as MessageCode,
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                    {role !== 'auditor' ? (
                                        <>
                                            <Summary
                                                title={t(
                                                    'role.apply.step.company.title',
                                                )}
                                                onEdit={() =>
                                                    setStep('company')
                                                }
                                                rows={[
                                                    [
                                                        company.legal_name,
                                                        `RDB ${company.rdb_code}`,
                                                    ],
                                                    [
                                                        company.industry,
                                                        addressLine(company),
                                                    ],
                                                ]}
                                            />
                                            <Summary
                                                title={t(
                                                    'role.apply.step.people.title',
                                                )}
                                                onEdit={() => setStep('people')}
                                                rows={[
                                                    [
                                                        t(
                                                            'role.apply.people.directors',
                                                        ),
                                                        String(
                                                            directors.length,
                                                        ),
                                                    ],
                                                    [
                                                        t(
                                                            'role.apply.people.owners',
                                                        ),
                                                        String(owners.length),
                                                    ],
                                                ]}
                                            />
                                            {role === 'institution' && (
                                                <Summary
                                                    title={t(
                                                        'role.apply.step.institution.title',
                                                    )}
                                                    onEdit={() =>
                                                        setStep('institution')
                                                    }
                                                    rows={[
                                                        [
                                                            institution.type ===
                                                            ''
                                                                ? ''
                                                                : t(
                                                                      `role.apply.institution.type.${institution.type}` as MessageCode,
                                                                  ),
                                                            `RWF ${groupDigits(institution.monthly_commitment)} / ${t('role.apply.institution.month')}`,
                                                        ],
                                                        [
                                                            institution.bank_name,
                                                            institution.account_name,
                                                        ],
                                                    ]}
                                                />
                                            )}
                                            <Summary
                                                title={t(
                                                    'role.apply.step.documents.title',
                                                )}
                                                onEdit={() =>
                                                    setStep('documents')
                                                }
                                                rows={[
                                                    [
                                                        t(
                                                            'role.apply.review.uploaded',
                                                        ),
                                                        String(
                                                            Object.keys(
                                                                application.uploads,
                                                            ).length,
                                                        ),
                                                    ],
                                                ]}
                                            />
                                        </>
                                    ) : (
                                        <>
                                            <Summary
                                                title={t(
                                                    'role.apply.step.licence.title',
                                                )}
                                                onEdit={() =>
                                                    setStep('licence')
                                                }
                                                rows={[
                                                    [
                                                        t(
                                                            'role.apply.licence.certificate',
                                                        ),
                                                        licence.licence_number,
                                                    ],
                                                    [
                                                        t(
                                                            'role.apply.licence.valid_until',
                                                        ),
                                                        licence.expires_on,
                                                    ],
                                                ]}
                                            />
                                            <Summary
                                                title={t(
                                                    'role.apply.step.coverage.title',
                                                )}
                                                onEdit={() =>
                                                    setStep('coverage')
                                                }
                                                rows={[
                                                    [
                                                        t(
                                                            'role.apply.company.address',
                                                        ),
                                                        addressLine(address),
                                                    ],
                                                    [
                                                        t(
                                                            'role.apply.coverage.districts',
                                                        ),
                                                        coverage.districts.join(
                                                            ', ',
                                                        ),
                                                    ],
                                                ]}
                                            />
                                        </>
                                    )}
                                    <Check
                                        accent={ACCENT[role]}
                                        checked={declared}
                                        error={error('declared')}
                                        onChange={setDeclared}
                                    >
                                        {t(
                                            (
                                                {
                                                    business:
                                                        'role.apply.review.declare_business',
                                                    auditor:
                                                        'role.apply.review.declare_auditor',
                                                    institution:
                                                        'role.apply.review.declare_institution',
                                                } as const
                                            )[role],
                                        )}
                                    </Check>
                                    <ProblemList errors={errors} />
                                </div>
                            )}

                            <div className="mt-8 lg:flex lg:justify-end lg:border-t lg:border-rz-border lg:pt-6">
                                {step === 'review' ? (
                                    <PrimaryButton
                                        accent={ACCENT[role]}
                                        disabled={busy || !editable}
                                        onClick={send}
                                    >
                                        {t(
                                            busy
                                                ? 'role.apply.sending'
                                                : 'role.apply.send',
                                        )}
                                    </PrimaryButton>
                                ) : (
                                    <PrimaryButton
                                        accent={ACCENT[role]}
                                        disabled={
                                            busy ||
                                            sending !== null ||
                                            !editable
                                        }
                                        onClick={() =>
                                            saveStep(
                                                step === 'company'
                                                    ? company
                                                    : step === 'people'
                                                      ? {
                                                            representative,
                                                            directors,
                                                            owners,
                                                            no_major_owner:
                                                                noMajorOwner,
                                                        }
                                                      : step === 'institution'
                                                        ? institution
                                                        : step === 'licence'
                                                          ? licence
                                                          : step === 'coverage'
                                                            ? // The registered address is saved with the coverage step.
                                                              {
                                                                  ...coverage,
                                                                  address,
                                                              }
                                                            : {},
                                            )
                                        }
                                    >
                                        {t(
                                            sending !== null
                                                ? 'role.apply.waiting_uploads'
                                                : busy
                                                  ? 'role.apply.saving'
                                                  : 'role.apply.continue',
                                        )}
                                    </PrimaryButton>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </InvestorAuthFrame>
        </Flags>
    );
}

/** The distinct people named as directors and owners: one ID document each. */
function distinctPeople(
    directors: RoleApplicationPerson[],
    owners: RoleApplicationPerson[],
): RoleApplicationPerson[] {
    const seen = new Map<string, RoleApplicationPerson>();

    for (const person of [...directors, ...owners]) {
        const key = person.id_number.replace(/\s+/g, '').toUpperCase();

        if (key !== '' && !seen.has(key)) {
            seen.set(key, { ...person, id_number: key });
        }
    }

    return [...seen.values()];
}

/**
 * The role choice (design "Your role on Rozine"): the person is an Investor; once their KYC is sent
 * they may switch to one other role, and the switch needs their Investor positions cleared first.
 */
function RoleChoice(props: RoleApplicationProps) {
    const { t } = useTranslation();

    return (
        <InvestorAuthFrame title={t('role.choice.title')} layout="workspace">
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+14px)] pb-10 lg:px-0 lg:pt-10">
                <Header
                    back={dashboard().url}
                    percent={null}
                    accent="#1e3aff"
                />
                <p className="mt-2 text-[12.5px] font-semibold tracking-[.05em] text-rz-accent-app-text uppercase">
                    {t('role.choice.kicker')}
                </p>
                <h1 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                    {t('role.choice.title')}
                </h1>
                <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                    {t('role.choice.subtitle')}
                </p>
                <div className="mt-4 flex flex-col gap-2.5 lg:mt-8 lg:grid lg:grid-cols-2 lg:items-stretch lg:gap-5">
                    <div className={CARD}>
                        <div className="flex items-center justify-between">
                            <span className="text-[15px] font-semibold text-rz-ink">
                                {t('suite.app.investor.title')}
                            </span>
                            <span className="rounded-md bg-[rgba(29,158,117,.1)] px-2 py-0.5 text-[10.5px] font-bold text-[#17795a]">
                                {t('role.choice.current')}
                            </span>
                        </div>
                        <p className="mt-1 text-[12px] text-rz-secondary">
                            {t(
                                props.kyc === 'approved'
                                    ? 'role.choice.investor_approved'
                                    : 'role.choice.investor_pending',
                            )}
                        </p>
                    </div>
                    {(['business', 'auditor', 'institution'] as const).map(
                        (option) => (
                            <div key={option} className={CARD}>
                                <span className="text-[15px] font-semibold text-rz-ink">
                                    {t(`role.choice.${option}.title`)}
                                </span>
                                <p className="mt-1 text-[12px] text-rz-secondary">
                                    {t(`role.choice.${option}.body`)}
                                </p>
                                <Link
                                    href={show({ query: { for: option } })}
                                    className="mt-2.5 flex h-10 items-center justify-center rounded-[11px] text-[13px] font-semibold text-white"
                                    style={{ background: ACCENT[option] }}
                                >
                                    {t(`role.choice.${option}.start`)}
                                </Link>
                            </div>
                        ),
                    )}
                    {props.blockers.length > 0 && (
                        <div className="rounded-2xl border border-[#f1d9bd] bg-[#fff8ef] px-4 py-3 lg:col-span-2 dark:border-[rgba(255,180,100,.3)] dark:bg-[rgba(194,102,31,.12)]">
                            <p className="text-[11px] font-bold tracking-[.05em] text-[#8a4b0f] uppercase dark:text-[#f0b37a]">
                                {t('role.apply.blockers.title')}
                            </p>
                            <ul className="mt-1 list-disc pl-4 text-[12.5px] text-rz-body">
                                {props.blockers.map((code) => (
                                    <li key={code}>
                                        {t(
                                            `role.apply.blocker.${code}` as MessageCode,
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                    {props.kyc !== 'approved' && (
                        <p className="text-[12px] text-rz-secondary lg:col-span-2">
                            {t('role.choice.kyc_note')}{' '}
                            <a
                                href={investorVerification().url}
                                className="font-semibold text-rz-accent-text"
                            >
                                {t('role.choice.kyc_link')}
                            </a>
                        </p>
                    )}
                </div>
            </div>
        </InvestorAuthFrame>
    );
}

function UnderReview({
    role,
    kyc,
}: {
    role: SwitchRole;
    kyc: RoleApplicationProps['kyc'];
}) {
    const { t } = useTranslation();

    return (
        <>
            <h1 className="mt-1.5 text-[22px] font-semibold text-rz-ink">
                {t('role.apply.review_state.title')}
            </h1>
            <p className="mt-1.5 text-[13.5px] text-rz-secondary">
                {t(`role.apply.review_state.${role}`)}
            </p>
            <div className={cn(CARD, 'mt-4')}>
                <ul className="flex flex-col gap-3 text-[13px]">
                    <li className="font-semibold text-rz-ink">
                        ✓ {t('role.apply.review_state.sent')}
                    </li>
                    <li className="text-rz-body">
                        {kyc === 'approved' ? '✓ ' : '○ '}
                        {t('role.apply.review_state.kyc')}
                    </li>
                    <li className="text-rz-body">
                        ○ {t('role.apply.review_state.compliance')}
                    </li>
                </ul>
            </div>
            <Link
                href={dashboard()}
                className="mt-8 flex h-[52px] items-center justify-center rounded-2xl bg-rz-ink text-[15px] font-semibold text-rz-surface"
            >
                {t('role.apply.back_home')}
            </Link>
        </>
    );
}

function Header({
    back,
    percent,
    accent,
}: {
    back: string | (() => void);
    percent: number | null;
    accent: string;
}) {
    const { t } = useTranslation();
    const button =
        'flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink';

    return (
        <div className="flex items-center gap-3">
            {typeof back === 'string' ? (
                <Link
                    href={back}
                    aria-label={t('investor.common.back')}
                    className={button}
                >
                    <span aria-hidden>←</span>
                </Link>
            ) : (
                <button
                    type="button"
                    aria-label={t('investor.common.back')}
                    onClick={back}
                    className={button}
                >
                    <span aria-hidden>←</span>
                </button>
            )}
            {percent !== null && (
                <>
                    <div
                        role="progressbar"
                        aria-label={t('role.apply.progress')}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent}
                        className="h-1.5 flex-1 overflow-hidden rounded-[4px] bg-rz-border lg:invisible"
                    >
                        <div
                            className="h-full transition-[width] duration-400"
                            style={{
                                width: `${percent}%`,
                                background: `linear-gradient(90deg, ${accent}, #1d9e75)`,
                            }}
                        />
                    </div>
                    <span
                        aria-hidden
                        className="text-[12.5px] font-semibold text-rz-secondary lg:hidden"
                    >
                        {percent}%
                    </span>
                </>
            )}
        </div>
    );
}

function Field({
    id,
    label,
    value,
    onChange,
    hint,
    error,
    type = 'text',
    inputMode,
    placeholder,
    grouped = false,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    /** Part of a group that shows Compliance's word once, above its fields. */
    grouped?: boolean;
    hint?: string;
    error?: string;
    type?: 'text' | 'date' | 'tel';
    inputMode?: 'numeric' | 'decimal';
}) {
    return (
        <div>
            <label htmlFor={id} className={LABEL}>
                {label}
            </label>
            <input
                id={id}
                type={type}
                inputMode={inputMode}
                placeholder={placeholder}
                value={value}
                aria-invalid={error !== undefined}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    FIELD,
                    flagged(use(Flags), id) &&
                        'border-rz-warning-text ring-2 ring-rz-warning-tint',
                )}
            />
            {!grouped && <Flag id={id} />}
            {error ? (
                <FieldError message={error} />
            ) : (
                hint && (
                    <p className="mt-1.5 text-[11.5px] text-rz-secondary">
                        {hint}
                    </p>
                )
            )}
        </div>
    );
}

function Select({
    id,
    label,
    value,
    options,
    onChange,
    error,
    placeholder,
    grouped = false,
}: {
    id: string;
    label: string;
    value: string;
    options: string[];
    onChange: (value: string) => void;
    error?: string;
    placeholder?: string;
    /** Part of a group that shows Compliance's word once, above its fields. */
    grouped?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <div>
            <label htmlFor={id} className={LABEL}>
                {label}
            </label>
            <select
                id={id}
                value={value}
                aria-invalid={error !== undefined}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    FIELD,
                    flagged(use(Flags), id) &&
                        'border-rz-warning-text ring-2 ring-rz-warning-tint',
                )}
            >
                <option value="">
                    {placeholder ?? t('role.apply.choose')}
                </option>
                {options.map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
            {!grouped && <Flag id={id} />}
            <FieldError message={error} />
        </div>
    );
}

/** One line for the review step: street, cell, sector, district. */
function addressLine(address: RoleApplicationAddress): string {
    return [address.street, address.cell, address.sector, address.district]
        .map((part) => part.trim())
        .filter((part) => part !== '')
        .join(', ');
}

/**
 * The registered address (Erastus, 24 Nov 2026): a province, a district of that province, and the
 * sector, cell and street as typed. Changing the province clears a district outside it.
 */
function AddressFields({
    idPrefix,
    errorPrefix,
    provinces,
    value,
    error,
    onChange,
}: {
    idPrefix: string;
    errorPrefix: string;
    provinces: Record<string, string[]>;
    value: RoleApplicationAddress;
    error: (key: string) => string | undefined;
    onChange: (value: Partial<RoleApplicationAddress>) => void;
}) {
    const { t } = useTranslation();

    return (
        <fieldset className="flex flex-col gap-[13px] lg:col-span-2">
            <legend className={cn(LABEL, 'mb-2.5 tracking-[.06em]')}>
                {t('role.apply.company.address')}
            </legend>
            <Flag id={`${idPrefix}street`} />
            <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <Select
                    id={`${idPrefix}province`}
                    grouped
                    label={t('role.apply.address.province')}
                    value={value.province}
                    options={Object.keys(provinces)}
                    error={error(`${errorPrefix}province`)}
                    onChange={(province) =>
                        onChange({
                            province,
                            district: (provinces[province] ?? []).includes(
                                value.district,
                            )
                                ? value.district
                                : '',
                        })
                    }
                />
                <Select
                    id={`${idPrefix}district`}
                    grouped
                    label={t('role.apply.company.district')}
                    value={value.district}
                    options={provinces[value.province] ?? []}
                    placeholder={t('role.apply.address.select_district')}
                    error={error(`${errorPrefix}district`)}
                    onChange={(district) => onChange({ district })}
                />
                <Field
                    id={`${idPrefix}sector`}
                    grouped
                    label={t('role.apply.address.sector')}
                    placeholder={t('role.apply.address.sector_placeholder')}
                    value={value.sector}
                    error={error(`${errorPrefix}sector`)}
                    onChange={(sector) => onChange({ sector })}
                />
                <Field
                    id={`${idPrefix}cell`}
                    grouped
                    label={t('role.apply.address.cell')}
                    placeholder={t('role.apply.address.cell_placeholder')}
                    value={value.cell}
                    error={error(`${errorPrefix}cell`)}
                    onChange={(cell) => onChange({ cell })}
                />
            </div>
            <Field
                id={`${idPrefix}street`}
                grouped
                label={t('role.apply.address.street')}
                placeholder={t('role.apply.address.street_placeholder')}
                hint={t('role.apply.address.street_hint')}
                value={value.street}
                error={error(`${errorPrefix}street`)}
                onChange={(street) => onChange({ street })}
            />
        </fieldset>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? (
        <p role="alert" className="mt-1.5 text-[11.5px] text-rz-danger-text">
            {message}
        </p>
    ) : null;
}

function Chip({
    active,
    accent,
    onClick,
    children,
}: {
    active: boolean;
    accent: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={active}
            onClick={onClick}
            className={cn(
                'rounded-[10px] border px-3 py-[9px] text-[12px] font-semibold',
                active ? '' : 'border-rz-border bg-rz-surface text-rz-body',
            )}
            style={
                active
                    ? {
                          borderColor: accent,
                          color: accent,
                          background: `${accent}14`,
                      }
                    : undefined
            }
        >
            {children}
        </button>
    );
}

function Check({
    checked,
    onChange,
    accent,
    error,
    children,
}: {
    checked: boolean;
    onChange: (checked: boolean) => void;
    accent: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label className="flex cursor-pointer gap-2.5 text-[12.5px] leading-[1.45] text-rz-body">
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={(event) => onChange(event.target.checked)}
                    className="mt-0.5 size-5 shrink-0"
                    style={{ accentColor: accent }}
                />
                <span>{children}</span>
            </label>
            <FieldError message={error} />
        </div>
    );
}

function PeopleList({
    title,
    people,
    accent,
    errors,
    field,
    onChange,
}: {
    title: string;
    people: RoleApplicationPerson[];
    accent: string;
    errors: Errors;
    field: 'directors';
    onChange: (people: RoleApplicationPerson[]) => void;
}) {
    const { t } = useTranslation();
    const update = (index: number, change: Partial<RoleApplicationPerson>) =>
        onChange(
            people.map((person, at) =>
                at === index ? { ...person, ...change } : person,
            ),
        );

    return (
        <div>
            <p className={cn(LABEL, 'mt-2')}>{title}</p>
            <Flag id={field} />
            <FieldError message={errors[field]} />
            <div className="flex flex-col gap-2.5">
                {people.map((person, index) => (
                    <div
                        key={index}
                        className={cn(CARD, 'flex flex-col gap-2.5')}
                    >
                        <Field
                            id={`${field}_${index}_name`}
                            label={t('role.apply.people.full_name')}
                            value={person.full_name}
                            error={errors[`${field}.${index}.full_name`]}
                            onChange={(value) =>
                                update(index, { full_name: value })
                            }
                        />
                        <div className="flex gap-2">
                            {(['national_id', 'passport'] as const).map(
                                (type) => (
                                    <Chip
                                        key={type}
                                        accent={accent}
                                        active={person.id_type === type}
                                        onClick={() =>
                                            update(index, { id_type: type })
                                        }
                                    >
                                        {t(`role.apply.people.${type}`)}
                                    </Chip>
                                ),
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-2.5">
                            <Field
                                id={`${field}_${index}_id`}
                                label={t('role.apply.people.id_number')}
                                value={person.id_number}
                                error={errors[`${field}.${index}.id_number`]}
                                onChange={(value) =>
                                    update(index, { id_number: value })
                                }
                            />
                            <Field
                                id={`${field}_${index}_nationality`}
                                label={t('role.apply.people.nationality')}
                                value={person.nationality}
                                error={errors[`${field}.${index}.nationality`]}
                                onChange={(value) =>
                                    update(index, { nationality: value })
                                }
                            />
                        </div>
                        {people.length > 1 && (
                            <button
                                type="button"
                                onClick={() =>
                                    onChange(
                                        people.filter((_, at) => at !== index),
                                    )
                                }
                                className="self-start text-[12px] font-semibold text-rz-danger-text"
                            >
                                {t('role.apply.people.remove')}
                            </button>
                        )}
                    </div>
                ))}
            </div>
            {people.length < 12 && (
                <button
                    type="button"
                    onClick={() => onChange([...people, blankPerson(false)])}
                    className="mt-2.5 w-full rounded-2xl border-[1.5px] border-dashed border-rz-border py-3 text-[13px] font-semibold"
                    style={{ color: accent }}
                >
                    {t('role.apply.people.add_director')}
                </button>
            )}
        </div>
    );
}

/** Whether an owner is that director: the same ID number and the same name. */
function isSamePerson(
    owner: RoleApplicationPerson,
    director: RoleApplicationPerson,
): boolean {
    const id = (person: RoleApplicationPerson) =>
        person.id_number.replace(/\s+/g, '').toUpperCase();
    const name = (person: RoleApplicationPerson) =>
        person.full_name.trim().toLowerCase();

    return (
        id(director) !== '' &&
        id(owner) === id(director) &&
        name(owner) === name(director)
    );
}

/** The sum of the shares typed so far, leaving out any share that isn't a plain number such as "3e1". */
function shareTotal(owners: RoleApplicationPerson[]): number {
    return owners.reduce((total, owner) => {
        const typed = owner.ownership_pct ?? '';
        const share = /^\d*(\.\d*)?$/.test(typed)
            ? Number.parseFloat(typed)
            : Number.NaN;

        return Number.isFinite(share) ? total + share : total;
    }, 0);
}

/**
 * An owner picked from the directors follows that director: correcting the director's name, ID or
 * nationality corrects the owner too, so the two never drift apart.
 */
function followDirectors(
    before: RoleApplicationPerson[],
    after: RoleApplicationPerson[],
    owners: RoleApplicationPerson[],
): RoleApplicationPerson[] {
    if (before.length !== after.length) {
        return owners;
    }

    const changed = before.findIndex((director, at) => director !== after[at]);

    if (changed === -1) {
        return owners;
    }

    const { full_name, id_type, id_number, nationality } = after[changed];

    return owners.map((owner) =>
        isSamePerson(owner, before[changed])
            ? { ...owner, full_name, id_type, id_number, nationality }
            : owner,
    );
}

/**
 * Owners of 25% or more (Erastus, 24 Nov 2026, #761). A director who also owns shares is picked from
 * the directors, not typed again; only an owner who isn't a director is typed in. The running total
 * shows as shares are typed, and once it reaches 100% no one else can be added.
 */
function OwnersList({
    owners,
    directors,
    accent,
    errors,
    onChange,
}: {
    owners: RoleApplicationPerson[];
    directors: RoleApplicationPerson[];
    accent: string;
    errors: Errors;
    onChange: (owners: RoleApplicationPerson[]) => void;
}) {
    const { t } = useTranslation();
    const [unreadable, setUnreadable] = useState<Record<number, boolean>>({});
    const total = shareTotal(owners);
    const full = total >= 100;
    const remaining = Math.max(0, 100 - total);
    const candidates = directors.filter(
        (director) =>
            director.full_name.trim() !== '' &&
            director.id_number.trim() !== '' &&
            !owners.some((owner) => isSamePerson(owner, director)),
    );
    const update = (index: number, change: Partial<RoleApplicationPerson>) =>
        onChange(
            owners.map((owner, at) =>
                at === index ? { ...owner, ...change } : owner,
            ),
        );
    const add = (person: RoleApplicationPerson) =>
        onChange([
            ...owners,
            {
                ...person,
                ownership_pct: remaining >= 25 ? String(remaining) : '',
                pep: false,
            },
        ]);
    const formatted = String(Math.round(total * 100) / 100);

    return (
        <div>
            <p className={cn(LABEL, 'mt-2')}>{t('role.apply.people.owners')}</p>
            <Flag id="owners" />
            <FieldError message={errors.owners} />
            <div className="flex flex-col gap-2.5">
                {owners.map((owner, index) => {
                    const director = directors.some((candidate) =>
                        isSamePerson(owner, candidate),
                    );
                    const shareError =
                        unreadable[index] === true
                            ? t('role.apply.people.share_number')
                            : errors[`owners.${index}.ownership_pct`];

                    return (
                        <div
                            key={index}
                            className={cn(CARD, 'flex flex-col gap-2.5')}
                        >
                            {director ? (
                                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                    <span className="text-[14px] font-semibold">
                                        {owner.full_name}
                                    </span>
                                    <span
                                        className="rounded-full px-2 py-0.5 text-[10.5px] font-semibold uppercase"
                                        style={{
                                            color: accent,
                                            backgroundColor: `${accent}14`,
                                        }}
                                    >
                                        {t('role.apply.people.director_badge')}
                                    </span>
                                    <span className="w-full text-[12px] text-rz-secondary">
                                        {t(
                                            `role.apply.people.${owner.id_type}`,
                                        )}{' '}
                                        {owner.id_number} · {owner.nationality}
                                    </span>
                                    <FieldError
                                        message={
                                            errors[`owners.${index}.id_number`]
                                        }
                                    />
                                </div>
                            ) : (
                                <>
                                    <Field
                                        id={`owners_${index}_name`}
                                        label={t('role.apply.people.full_name')}
                                        value={owner.full_name}
                                        error={
                                            errors[`owners.${index}.full_name`]
                                        }
                                        onChange={(value) =>
                                            update(index, { full_name: value })
                                        }
                                    />
                                    <div className="flex gap-2">
                                        {(
                                            ['national_id', 'passport'] as const
                                        ).map((type) => (
                                            <Chip
                                                key={type}
                                                accent={accent}
                                                active={owner.id_type === type}
                                                onClick={() =>
                                                    update(index, {
                                                        id_type: type,
                                                    })
                                                }
                                            >
                                                {t(`role.apply.people.${type}`)}
                                            </Chip>
                                        ))}
                                    </div>
                                    <div className="grid grid-cols-2 gap-2.5">
                                        <Field
                                            id={`owners_${index}_id`}
                                            label={t(
                                                'role.apply.people.id_number',
                                            )}
                                            value={owner.id_number}
                                            error={
                                                errors[
                                                    `owners.${index}.id_number`
                                                ]
                                            }
                                            onChange={(value) =>
                                                update(index, {
                                                    id_number: value,
                                                })
                                            }
                                        />
                                        <Field
                                            id={`owners_${index}_nationality`}
                                            label={t(
                                                'role.apply.people.nationality',
                                            )}
                                            value={owner.nationality}
                                            error={
                                                errors[
                                                    `owners.${index}.nationality`
                                                ]
                                            }
                                            onChange={(value) =>
                                                update(index, {
                                                    nationality: value,
                                                })
                                            }
                                        />
                                    </div>
                                </>
                            )}
                            <Field
                                id={`owners_${index}_share`}
                                label={t('role.apply.people.share')}
                                value={owner.ownership_pct ?? ''}
                                inputMode="decimal"
                                error={shareError}
                                onChange={(value) => {
                                    // A share is a number. A decimal comma reads as a point; anything
                                    // else (letters, a minus, a second separator) stays exactly as typed
                                    // and is flagged, never rewritten into another number, so a save
                                    // sends what the field shows and the server refuses it too.
                                    const share = value.replace(',', '.');

                                    setUnreadable({
                                        ...unreadable,
                                        [index]: !/^\d*(\.\d*)?$/.test(share),
                                    });
                                    update(index, { ownership_pct: share });
                                }}
                            />
                            <Check
                                accent={accent}
                                checked={owner.pep === true}
                                onChange={(checked) =>
                                    update(index, { pep: checked })
                                }
                            >
                                {t('role.apply.people.pep')}
                            </Check>
                            <button
                                type="button"
                                onClick={() =>
                                    onChange(
                                        owners.filter((_, at) => at !== index),
                                    )
                                }
                                className="self-start text-[12px] font-semibold text-rz-danger-text"
                            >
                                {t('role.apply.people.remove')}
                            </button>
                        </div>
                    );
                })}
            </div>
            <p
                aria-live="polite"
                className={cn(
                    'mt-2.5 text-[12.5px] font-semibold',
                    total > 100 ? 'text-rz-danger-text' : 'text-rz-secondary',
                )}
            >
                {t('role.apply.people.total', { total: formatted })}
                {total > 100 && <> · {t('role.apply.people.total_over')}</>}
            </p>
            {full
                ? // Over 100% the warning above says what to do; "no more owners" is only for exactly 100%.
                  total <= 100 && (
                      <p className="mt-1.5 text-[12px] text-rz-secondary">
                          {t('role.apply.people.full')}
                      </p>
                  )
                : owners.length < 12 && (
                      <>
                          {candidates.length > 0 && (
                              <div className="mt-2.5">
                                  <p className="text-[12.5px] text-rz-secondary">
                                      {t('role.apply.people.pick_director')}
                                  </p>
                                  <div className="mt-1.5 flex flex-wrap gap-2">
                                      {candidates.map((director) => (
                                          <button
                                              key={director.id_number}
                                              type="button"
                                              onClick={() => add(director)}
                                              className="rounded-full border-[1.5px] px-3.5 py-2 text-[13px] font-semibold"
                                              style={{
                                                  color: accent,
                                                  borderColor: accent,
                                              }}
                                          >
                                              + {director.full_name}
                                          </button>
                                      ))}
                                  </div>
                              </div>
                          )}
                          <button
                              type="button"
                              onClick={() => add(blankPerson(true))}
                              className="mt-2.5 w-full rounded-2xl border-[1.5px] border-dashed border-rz-border py-3 text-[13px] font-semibold"
                              style={{ color: accent }}
                          >
                              {t('role.apply.people.add_other_owner')}
                          </button>
                      </>
                  )}
        </div>
    );
}

/**
 * A document slot as a file picker. While the file travels it shows how much has gone; every other
 * tile waits, since each upload moves the application's revision.
 */
function UploadTile({
    slot,
    label,
    hint,
    accent,
    uploaded,
    sending,
    disabled,
    error,
    onPick,
}: {
    slot: string;
    label: string;
    hint?: string;
    accent: string;
    uploaded?: { filename: string; media_type: string };
    sending: Sending | null;
    disabled: boolean;
    error?: string;
    onPick: (file: File) => void;
}) {
    const { t } = useTranslation();
    const mine = sending?.slot === slot;

    return (
        <div>
            <label
                aria-busy={mine || undefined}
                className={cn(
                    'relative flex items-center gap-3 rounded-[14px] border-[1.5px] bg-rz-surface px-3.5 py-3',
                    uploaded
                        ? 'border-solid border-rz-border'
                        : 'border-dashed border-rz-border',
                    error && 'border-solid border-[#e5484d]',
                    flagged(use(Flags), `documents.${slot}`) &&
                        'border-solid border-rz-warning-text ring-2 ring-rz-warning-tint',
                    sending === null && !disabled
                        ? 'cursor-pointer'
                        : 'cursor-wait',
                )}
            >
                <input
                    type="file"
                    accept="image/*,application/pdf"
                    aria-label={label}
                    disabled={disabled || sending !== null}
                    className="sr-only"
                    onChange={(event) => {
                        const file = event.target.files?.[0];
                        event.target.value = '';

                        if (file) {
                            onPick(file);
                        }
                    }}
                />
                <span
                    aria-hidden
                    className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] font-bold"
                    style={{
                        color: uploaded ? '#17795a' : accent,
                        background: uploaded
                            ? 'rgba(29,158,117,.1)'
                            : `${accent}14`,
                    }}
                >
                    {uploaded ? '✓' : '+'}
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block text-[13.5px] font-semibold text-rz-ink">
                        {label}
                    </span>
                    <span className="block truncate text-[11.5px] text-rz-secondary">
                        {uploaded
                            ? `${uploaded.filename} · ${t('role.apply.document.replace')}`
                            : t('role.apply.document.tap')}
                    </span>
                    {hint && (
                        <span className="mt-0.5 block text-[11.5px] text-rz-muted">
                            {hint}
                        </span>
                    )}
                </span>
                {mine && <UploadStatus percent={sending.percent} overlay />}
            </label>
            {uploaded && (
                <a
                    href={ownDocument({ slot }).url}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-1 inline-block text-[11.5px] font-semibold text-rz-accent-text"
                >
                    {t('role.apply.document.view')}
                </a>
            )}
            <Flag id={`documents.${slot}`} />
            <FieldError message={error} />
        </div>
    );
}

function Summary({
    title,
    rows,
    onEdit,
}: {
    title: string;
    rows: [string, string][];
    onEdit: () => void;
}) {
    const { t } = useTranslation();

    return (
        <div className={CARD}>
            <div className="mb-1.5 flex justify-between">
                <span className="text-[12px] font-bold tracking-[.05em] text-rz-secondary uppercase">
                    {title}
                </span>
                <button
                    type="button"
                    onClick={onEdit}
                    className="text-[12px] font-semibold text-rz-accent-text"
                >
                    {t('role.apply.edit')}
                </button>
            </div>
            {rows.map(([left, right]) => (
                <div
                    key={`${left}-${right}`}
                    className="flex justify-between gap-2.5 py-0.5 text-[12.5px]"
                >
                    <span className="text-rz-secondary">{left}</span>
                    <span className="text-right font-semibold text-rz-ink">
                        {right}
                    </span>
                </div>
            ))}
        </div>
    );
}

/** What the server says is still missing, listed together on the review step. */
function ProblemList({ errors }: { errors: Errors }) {
    const messages = Object.entries(errors)
        .filter(([key]) => key !== 'form' && key !== 'declared')
        .map(([, message]) => message);

    return messages.length === 0 ? null : (
        <ErrorBanner>
            <span className="flex flex-col gap-1">
                {messages.map((message, index) => (
                    <span key={index}>{message}</span>
                ))}
            </span>
        </ErrorBanner>
    );
}

function PrimaryButton({
    accent,
    disabled,
    onClick,
    children,
}: {
    accent: string;
    disabled: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className="flex h-[52px] w-full items-center justify-center rounded-2xl text-[15px] font-semibold text-white disabled:opacity-60 lg:w-auto lg:min-w-[240px] lg:px-10"
            style={{ background: accent }}
        >
            {children}
        </button>
    );
}

/** The answers and documents Compliance named, each with its word on what to fix. */
function RequestedItems({
    items,
}: {
    items: { key: string; label: string; note: string }[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <p className="mt-2 text-[12.5px] font-semibold text-rz-ink">
                {t('role.apply.flag.list_title')}
            </p>
            <ul className="mt-1 list-disc space-y-0.5 pl-5 text-[13px] text-rz-body">
                {items.map((item) => (
                    <li key={item.key}>
                        <span className="font-semibold text-rz-ink">
                            {item.label}
                        </span>
                        {item.note !== '' && `: ${item.note}`}
                    </li>
                ))}
            </ul>
            <p className="mt-2 text-[12px] text-rz-secondary">
                {t('role.apply.flag.still_editable')}
            </p>
        </>
    );
}
