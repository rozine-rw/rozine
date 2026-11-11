import { Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { ReactNode } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime, formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type {
    BusinessProspectusProps,
    ProspectusDocument,
    ProspectusForecastMonth,
    ProspectusInputs,
    ProspectusProfile,
} from '@/types/business';
import type { Money } from '@/types/money';

const INPUT =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[11px] text-sm text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border disabled:text-rz-secondary';

const CARD =
    'rounded-2xl border border-rz-border bg-rz-surface px-4 py-4 sm:px-5';

const rwf = (amount: string): Money => ({ currency: 'RWF', amount });

const LABEL = 'mb-1.5 block text-xs font-semibold text-rz-label uppercase';

const ROLES = ['representative', 'director', 'owner'] as const;

/** The months of the term, starting the month after `serverTime`, keeping any month already written. */
export function forecastMonths(
    current: ProspectusForecastMonth[],
    term: number,
    serverTime: string,
): ProspectusForecastMonth[] {
    if (current.length === term) {
        return current;
    }

    const start = new Date(serverTime);

    return Array.from({ length: term }, (_, index) => {
        const date = new Date(
            Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 1 + index),
        );
        const month = `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`;

        return (
            current.find((row) => row.month === month) ?? {
                month,
                revenue: '',
                costs: '',
                contract_document_id: null,
            }
        );
    });
}

function Section({
    id,
    title,
    fixes,
    children,
}: {
    id: string;
    title: string;
    fixes: string[];
    children: ReactNode;
}) {
    return (
        <section aria-labelledby={`${id}-title`} className={CARD}>
            <h2
                id={`${id}-title`}
                className="text-[15px] font-semibold text-rz-ink"
            >
                {title}
            </h2>
            {fixes.map((fix) => (
                <p
                    key={fix}
                    className="mt-2 rounded-lg bg-rz-warning-tint px-3 py-2 text-[12.5px] text-rz-warning-text"
                >
                    {fix}
                </p>
            ))}
            <div className="mt-3 flex flex-col gap-3">{children}</div>
        </section>
    );
}

/** A choice of the uploaded documents of one kind, or none. */
function DocumentPicker({
    label,
    documents,
    value,
    none,
    onChange,
}: {
    label: string;
    documents: ProspectusDocument[];
    value: string | null;
    none: string;
    onChange: (id: string | null) => void;
}) {
    return (
        <select
            aria-label={label}
            value={value ?? ''}
            onChange={(event) =>
                onChange(event.target.value === '' ? null : event.target.value)
            }
            className={INPUT}
        >
            <option value="">{none}</option>
            {documents.map((document) => (
                <option key={document.id} value={document.id}>
                    {document.filename}
                </option>
            ))}
        </select>
    );
}

function Fact({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-3 border-b border-rz-divider py-2 text-sm last:border-b-0">
            <dt className="text-rz-secondary">{label}</dt>
            <dd className="font-semibold text-rz-ink tabular-nums">{value}</dd>
        </div>
    );
}

/**
 * The Listing Builder (BRS v3.0 §19, PRO-R01 to PRO-R04). The Business writes the parts of its
 * prospectus only it can tell; principal, term, instalment, the 3% platform fee and verified
 * revenue come from its accepted application and are shown, never typed. Every gate the server
 * judged appears beside its section with the exact fix. Saving keeps a new draft version; once
 * gates P-G01 to P-G11 pass, the official representative signs, which locks the prospectus under
 * its fingerprint. Nothing is shown as signed before the server says so.
 */
