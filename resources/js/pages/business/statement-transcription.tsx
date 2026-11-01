import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { BusinessShell } from '@/components/business/business-shell';
import { DetailSheet } from '@/components/business/detail-sheet';
import { HomeBody } from '@/components/business/home/home-body';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatMonthYear } from '@/lib/rozine/format';
import type {
    Account,
    Direction,
    Problem,
    TranscriptionForm,
} from '@/lib/rozine/statement-transcription';
import {
    CLASSIFICATIONS,
    MAX_MONTHS,
    cleanFrancs,
    cleanIdentifier,
    computedClosing,
    drawsBefore,
    formFrom,
    isActive,
    monthsBetween,
    newKey,
    openingOf,
    pairTransfer,
    payloadOf,
    problemsOf,
    sheetKey,
    sheetOf,
    sheetProblems,
    transferCandidates,
    updateRow,
} from '@/lib/rozine/statement-transcription';
import { cn } from '@/lib/utils';
import type {
    BusinessStatementTranscriptionProps,
    StatementClassification,
} from '@/types/business';
import type { RouteAction } from '@/types/routing';

const FIELD =
    'w-full rounded-xl border border-rz-field-border bg-rz-field px-3 py-2.5 text-[14px] font-semibold text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border disabled:opacity-70';
const LABEL = 'mb-1 block text-[11px] font-semibold text-rz-label uppercase';
const SECTION =
    'mt-5 text-xs font-bold tracking-[.04em] text-rz-slate uppercase';
const BUTTON =
    'rounded-xl border border-rz-border px-3 py-2 text-[12.5px] font-semibold text-rz-accent-app-text';

/** Grouped whole francs: "1,250,000". */
const francs = (value: bigint): string =>
    value.toString().replace(/\B(?=(\d{3})+(?!\d))/gu, ',');

/** The month before today, as `YYYY-MM`: the latest month a statement can be complete for. */
const lastCompleteMonth = (now: Date): string => {
    const date = new Date(
        Date.UTC(now.getUTCFullYear(), now.getUTCMonth() - 1, 1),
    );

    return date.toISOString().slice(0, 7);
};

/**
 * "Enter your statement figures" (#396, contract initial-statement-audit-1 §5). The representative
 * copies each month of each bank or Mobile Money account from the statements they uploaded, and
 * saves them for an Audit Partner to verify against the originals. The form checks what the
 * server's reconciliation will check; saving verifies nothing and counts no month yet.
 */