export default function BusinessProspectus({
    server_time,
    shell_links,
    application,
    version,
    inputs,
    signed,
    unavailable,
    facts,
    evaluation,
    documents,
    actions,
    links,
}: BusinessProspectusProps) {
    const { t, locale } = useTranslation();
    const editable = actions.save !== null;
    const signAction = actions.sign;
    const uploadAction = actions.upload;
    const form = useForm<ProspectusInputs>({
        ...inputs,
        forecast:
            facts === null
                ? inputs.forecast
                : forecastMonths(
                      inputs.forecast,
                      facts.term_months,
                      server_time,
                  ),
    });
    const signing = useForm({});
    const uploading = useForm<{
        kind: ProspectusDocument['kind'];
        file: File | null;
    }>({ kind: 'quote', file: null });
    const uploadForm = useRef<HTMLFormElement>(null);
    const quotes = documents.filter((document) => document.kind === 'quote');
    const contracts = documents.filter(
        (document) => document.kind === 'contract',
    );
    const { data, errors } = form;
    const fieldErrors = errors as Record<string, string | undefined>;
    const gates = evaluation?.gates ?? [];
    const fixes = (section: string) =>
        gates.flatMap((gate) =>
            gate.section === section && !gate.passed && gate.id !== 'P-G12'
                ? [gate.fix ?? '']
                : [],
        );
    const passed = gates.filter((gate) => gate.passed).length;

    const setRow = <
        K extends 'use_of_funds' | 'forecast' | 'profiles' | 'risks',
    >(
        key: K,
        index: number,
        patch: Partial<ProspectusInputs[K][number]>,
    ) =>
        form.setData((current) => ({
            ...current,
            [key]: current[key].map((row, at) =>
                at === index ? { ...row, ...patch } : row,
            ),
        }));
    const removeRow = (
        key: 'use_of_funds' | 'profiles' | 'risks',
        index: number,
    ) =>
        form.setData((current) => ({
            ...current,
            [key]: current[key].filter((_, at) => at !== index),
        }));

    const save = (action: { url: string }) => {
        form.transform((current) => ({
            ...current,
            request_id: crypto.randomUUID(),
            expected_version: version,
        }));
        form.put(action.url, { preserveScroll: true });
    };
    const upload = (action: { url: string }) => {
        uploading.transform((current) => ({
            ...current,
            request_id: crypto.randomUUID(),
        }));
        uploading.post(action.url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploading.setData('file', null);
                uploadForm.current?.reset();
            },
        });
    };
    const sign = (action: { url: string }) => {
        signing.transform(() => ({
            request_id: crypto.randomUUID(),
            expected_version: version,
        }));
        signing.post(action.url, { preserveScroll: true });
    };

    return (
        <BusinessShell
            title={t('business.prospectus.title')}
            tab="home"
            links={shell_links}
            showTabBar={false}
        >
            <div className="mx-auto flex w-full max-w-[760px] flex-col gap-4 px-4 py-5">
                <header className="flex items-start justify-between gap-3">
                    <div>
                        <Link
                            href={links.close}
                            className="text-[13px] font-semibold text-rz-accent"
                        >
                            {t('business.prospectus.back')}
                        </Link>
                        <h1 className="mt-1 text-xl font-semibold text-rz-ink">
                            {t('business.prospectus.title')}
                        </h1>
                        <p className="text-sm text-rz-secondary">
                            {application.title}
                        </p>
                    </div>
                    <span
                        className={cn(
                            'shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                            signed === null
                                ? 'bg-rz-field text-rz-secondary'
                                : 'bg-rz-positive-tint text-rz-positive',
                        )}
                    >
                        {signed === null
                            ? t('business.prospectus.draft', { version })
                            : t('business.prospectus.signed', {
                                  version: signed.version,
                              })}
                    </span>
                </header>

                {signed !== null ? (
                    <p
                        role="status"
                        className="rounded-xl bg-rz-positive-tint px-4 py-3 text-sm text-rz-positive"
                    >
                        {t('business.prospectus.locked', {
                            date: formatDateTime(signed.signed_at, locale),
                            fingerprint: signed.fingerprint.slice(0, 12),
                        })}
                    </p>
                ) : null}

                {unavailable !== null ? (
                    <p
                        role="status"
                        className="rounded-xl bg-rz-warning-tint px-4 py-3 text-sm text-rz-warning-text"
                    >
                        {t('business.prospectus.unavailable', {
                            code: unavailable,
                        })}
                    </p>
                ) : null}

                {facts !== null ? (
                    <section aria-labelledby="facts-title" className={CARD}>
                        <h2
                            id="facts-title"
                            className="text-[15px] font-semibold text-rz-ink"
                        >
                            {t('business.prospectus.facts.title')}
                        </h2>
                        <p className="mt-1 text-[12.5px] text-rz-secondary">
                            {t('business.prospectus.facts.note')}
                        </p>
                        <dl className="mt-2">
                            <Fact
                                label={t('business.prospectus.facts.principal')}
                                value={formatRwf(rwf(facts.principal))}
                            />
                            <Fact
                                label={t('business.prospectus.facts.fee')}
                                value={formatRwf(rwf(facts.platform_fee))}
                            />
                            <Fact
                                label={t('business.prospectus.facts.net')}
                                value={formatRwf(rwf(facts.net_of_fee))}
                            />
                            <Fact
                                label={t('business.prospectus.facts.term')}
                                value={t('business.prospectus.facts.months', {
                                    count: facts.term_months,
                                })}
                            />
                            <Fact
                                label={t(
                                    'business.prospectus.facts.instalment',
                                )}
                                value={formatRwf(rwf(facts.instalment))}
                            />
                            <Fact
                                label={t('business.prospectus.facts.revenue')}
                                value={formatRwf(
                                    rwf(facts.verified_monthly_revenue),
                                )}
                            />
                        </dl>
                    </section>
                ) : null}

                {evaluation !== null ? (
                    <section aria-labelledby="checks-title" className={CARD}>
                        <h2
                            id="checks-title"
                            className="text-[15px] font-semibold text-rz-ink"
                        >
                            {t('business.prospectus.checks.title', {
                                passed,
                                total: gates.length,
                            })}
                        </h2>
                        <ul className="mt-2 flex flex-col gap-1.5">
                            {gates.map((gate) => (
                                <li
                                    key={gate.id}
                                    className="flex gap-2 text-[13px]"
                                >
                                    <span
                                        aria-hidden
                                        className={
                                            gate.passed
                                                ? 'text-rz-positive'
                                                : 'text-rz-warning-text'
                                        }
                                    >
                                        {gate.passed ? '✓' : '!'}
                                    </span>
                                    <span className="text-rz-ink">
                                        <span className="font-semibold">
                                            {gate.id}
                                        </span>{' '}
                                        {gate.passed
                                            ? t(
                                                  'business.prospectus.checks.passed',
                                              )
                                            : gate.fix}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                {uploadAction !== null || documents.length > 0 ? (
                    <section aria-labelledby="evidence-title" className={CARD}>
                        <h2
                            id="evidence-title"
                            className="text-[15px] font-semibold text-rz-ink"
                        >
                            {t('business.prospectus.evidence.title')}
                        </h2>
                        <p className="mt-1 text-[12.5px] text-rz-secondary">
                            {t('business.prospectus.evidence.note')}
                        </p>
                        {documents.length === 0 ? (
                            <p className="mt-3 text-[13px] text-rz-secondary">
                                {t('business.prospectus.evidence.empty')}
                            </p>
                        ) : (
                            <ul className="mt-3 flex flex-col">
                                {documents.map((document) => (
                                    <li
                                        key={document.id}
                                        className="flex items-center justify-between gap-3 border-b border-rz-divider py-2 text-[13px] last:border-b-0"
                                    >
                                        <span className="min-w-0 truncate font-semibold text-rz-ink">
                                            {document.filename}
                                        </span>
                                        <span className="shrink-0 text-rz-secondary">
                                            {document.kind === 'quote'
                                                ? t(
                                                      'business.prospectus.evidence.kind_quote',
                                                  )
                                                : t(
                                                      'business.prospectus.evidence.kind_contract',
                                                  )}
                                            {' · '}
                                            {formatDateTime(
                                                document.uploaded_at,
                                                locale,
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {uploadAction !== null ? (
                            <form
                                ref={uploadForm}
                                aria-label={t(
                                    'business.prospectus.evidence.title',
                                )}
                                aria-busy={uploading.processing || undefined}
                                className="mt-3 grid grid-cols-1 items-end gap-2 sm:grid-cols-[1fr_2fr_auto]"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    upload(uploadAction);
                                }}
                            >
                                <select
                                    aria-label={t(
                                        'business.prospectus.evidence.kind',
                                    )}
                                    value={uploading.data.kind}
                                    onChange={(event) =>
                                        uploading.setData(
                                            'kind',
                                            event.target.value === 'contract'
                                                ? 'contract'
                                                : 'quote',
                                        )
                                    }
                                    className={INPUT}
                                >
                                    <option value="quote">
                                        {t(
                                            'business.prospectus.evidence.kind_quote',
                                        )}
                                    </option>
                                    <option value="contract">
                                        {t(
                                            'business.prospectus.evidence.kind_contract',
                                        )}
                                    </option>
                                </select>
                                <input
                                    type="file"
                                    accept="application/pdf,image/png,image/jpeg"
                                    aria-label={t(
                                        'business.prospectus.evidence.file',
                                    )}
                                    onChange={(event) =>
                                        uploading.setData(
                                            'file',
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="text-[13px] text-rz-secondary"
                                />
                                <button
                                    type="submit"
                                    disabled={
                                        uploading.data.file === null ||
                                        uploading.processing
                                    }
                                    className="h-[44px] rounded-xl border border-rz-border px-4 text-[13.5px] font-semibold text-rz-accent disabled:opacity-50"
                                >
                                    {uploading.processing
                                        ? t(
                                              'business.prospectus.evidence.uploading',
                                          )
                                        : t(
                                              'business.prospectus.evidence.upload',
                                          )}
                                </button>
                                <FieldError id="prospectus-evidence-error">
                                    {uploading.errors.kind ??
                                        uploading.errors.file}
                                </FieldError>
                            </form>
                        ) : null}
                    </section>
                ) : null}

                <form
                    aria-label={t('business.prospectus.title')}
                    className="flex flex-col gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (actions.save !== null) {
                            save(actions.save);
                        }
                    }}
                >
                    <fieldset
                        disabled={!editable}
                        className="flex flex-col gap-4"
                    >
                        <Section
                            id="purpose"
                            title={t('business.prospectus.purpose.title')}
                            fixes={fixes('purpose')}
                        >
                            <label
                                htmlFor="prospectus-purpose"
                                className={LABEL}
                            >
                                {t('business.prospectus.purpose.label')}
                            </label>
                            <textarea
                                id="prospectus-purpose"
                                rows={4}
                                value={data.purpose}
                                onChange={(event) =>
                                    form.setData('purpose', event.target.value)
                                }
                                className={INPUT}
                            />
                            <FieldError id="prospectus-purpose-error">
                                {fieldErrors.purpose}
                            </FieldError>
                            <p className={LABEL}>
                                {t('business.prospectus.funds.title')}
                            </p>
                            {data.use_of_funds.map((line, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 gap-2 sm:grid-cols-[2fr_1fr_1fr_1.5fr_auto]"
                                >
                                    <input
                                        aria-label={t(
                                            'business.prospectus.funds.label',
                                        )}
                                        value={line.label}
                                        onChange={(event) =>
                                            setRow('use_of_funds', index, {
                                                label: event.target.value,
                                            })
                                        }
                                        className={INPUT}
                                    />
                                    <input
                                        aria-label={t(
                                            'business.prospectus.funds.amount',
                                        )}
                                        inputMode="numeric"
                                        value={line.amount}
                                        onChange={(event) =>
                                            setRow('use_of_funds', index, {
                                                amount: event.target.value.replace(
                                                    /\D/gu,
                                                    '',
                                                ),
                                            })
                                        }
                                        className={INPUT}
                                    />
                                    <input
                                        aria-label={t(
                                            'business.prospectus.funds.quote',
                                        )}
                                        inputMode="numeric"
                                        value={line.quote_amount ?? ''}
                                        onChange={(event) => {
                                            const digits =
                                                event.target.value.replace(
                                                    /\D/gu,
                                                    '',
                                                );
                                            setRow('use_of_funds', index, {
                                                quote_amount:
                                                    digits === ''
                                                        ? null
                                                        : digits,
                                            });
                                        }}
                                        className={INPUT}
                                    />
                                    <DocumentPicker
                                        label={t(
                                            'business.prospectus.funds.quote_document',
                                        )}
                                        documents={quotes}
                                        value={line.quote_document_id}
                                        none={t(
                                            'business.prospectus.evidence.none',
                                        )}
                                        onChange={(id) =>
                                            setRow('use_of_funds', index, {
                                                quote_document_id: id,
                                            })
                                        }
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            removeRow('use_of_funds', index)
                                        }
                                        className="text-[13px] font-semibold text-rz-secondary"
                                    >
                                        {t('business.prospectus.remove')}
                                    </button>
                                </div>
                            ))}
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData('use_of_funds', [
                                        ...data.use_of_funds,
                                        {
                                            label: '',
                                            amount: '',
                                            quote_amount: null,
                                            quote_document_id: null,
                                        },
                                    ])
                                }
                                className="self-start text-[13px] font-semibold text-rz-accent"
                            >
                                {t('business.prospectus.funds.add')}
                            </button>
                        </Section>

                        <Section
                            id="forecast"
                            title={t('business.prospectus.forecast.title')}
                            fixes={fixes('forecast')}
                        >
                            {data.forecast.map((month, index) => (
                                <div
                                    key={month.month}
                                    className="grid grid-cols-[5rem_1fr_1fr] items-center gap-2 sm:grid-cols-[5rem_1fr_1fr_1.5fr]"
                                >
                                    <span className="text-[13px] font-semibold text-rz-ink tabular-nums">
                                        {month.month}
                                    </span>
                                    <input
                                        aria-label={t(
                                            'business.prospectus.forecast.revenue',
                                            { month: month.month },
                                        )}
                                        inputMode="numeric"
                                        value={month.revenue}
                                        onChange={(event) =>
                                            setRow('forecast', index, {
                                                revenue:
                                                    event.target.value.replace(
                                                        /\D/gu,
                                                        '',
                                                    ),
                                            })
                                        }
                                        className={INPUT}
                                    />
                                    <input
                                        aria-label={t(
                                            'business.prospectus.forecast.costs',
                                            { month: month.month },
                                        )}
                                        inputMode="numeric"
                                        value={month.costs}
                                        onChange={(event) =>
                                            setRow('forecast', index, {
                                                costs: event.target.value.replace(
                                                    /\D/gu,
                                                    '',
                                                ),
                                            })
                                        }
                                        className={INPUT}
                                    />
                                    <div className="col-span-3 sm:col-span-1">
                                        <DocumentPicker
                                            label={t(
                                                'business.prospectus.forecast.contract',
                                                { month: month.month },
                                            )}
                                            documents={contracts}
                                            value={month.contract_document_id}
                                            none={t(
                                                'business.prospectus.evidence.none',
                                            )}
                                            onChange={(id) =>
                                                setRow('forecast', index, {
                                                    contract_document_id: id,
                                                })
                                            }
                                        />
                                    </div>
                                </div>
                            ))}
                            <label
                                htmlFor="prospectus-assumptions"
                                className={LABEL}
                            >
                                {t('business.prospectus.forecast.assumptions')}
                            </label>
                            <textarea
                                id="prospectus-assumptions"
                                rows={3}
                                value={data.assumptions}
                                onChange={(event) =>
                                    form.setData(
                                        'assumptions',
                                        event.target.value,
                                    )
                                }
                                className={INPUT}
                            />
                            <label
                                htmlFor="prospectus-downside"
                                className={LABEL}
                            >
                                {t('business.prospectus.forecast.downside')}
                            </label>
                            <textarea
                                id="prospectus-downside"
                                rows={3}
                                value={data.downside}
                                onChange={(event) =>
                                    form.setData('downside', event.target.value)
                                }
                                className={INPUT}
                            />
                        </Section>

                        <Section
                            id="management"
                            title={t('business.prospectus.management.title')}
                            fixes={fixes('management')}
                        >
                            {data.profiles.map((profile, index) => (
                                <div
                                    key={index}
                                    className="flex flex-col gap-2 border-b border-rz-divider pb-3 last:border-b-0"
                                >
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-[2fr_1fr_auto]">
                                        <input
                                            aria-label={t(
                                                'business.prospectus.management.name',
                                            )}
                                            value={profile.name}
                                            onChange={(event) =>
                                                setRow('profiles', index, {
                                                    name: event.target.value,
                                                })
                                            }
                                            className={INPUT}
                                        />
                                        <select
                                            aria-label={t(
                                                'business.prospectus.management.role',
                                            )}
                                            value={profile.role}
                                            onChange={(event) =>
                                                setRow('profiles', index, {
                                                    role: event.target
                                                        .value as ProspectusProfile['role'],
                                                })
                                            }
                                            className={INPUT}
                                        >
                                            {ROLES.map((role) => (
                                                <option key={role} value={role}>
                                                    {t(
                                                        `business.prospectus.management.roles.${role}`,
                                                    )}
                                                </option>
                                            ))}
                                        </select>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                removeRow('profiles', index)
                                            }
                                            className="text-[13px] font-semibold text-rz-secondary"
                                        >
                                            {t('business.prospectus.remove')}
                                        </button>
                                    </div>
                                    <textarea
                                        aria-label={t(
                                            'business.prospectus.management.text',
                                            { name: profile.name },
                                        )}
                                        rows={3}
                                        value={profile.text}
                                        onChange={(event) =>
                                            setRow('profiles', index, {
                                                text: event.target.value,
                                            })
                                        }
                                        className={INPUT}
                                    />
                                </div>
                            ))}
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData('profiles', [
                                        ...data.profiles,
                                        {
                                            name: '',
                                            role: 'director',
                                            text: '',
                                        },
                                    ])
                                }
                                className="self-start text-[13px] font-semibold text-rz-accent"
                            >
                                {t('business.prospectus.management.add')}
                            </button>
                        </Section>

                        <Section
                            id="risks"
                            title={t('business.prospectus.risks.title')}
                            fixes={fixes('risks')}
                        >
                            <label htmlFor="prospectus-share" className={LABEL}>
                                {t('business.prospectus.risks.share')}
                            </label>
                            <input
                                id="prospectus-share"
                                inputMode="numeric"
                                value={
                                    data.largest_customer_share_percent ?? ''
                                }
                                onChange={(event) => {
                                    const digits = event.target.value.replace(
                                        /\D/gu,
                                        '',
                                    );
                                    form.setData(
                                        'largest_customer_share_percent',
                                        digits === '' ? null : Number(digits),
                                    );
                                }}
                                className={cn(INPUT, 'max-w-[8rem]')}
                            />
                            <FieldError id="prospectus-share-error">
                                {fieldErrors.largest_customer_share_percent}
                            </FieldError>
                            {data.risks.map((risk, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]"
                                >
                                    <textarea
                                        aria-label={t(
                                            'business.prospectus.risks.text',
                                            { number: index + 1 },
                                        )}
                                        rows={2}
                                        value={risk.text}
                                        onChange={(event) =>
                                            setRow('risks', index, {
                                                text: event.target.value,
                                            })
                                        }
                                        className={INPUT}
                                    />
                                    <div className="flex items-center gap-3">
                                        <label className="flex items-center gap-2 text-[12.5px] text-rz-secondary">
                                            <input
                                                type="checkbox"
                                                checked={risk.concentration}
                                                onChange={(event) =>
                                                    setRow('risks', index, {
                                                        concentration:
                                                            event.target
                                                                .checked,
                                                    })
                                                }
                                            />
                                            {t(
                                                'business.prospectus.risks.concentration',
                                            )}
                                        </label>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                removeRow('risks', index)
                                            }
                                            className="text-[13px] font-semibold text-rz-secondary"
                                        >
                                            {t('business.prospectus.remove')}
                                        </button>
                                    </div>
                                </div>
                            ))}
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData('risks', [
                                        ...data.risks,
                                        { text: '', concentration: false },
                                    ])
                                }
                                className="self-start text-[13px] font-semibold text-rz-accent"
                            >
                                {t('business.prospectus.risks.add')}
                            </button>
                        </Section>
                    </fieldset>

                    {editable ? (
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="h-[50px] w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:opacity-80"
                        >
                            {form.processing
                                ? t('business.prospectus.saving')
                                : t('business.prospectus.save')}
                        </button>
                    ) : null}
                </form>

                {signed === null ? (
                    <Section
                        id="declaration"
                        title={t('business.prospectus.declaration.title')}
                        fixes={[]}
                    >
                        <p className="text-sm text-rz-ink">
                            {t('business.prospectus.declaration.text')}
                        </p>
                        <FieldError id="prospectus-declaration-error">
                            {(
                                signing.errors as Record<
                                    string,
                                    string | undefined
                                >
                            ).declaration ?? fieldErrors.declaration}
                        </FieldError>
                        <button
                            type="button"
                            onClick={
                                signAction === null
                                    ? undefined
                                    : () => sign(signAction)
                            }
                            disabled={
                                actions.sign === null ||
                                form.isDirty ||
                                signing.processing
                            }
                            className="h-[50px] w-full rounded-xl bg-rz-accent-fill text-[14.5px] font-semibold text-white disabled:opacity-50"
                        >
                            {signing.processing
                                ? t('business.prospectus.signing')
                                : t('business.prospectus.sign')}
                        </button>
                        <p className="text-[12.5px] text-rz-secondary">
                            {actions.sign !== null
                                ? form.isDirty
                                    ? t(
                                          'business.prospectus.declaration.save_first',
                                      )
                                    : t('business.prospectus.declaration.ready')
                                : t('business.prospectus.declaration.waiting')}
                        </p>
                    </Section>
                ) : null}
            </div>
        </BusinessShell>
    );
}