export default function BusinessStatementTranscription({
    draft,
    identity_context_revision,
    statements,
    home,
    links,
}: BusinessStatementTranscriptionProps) {
    const { t, locale } = useTranslation();
    const [form, setForm] = useState<TranscriptionForm>(() =>
        formFrom(
            draft.current_transcription?.payload ?? null,
            lastCompleteMonth(new Date()),
        ),
    );
    const [selected, setSelected] = useState<string | null>(null);
    const [reviewed, setReviewed] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const months = monthsBetween(form.first, form.last);
    const month =
        selected !== null && months.includes(selected) ? selected : months[0];
    const problems = problemsOf(form);
    const monthLabel = (value: string) =>
        formatMonthYear(`${value}-01T12:00:00Z`, locale);
    const accountLabel = (account: Account) =>
        account.name === ''
            ? t('business.transcription.account.unnamed', {
                  number: form.accounts.indexOf(account) + 1,
              })
            : account.name;
    const problemText = (problem: Problem) => {
        const account = form.accounts.find(
            (candidate) => candidate.key === problem.account,
        );
        const text = t(`business.transcription.problem.${problem.code}`, {
            max: MAX_MONTHS,
        });

        return problem.month === undefined
            ? text
            : `${monthLabel(problem.month)} · ${accountLabel(account as Account)}: ${text}`;
    };

    const change = (next: TranscriptionForm) => {
        setForm(next);
        setReviewed(false);
        setError(null);
    };
    const changeSheet = (
        account: string,
        update: (
            sheet: ReturnType<typeof sheetOf>,
        ) => ReturnType<typeof sheetOf>,
    ) =>
        change({
            ...form,
            sheets: {
                ...form.sheets,
                [sheetKey(month, account)]: update(
                    sheetOf(form, month, account),
                ),
            },
        });
    const changeAccount = (key: string, update: Partial<Account>) =>
        change({
            ...form,
            accounts: form.accounts.map((account) =>
                account.key === key ? { ...account, ...update } : account,
            ),
        });

    const save = (action: RouteAction) =>
        router.post(
            action.url,
            {
                ...payloadOf(form),
                request_id: crypto.randomUUID(),
                identity_context_revision,
                expected_business_revision: draft.business_revision,
                expected_mandate_version: draft.mandate_version,
                expected_evidence_revision: draft.evidence.evidence_revision,
                expected_transcription_id:
                    draft.current_transcription?.id ?? null,
                expected_transcription_sha256:
                    draft.current_transcription?.sha256 ?? null,
                source_binding: draft.evidence,
                reviewed_against_originals: true,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setSaving(true),
                onSuccess: () => setReviewed(false),
                onError: (errors) =>
                    setError(
                        errors.transcription ??
                            Object.values(errors)[0] ??
                            t('business.transcription.failed'),
                    ),
                onFinish: () => setSaving(false),
            },
        );

    const footer =
        draft.save === null ? null : (
            <div className="border-t border-rz-border bg-rz-surface px-5 py-3.5">
                {error !== null && (
                    <p
                        role="alert"
                        className="mb-2.5 flex items-start gap-2 text-[12.5px] text-rz-danger-text"
                    >
                        <Icon name="warning" tone="red" />
                        {error}
                    </p>
                )}
                {problems.length > 0 ? (
                    <div
                        role="status"
                        className="mb-2.5 text-xs text-rz-secondary"
                    >
                        <p className="font-semibold text-rz-ink">
                            {t('business.transcription.to_fix', {
                                count: problems.length,
                            })}
                        </p>
                        <ul className="mt-1 list-disc pl-4">
                            {problems.slice(0, 3).map((problem, index) => (
                                <li key={index}>{problemText(problem)}</li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <label className="mb-2.5 flex items-start gap-2 text-[12.5px] leading-[1.5] text-rz-ink">
                        <input
                            type="checkbox"
                            className="mt-0.5"
                            checked={reviewed}
                            onChange={(event) =>
                                setReviewed(event.target.checked)
                            }
                        />
                        {t('business.transcription.reviewed')}
                    </label>
                )}
                <button
                    type="button"
                    onClick={() => save(draft.save as RouteAction)}
                    disabled={problems.length > 0 || !reviewed || saving}
                    className="w-full rounded-xl bg-rz-accent-fill px-4 py-3 text-[14px] font-bold text-white disabled:opacity-50"
                >
                    {saving
                        ? t('business.transcription.saving')
                        : t('business.transcription.save')}
                </button>
            </div>
        );

    const sheet = (
        <DetailSheet
            label={t('business.transcription.title')}
            close={links.close}
            closeLabel={t('business.note.close')}
            dismissible={false}
            footer={footer}
        >
            <div className="px-5 pt-[calc(env(safe-area-inset-top)+2px)] pb-10 lg:pt-[18px]">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.close}
                        aria-label={t('business.note.back')}
                        className="flex size-[38px] items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                    >
                        <span aria-hidden>←</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-rz-ink">
                        {t('business.transcription.title')}
                    </h1>
                </div>
                <p className="mt-3 text-[12.5px] leading-[1.55] text-rz-ink">
                    {t('business.transcription.intro')}
                </p>
                {draft.current_transcription !== null && (
                    <p className="mt-2.5 inline-flex rounded-[10px] border border-rz-border px-2 py-0.5 text-[11px] font-semibold text-rz-slate">
                        {t('business.transcription.saved')}
                    </p>
                )}

                {draft.save === null ? (
                    <p
                        role="status"
                        className="mt-4 rounded-2xl border border-rz-border bg-rz-surface p-4 text-[12.5px] leading-[1.55] text-rz-secondary"
                    >
                        {t(
                            statements.documents.length === 0
                                ? 'business.transcription.no_statements'
                                : 'business.transcription.view_only',
                        )}
                    </p>
                ) : (
                    <>
                        <h2 className={SECTION}>
                            {t('business.transcription.months.title')}
                        </h2>
                        <div className="mt-2 grid grid-cols-2 gap-2.5">
                            <label>
                                <span className={LABEL}>
                                    {t('business.transcription.months.first')}
                                </span>
                                <input
                                    type="month"
                                    className={FIELD}
                                    value={form.first}
                                    onChange={(event) =>
                                        change({
                                            ...form,
                                            first: event.target.value,
                                        })
                                    }
                                />
                            </label>
                            <label>
                                <span className={LABEL}>
                                    {t('business.transcription.months.last')}
                                </span>
                                <input
                                    type="month"
                                    className={FIELD}
                                    value={form.last}
                                    onChange={(event) =>
                                        change({
                                            ...form,
                                            last: event.target.value,
                                        })
                                    }
                                />
                            </label>
                        </div>
                        <p className="mt-1.5 text-[11px] text-rz-secondary">
                            {t('business.transcription.months.count', {
                                count: months.length,
                                max: MAX_MONTHS,
                            })}
                        </p>

                        <h2 className={SECTION}>
                            {t('business.transcription.accounts.title')}
                        </h2>
                        <p className="mt-1 text-[11px] text-rz-secondary">
                            {t('business.transcription.accounts.hint')}
                        </p>
                        <ul className="mt-2 flex flex-col gap-2.5">
                            {form.accounts.map((account) => (
                                <li
                                    key={account.key}
                                    className="rounded-xl border border-rz-border p-3"
                                >
                                    <label>
                                        <span className={LABEL}>
                                            {t(
                                                'business.transcription.account.name',
                                            )}
                                        </span>
                                        <input
                                            className={FIELD}
                                            value={account.name}
                                            placeholder={t(
                                                'business.transcription.account.placeholder',
                                            )}
                                            onChange={(event) =>
                                                changeAccount(account.key, {
                                                    name: cleanIdentifier(
                                                        event.target.value,
                                                    ),
                                                })
                                            }
                                        />
                                    </label>
                                    <div className="mt-2 grid grid-cols-2 gap-2.5">
                                        <label>
                                            <span className={LABEL}>
                                                {t(
                                                    'business.transcription.account.from',
                                                )}
                                            </span>
                                            <select
                                                className={FIELD}
                                                value={account.from}
                                                onChange={(event) =>
                                                    changeAccount(account.key, {
                                                        from: event.target
                                                            .value,
                                                    })
                                                }
                                            >
                                                {!months.includes(
                                                    account.from,
                                                ) && (
                                                    <option
                                                        value={account.from}
                                                    >
                                                        {account.from}
                                                    </option>
                                                )}
                                                {months.map((value) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {monthLabel(value)}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <label>
                                            <span className={LABEL}>
                                                {t(
                                                    'business.transcription.account.until',
                                                )}
                                            </span>
                                            <select
                                                className={FIELD}
                                                value={account.until ?? ''}
                                                onChange={(event) =>
                                                    changeAccount(account.key, {
                                                        until:
                                                            event.target
                                                                .value === ''
                                                                ? null
                                                                : event.target
                                                                      .value,
                                                    })
                                                }
                                            >
                                                <option value="">
                                                    {t(
                                                        'business.transcription.account.open',
                                                    )}
                                                </option>
                                                {months.map((value) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {monthLabel(value)}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                    </div>
                                    {form.accounts.length > 1 && (
                                        <button
                                            type="button"
                                            className={cn(BUTTON, 'mt-2.5')}
                                            onClick={() =>
                                                change({
                                                    ...form,
                                                    accounts:
                                                        form.accounts.filter(
                                                            (candidate) =>
                                                                candidate.key !==
                                                                account.key,
                                                        ),
                                                })
                                            }
                                        >
                                            {t(
                                                'business.transcription.account.remove',
                                                {
                                                    account:
                                                        accountLabel(account),
                                                },
                                            )}
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                        <button
                            type="button"
                            className={cn(BUTTON, 'mt-2.5')}
                            onClick={() =>
                                change({
                                    ...form,
                                    accounts: [
                                        ...form.accounts,
                                        {
                                            key: newKey('a'),
                                            name: '',
                                            from: form.first,
                                            until: null,
                                        },
                                    ],
                                })
                            }
                        >
                            {t('business.transcription.account.add')}
                        </button>

                        {month !== undefined && (
                            <>
                                <h2 className={SECTION}>
                                    {t('business.transcription.figures.title')}
                                </h2>
                                <div
                                    role="group"
                                    aria-label={t(
                                        'business.transcription.figures.months',
                                    )}
                                    className="mt-2 flex flex-wrap gap-1.5"
                                >
                                    {months.map((value) => {
                                        const open = problems.filter(
                                            (problem) =>
                                                problem.month === value,
                                        ).length;

                                        return (
                                            <button
                                                key={value}
                                                type="button"
                                                aria-pressed={value === month}
                                                onClick={() =>
                                                    setSelected(value)
                                                }
                                                className={cn(
                                                    'rounded-[10px] border px-2.5 py-1 text-[11.5px] font-semibold',
                                                    value === month
                                                        ? 'border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                                                        : 'border-rz-border text-rz-slate',
                                                )}
                                            >
                                                {monthLabel(value)}{' '}
                                                <span
                                                    className={
                                                        open === 0
                                                            ? 'text-rz-success-text'
                                                            : 'text-rz-danger-text'
                                                    }
                                                >
                                                    {open === 0
                                                        ? '✓'
                                                        : `· ${open}`}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                                {form.accounts
                                    .filter((account) =>
                                        isActive(account, month),
                                    )
                                    .map((account) => (
                                        <AccountMonth
                                            key={account.key}
                                            form={form}
                                            months={months}
                                            month={month}
                                            account={account}
                                            label={accountLabel(account)}
                                            documents={statements.documents}
                                            monthLabel={monthLabel}
                                            accountLabel={accountLabel}
                                            onSheet={(update) =>
                                                changeSheet(account.key, update)
                                            }
                                            onForm={change}
                                        />
                                    ))}
                            </>
                        )}
                    </>
                )}
            </div>
        </DetailSheet>
    );

    return (
        <BusinessShell
            title={t('business.transcription.title')}
            tab="home"
            links={home.links}
            showTabBar={false}
        >
            <HomeBody
                {...home}
                backdrop
                overlay={{ column: 'right', content: sheet }}
            />
        </BusinessShell>
    );
}

type Sheet = ReturnType<typeof sheetOf>;

type AccountMonthProps = {
    form: TranscriptionForm;
    months: string[];
    month: string;
    account: Account;
    label: string;
    documents: BusinessStatementTranscriptionProps['statements']['documents'];
    monthLabel: (month: string) => string;
    accountLabel: (account: Account) => string;
    onSheet: (update: (sheet: Sheet) => Sheet) => void;
    onForm: (form: TranscriptionForm) => void;
};

/** One account's month: which statement it is from, its balances and its transactions. */
function AccountMonth({
    form,
    months,
    month,
    account,
    label,
    documents,
    monthLabel,
    accountLabel,
    onSheet,
    onForm,
}: AccountMonthProps) {
    const { t } = useTranslation();
    const current = sheetOf(form, month, account.key);
    const opening = openingOf(form, months, account, month);
    const computed = computedClosing(opening.value, current.rows);
    const codes = sheetProblems(form, months, account, month);
    const id = `${month}-${account.key}`;

    return (
        <section
            aria-labelledby={`${id}-heading`}
            className="mt-3 rounded-2xl border border-rz-border bg-rz-surface p-4"
        >
            <h3
                id={`${id}-heading`}
                className="text-[13px] font-bold text-rz-ink"
            >
                {t('business.transcription.figures.heading', {
                    account: label,
                    month: monthLabel(month),
                })}
            </h3>

            <fieldset className="mt-3">
                <legend className={LABEL}>
                    {t('business.transcription.figures.sources')}
                </legend>
                {documents.map((document) => (
                    <label
                        key={document.id}
                        className="flex items-center gap-2 py-0.5 text-[12.5px] text-rz-ink"
                    >
                        <input
                            type="checkbox"
                            checked={current.sources.includes(document.id)}
                            onChange={(event) =>
                                onSheet((sheet) => ({
                                    ...sheet,
                                    sources: event.target.checked
                                        ? [...sheet.sources, document.id]
                                        : sheet.sources.filter(
                                              (source) =>
                                                  source !== document.id,
                                          ),
                                }))
                            }
                        />
                        <span className="truncate">{document.filename}</span>
                    </label>
                ))}
            </fieldset>

            <label className="mt-3 block">
                <span className={LABEL}>
                    {t('business.transcription.figures.opening')}
                </span>
                <input
                    inputMode="numeric"
                    className={FIELD}
                    value={opening.value}
                    disabled={opening.fixed}
                    onChange={(event) =>
                        onSheet((sheet) => ({
                            ...sheet,
                            opening: cleanFrancs(event.target.value, true),
                        }))
                    }
                />
            </label>
            {opening.fixed && (
                <p className="mt-1 text-[11px] text-rz-secondary">
                    {t('business.transcription.figures.opening_fixed')}
                </p>
            )}

            <h4 className={cn(LABEL, 'mt-4')}>
                {t('business.transcription.figures.transactions')}
            </h4>
            {current.rows.length === 0 && (
                <p className="text-[12px] text-rz-secondary">
                    {t('business.transcription.figures.none')}
                </p>
            )}
            <ol className="flex flex-col gap-2.5">
                {current.rows.map((row, index) => {
                    const links =
                        row.classification === 'transfer'
                            ? transferCandidates(form, month, account.key).map(
                                  (candidate) => ({
                                      link: candidate.link,
                                      text: `${accountLabel(candidate.account)} · ${candidate.row.date} · ${candidate.row.amount}`,
                                  }),
                              )
                            : drawsBefore(form, months, month)
                                  .filter((draw) => draw.row.key !== row.key)
                                  .map((draw) => ({
                                      link: draw.link,
                                      text: `${monthLabel(draw.month)} · ${accountLabel(draw.account)} · ${draw.row.amount}`,
                                  }));
                    const setRow = (change: Parameters<typeof updateRow>[4]) =>
                        onForm(
                            updateRow(
                                form,
                                month,
                                account.key,
                                row.key,
                                change,
                            ),
                        );
                    const rowLabel = t('business.transcription.row.label', {
                        number: index + 1,
                    });

                    return (
                        <li
                            key={row.key}
                            aria-label={rowLabel}
                            className="rounded-xl border border-rz-border p-2.5"
                        >
                            <div className="grid grid-cols-2 gap-2">
                                <label>
                                    <span className={LABEL}>
                                        {t('business.transcription.row.date')}
                                    </span>
                                    <input
                                        type="date"
                                        className={FIELD}
                                        value={row.date}
                                        min={`${month}-01`}
                                        max={`${month}-31`}
                                        onChange={(event) =>
                                            setRow({ date: event.target.value })
                                        }
                                    />
                                </label>
                                <label>
                                    <span className={LABEL}>
                                        {t(
                                            'business.transcription.row.direction',
                                        )}
                                    </span>
                                    <select
                                        className={FIELD}
                                        value={row.direction}
                                        onChange={(event) =>
                                            setRow({
                                                direction: event.target
                                                    .value as Direction,
                                                classification: '',
                                                link: null,
                                            })
                                        }
                                    >
                                        <option value="in">
                                            {t('business.transcription.row.in')}
                                        </option>
                                        <option value="out">
                                            {t(
                                                'business.transcription.row.out',
                                            )}
                                        </option>
                                    </select>
                                </label>
                                <label>
                                    <span className={LABEL}>
                                        {t('business.transcription.row.amount')}
                                    </span>
                                    <input
                                        inputMode="numeric"
                                        className={FIELD}
                                        value={row.amount}
                                        onChange={(event) =>
                                            setRow({
                                                amount: cleanFrancs(
                                                    event.target.value,
                                                    false,
                                                ),
                                            })
                                        }
                                    />
                                </label>
                                <label>
                                    <span className={LABEL}>
                                        {t('business.transcription.row.type')}
                                    </span>
                                    <select
                                        className={FIELD}
                                        value={row.classification}
                                        onChange={(event) =>
                                            onForm(
                                                pairTransfer(
                                                    updateRow(
                                                        form,
                                                        month,
                                                        account.key,
                                                        row.key,
                                                        {
                                                            classification:
                                                                event.target
                                                                    .value as StatementClassification,
                                                        },
                                                    ),
                                                    month,
                                                    account.key,
                                                    row.key,
                                                    null,
                                                ),
                                            )
                                        }
                                    >
                                        <option value="">
                                            {t(
                                                'business.transcription.row.choose',
                                            )}
                                        </option>
                                        {CLASSIFICATIONS[row.direction].map(
                                            (value) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {t(
                                                        `business.transcription.type.${row.direction}.${value}` as MessageCode,
                                                    )}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </label>
                            </div>
                            {(row.classification === 'transfer' ||
                                row.classification === 'owner_return') && (
                                <label className="mt-2 block">
                                    <span className={LABEL}>
                                        {t(
                                            `business.transcription.row.link.${row.classification}`,
                                        )}
                                    </span>
                                    <select
                                        className={FIELD}
                                        value={row.link ?? ''}
                                        onChange={(event) => {
                                            const link =
                                                event.target.value === ''
                                                    ? null
                                                    : event.target.value;

                                            onForm(
                                                row.classification ===
                                                    'transfer'
                                                    ? pairTransfer(
                                                          form,
                                                          month,
                                                          account.key,
                                                          row.key,
                                                          link,
                                                      )
                                                    : updateRow(
                                                          form,
                                                          month,
                                                          account.key,
                                                          row.key,
                                                          { link },
                                                      ),
                                            );
                                        }}
                                    >
                                        <option value="">
                                            {t(
                                                'business.transcription.row.choose',
                                            )}
                                        </option>
                                        {links.map((candidate) => (
                                            <option
                                                key={candidate.link}
                                                value={candidate.link}
                                            >
                                                {candidate.text}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            )}
                            <div className="mt-2 flex items-end gap-2">
                                <label className="min-w-0 flex-1">
                                    <span className={LABEL}>
                                        {t(
                                            'business.transcription.row.reference',
                                        )}
                                    </span>
                                    <input
                                        className={FIELD}
                                        value={row.reference}
                                        placeholder={`row-${index + 1}`}
                                        onChange={(event) =>
                                            setRow({
                                                reference: cleanIdentifier(
                                                    event.target.value,
                                                ),
                                            })
                                        }
                                    />
                                </label>
                                <button
                                    type="button"
                                    className={BUTTON}
                                    onClick={() => {
                                        const released = pairTransfer(
                                            form,
                                            month,
                                            account.key,
                                            row.key,
                                            null,
                                        );
                                        const remaining = sheetOf(
                                            released,
                                            month,
                                            account.key,
                                        );

                                        onForm({
                                            ...released,
                                            sheets: {
                                                ...released.sheets,
                                                [sheetKey(month, account.key)]:
                                                    {
                                                        ...remaining,
                                                        rows: remaining.rows.filter(
                                                            (candidate) =>
                                                                candidate.key !==
                                                                row.key,
                                                        ),
                                                    },
                                            },
                                        });
                                    }}
                                >
                                    {t('business.transcription.row.remove', {
                                        row: rowLabel,
                                    })}
                                </button>
                            </div>
                        </li>
                    );
                })}
            </ol>
            <button
                type="button"
                className={cn(BUTTON, 'mt-2.5')}
                onClick={() =>
                    onSheet((sheet) => ({
                        ...sheet,
                        rows: [
                            ...sheet.rows,
                            {
                                key: newKey('r'),
                                reference: '',
                                date: '',
                                direction: 'in',
                                amount: '',
                                classification: '',
                                link: null,
                            },
                        ],
                    }))
                }
            >
                {t('business.transcription.row.add')}
            </button>

            <label className="mt-4 block">
                <span className={LABEL}>
                    {t('business.transcription.figures.closing')}
                </span>
                <input
                    inputMode="numeric"
                    className={FIELD}
                    value={current.closing}
                    onChange={(event) =>
                        onSheet((sheet) => ({
                            ...sheet,
                            closing: cleanFrancs(event.target.value, true),
                        }))
                    }
                />
            </label>
            {computed !== null && (
                <p className="mt-1 text-[11px] text-rz-secondary">
                    {t('business.transcription.figures.computed', {
                        amount: francs(computed),
                    })}
                </p>
            )}
            {codes.length > 0 && (
                <ul className="mt-2.5 flex flex-col gap-1 text-[12px] text-rz-danger-text">
                    {codes.map((code) => (
                        <li key={code}>
                            {t(`business.transcription.problem.${code}`, {
                                max: MAX_MONTHS,
                            })}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
