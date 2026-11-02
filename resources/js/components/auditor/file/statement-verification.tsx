import { useState } from 'react';
import type { ReactNode } from 'react';
import { useAuditorCommands } from '@/components/auditor/commands';
import { Eyebrow, PRIMARY } from '@/components/auditor/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatMonthYearLong } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { StatementReviewFile } from '@/types/auditor';

/** A whole RWF amount, as the review takes it: no sign, separators or decimals. */
const AMOUNT = /^(0|[1-9]\d{0,63})$/;

/** The review refusals this form explains in its own words; any other reads as the page's notice. */
const REFUSALS = [
    'STATEMENT_REVIEW_INCOMPLETE',
    'STATEMENT_SOURCE_REVIEW_REQUIRED',
    'STATEMENT_REVIEW_REFERENCE_REQUIRED',
    'STATEMENT_DEBT_EVIDENCE_REQUIRED',
    'STATEMENT_REVIEW_AMOUNT_INVALID',
    'STATEMENT_COMPLETE_MONTH_REQUIRED',
    'STATEMENT_RECONCILIATION_REQUIRED',
    'STATEMENT_TRANSCRIPTION_STALE',
];

type Obligation = {
    id: string;
    principal: string;
    sources: string[];
    service: Record<string, string>;
    /** The contractual payment for each month after the statements, until `lastPayment`. */
    scheduled: string;
    /** The last month the payment falls due, or `BEYOND` when it runs past the schedule. */
    lastPayment: string;
};

/**
 * How many months after the last statement month a debt's schedule must cover. Statement evidence
 * stays usable until 45 days after its last month, so the first repayment month falls at most two
 * months later, and the longest term (six months) ends at most seven months after the statements.
 */
const SCHEDULE_MONTHS = 7;

/** A debt still being repaid after the last month of the schedule. */
const BEYOND = 'beyond';

/** The `count` calendar months after `month` (YYYY-MM). */
const monthsAfter = (month: string, count: number): string[] => {
    const [year, index] = month.split('-').map(Number);

    return Array.from({ length: count }, (_, offset) => {
        const at = index + offset;
        const label = String((at % 12) + 1).padStart(2, '0');

        return `${year + Math.floor(at / 12)}-${label}`;
    });
};

const FIELD =
    'h-10 w-full rounded-xl border border-rz-border bg-rz-surface px-3 text-[13px] text-rz-text';

/** The review sends text on one line: the procedure refuses control characters, line breaks included. */
const oneLine = (text: string): string => text.replace(/\s+/g, ' ').trim();

function Group({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="mt-4">
            <Eyebrow as="h4" className="tracking-[.05em]">
                {title}
            </Eyebrow>
            <div className="mt-2 space-y-2.5 rounded-2xl border border-rz-border bg-rz-surface p-3.5">
                {children}
            </div>
        </section>
    );
}

/**
 * The Audit Partner's factual statement review (#396, contract MVP-AUP-1): open each statement on
 * file and say where it was checked, compare the Business's saved monthly figures, tick every check
 * the procedure asks for, declare the debts found (or none), the recurring owner draw and the
 * findings. Sent once, pinned to the evidence, transcription and earlier verification it was made
 * against; the server reconciles the figures again and records them verified. Nothing here is a
 * credit decision.
 */
export function StatementVerification({
    review,
    business,
}: {
    review: StatementReviewFile;
    business: string;
}) {
    const { t, locale } = useTranslation();
    const center = useAuditorCommands();
    const transcription = review.transcription;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const [references, setReferences] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            review.documents.map((document) => [document.id, '']),
        ),
    );
    const [inventory, setInventory] = useState('');
    const [obligations, setObligations] = useState<Obligation[]>([]);
    const [ownerDraw, setOwnerDraw] = useState('');
    const [ownerDrawReference, setOwnerDrawReference] = useState('');
    const [findings, setFindings] = useState('');
    const [refusal, setRefusal] = useState<string | null>(null);

    if (transcription === null || review.verify === null) {
        return (
            <p
                role="note"
                className="mt-4 rounded-xl border border-dashed border-rz-border px-3.5 py-3 text-[12px] leading-[1.5] text-rz-secondary"
            >
                {t('auditor.verify.waiting_for_figures')}
            </p>
        );
    }

    const route = review.verify;
    const months = transcription.months;
    const upcoming = monthsAfter(months[months.length - 1], SCHEDULE_MONTHS);
    const amount = (value: string) =>
        new Intl.NumberFormat(locale).format(BigInt(value));
    const filled = (text: string) => oneLine(text) !== '';
    const obligationComplete = (obligation: Obligation) =>
        filled(obligation.id) &&
        AMOUNT.test(obligation.principal) &&
        obligation.sources.length > 0 &&
        months.every((month) => AMOUNT.test(obligation.service[month])) &&
        AMOUNT.test(obligation.scheduled) &&
        obligation.lastPayment !== '';
    const complete =
        review.checks.every((check) => checks[check] === true) &&
        review.documents.every((document) => filled(references[document.id])) &&
        filled(inventory) &&
        obligations.every(obligationComplete) &&
        new Set(obligations.map((obligation) => oneLine(obligation.id)))
            .size === obligations.length &&
        AMOUNT.test(ownerDraw) &&
        filled(ownerDrawReference) &&
        filled(findings);

    const change = (index: number, patch: Partial<Obligation>) =>
        setObligations((current) =>
            current.map((obligation, at) =>
                at === index ? { ...obligation, ...patch } : obligation,
            ),
        );

    const submit = () => {
        setRefusal(null);
        center.send(
            {
                name: 'statement.verify',
                business,
                route,
                lookup: review.operation,
                payload: {
                    expected_revision: review.assignment_revision,
                    expected_evidence_revision: review.evidence_revision,
                    expected_verification_revision:
                        review.verification_revision,
                    transcription_id: transcription.id,
                    transcription_sha256: transcription.sha256,
                    review: {
                        procedure_version: review.procedure_version,
                        checks: Object.fromEntries(
                            review.checks.map((check) => [check, true]),
                        ),
                        source_checks: Object.fromEntries(
                            review.documents.map((document) => [
                                document.id,
                                {
                                    sha256: document.sha256,
                                    reference: oneLine(references[document.id]),
                                },
                            ]),
                        ),
                        inventory_reference: oneLine(inventory),
                        obligations: obligations.map((obligation) => ({
                            id: oneLine(obligation.id),
                            principal: obligation.principal,
                            source_ids: obligation.sources,
                            service_by_month: Object.fromEntries([
                                ...months.map((month) => [
                                    month,
                                    obligation.service[month],
                                ]),
                                ...upcoming.map((month) => [
                                    month,
                                    obligation.lastPayment === BEYOND ||
                                    month <= obligation.lastPayment
                                        ? obligation.scheduled
                                        : '0',
                                ]),
                            ]),
                        })),
                        recurring_owner_draw: ownerDraw,
                        owner_draw_reference: oneLine(ownerDrawReference),
                        findings: oneLine(findings),
                    },
                },
            },
            {
                onRefused: (code) => {
                    if (!REFUSALS.includes(code)) {
                        return false;
                    }

                    setRefusal(code);

                    return true;
                },
            },
        );
    };

    return (
        <form
            aria-label={t('auditor.verify.title')}
            className="mt-5"
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
        >
            <h3 className="text-rz-text text-[15px] font-bold">
                {t('auditor.verify.title')}
            </h3>
            <p className="mt-1 text-[12px] leading-[1.5] text-rz-secondary">
                {t('auditor.verify.lead')}
            </p>

            <Group title={t('auditor.verify.statements')}>
                {review.documents.map((document) => (
                    <div key={document.id}>
                        <div className="flex items-center justify-between gap-3 text-[13px]">
                            <span className="text-rz-text font-semibold">
                                {document.filename}
                            </span>
                            <a
                                href={document.open.url}
                                className="font-semibold text-rz-accent-app-text"
                            >
                                {t('auditor.verify.open')}
                            </a>
                        </div>
                        <label className="mt-1.5 block text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.reference')}
                            <input
                                className={cn(FIELD, 'mt-1')}
                                value={references[document.id]}
                                onChange={(event) =>
                                    setReferences((current) => ({
                                        ...current,
                                        [document.id]: event.target.value,
                                    }))
                                }
                            />
                        </label>
                    </div>
                ))}
            </Group>

            <Group title={t('auditor.verify.figures')}>
                <table className="w-full text-left text-[12px]">
                    <thead className="text-rz-secondary">
                        <tr>
                            <th scope="col">{t('auditor.verify.month')}</th>
                            <th scope="col">{t('auditor.verify.inflow')}</th>
                            <th scope="col">{t('auditor.verify.outflow')}</th>
                            <th scope="col">
                                {t('auditor.verify.owner_draw')}
                            </th>
                            <th scope="col">
                                {t('auditor.verify.debt_service')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="text-rz-text">
                        {transcription.observations.map((observation) => (
                            <tr key={observation.month}>
                                <th scope="row" className="font-semibold">
                                    {formatMonthYearLong(
                                        `${observation.month}-15`,
                                        locale,
                                    )}
                                </th>
                                <td>{amount(observation.operating_inflow)}</td>
                                <td>{amount(observation.operating_outflow)}</td>
                                <td>{amount(observation.owner_draw)}</td>
                                <td>{amount(observation.debt_service)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Group>

            <Group title={t('auditor.verify.checks')}>
                {review.checks.map((check) => (
                    <label
                        key={check}
                        className="text-rz-text flex items-start gap-2.5 text-[12.5px] leading-[1.45]"
                    >
                        <input
                            type="checkbox"
                            className="mt-0.5"
                            checked={checks[check] === true}
                            onChange={(event) =>
                                setChecks((current) => ({
                                    ...current,
                                    [check]: event.target.checked,
                                }))
                            }
                        />
                        {t(`auditor.verify.check.${check}` as MessageCode)}
                    </label>
                ))}
                <label className="block text-[11.5px] text-rz-secondary">
                    {t('auditor.verify.inventory')}
                    <input
                        className={cn(FIELD, 'mt-1')}
                        value={inventory}
                        onChange={(event) => setInventory(event.target.value)}
                    />
                </label>
            </Group>

            <Group title={t('auditor.verify.debts')}>
                {obligations.length === 0 && (
                    <p className="text-[12px] leading-[1.5] text-rz-secondary">
                        {t('auditor.verify.no_debts')}
                    </p>
                )}
                {obligations.map((obligation, index) => (
                    <fieldset
                        key={index}
                        className="space-y-2 rounded-xl border border-rz-border p-3"
                    >
                        <legend className="text-rz-text px-1 text-[12px] font-semibold">
                            {t('auditor.verify.debt', { number: index + 1 })}
                        </legend>
                        <label className="block text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.debt_name')}
                            <input
                                className={cn(FIELD, 'mt-1')}
                                value={obligation.id}
                                onChange={(event) =>
                                    change(index, { id: event.target.value })
                                }
                            />
                        </label>
                        <label className="block text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.debt_principal')}
                            <input
                                inputMode="numeric"
                                className={cn(FIELD, 'mt-1')}
                                value={obligation.principal}
                                onChange={(event) =>
                                    change(index, {
                                        principal: event.target.value,
                                    })
                                }
                            />
                        </label>
                        {months.map((month) => (
                            <label
                                key={month}
                                className="block text-[11.5px] text-rz-secondary"
                            >
                                {t('auditor.verify.debt_service_in', {
                                    month: formatMonthYearLong(
                                        `${month}-15`,
                                        locale,
                                    ),
                                })}
                                <input
                                    inputMode="numeric"
                                    className={cn(FIELD, 'mt-1')}
                                    value={obligation.service[month]}
                                    onChange={(event) =>
                                        change(index, {
                                            service: {
                                                ...obligation.service,
                                                [month]: event.target.value,
                                            },
                                        })
                                    }
                                />
                            </label>
                        ))}
                        <label className="block text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.debt_scheduled', {
                                month: formatMonthYearLong(
                                    `${upcoming[0]}-15`,
                                    locale,
                                ),
                            })}
                            <input
                                inputMode="numeric"
                                className={cn(FIELD, 'mt-1')}
                                value={obligation.scheduled}
                                onChange={(event) =>
                                    change(index, {
                                        scheduled: event.target.value,
                                    })
                                }
                            />
                        </label>
                        <label className="block text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.debt_last_payment')}
                            <select
                                className={cn(FIELD, 'mt-1')}
                                value={obligation.lastPayment}
                                onChange={(event) =>
                                    change(index, {
                                        lastPayment: event.target.value,
                                    })
                                }
                            >
                                <option value="" disabled>
                                    {t('auditor.verify.debt_last_payment_pick')}
                                </option>
                                {upcoming.map((month) => (
                                    <option key={month} value={month}>
                                        {formatMonthYearLong(
                                            `${month}-15`,
                                            locale,
                                        )}
                                    </option>
                                ))}
                                <option value={BEYOND}>
                                    {t('auditor.verify.debt_beyond', {
                                        month: formatMonthYearLong(
                                            `${upcoming[upcoming.length - 1]}-15`,
                                            locale,
                                        ),
                                    })}
                                </option>
                            </select>
                        </label>
                        <p className="text-[11.5px] text-rz-secondary">
                            {t('auditor.verify.debt_sources')}
                        </p>
                        {review.documents.map((document) => (
                            <label
                                key={document.id}
                                className="text-rz-text flex items-center gap-2 text-[12px]"
                            >
                                <input
                                    type="checkbox"
                                    checked={obligation.sources.includes(
                                        document.id,
                                    )}
                                    onChange={(event) =>
                                        change(index, {
                                            sources: event.target.checked
                                                ? [
                                                      ...obligation.sources,
                                                      document.id,
                                                  ]
                                                : obligation.sources.filter(
                                                      (source) =>
                                                          source !==
                                                          document.id,
                                                  ),
                                        })
                                    }
                                />
                                {document.filename}
                            </label>
                        ))}
                        <button
                            type="button"
                            className="text-[12px] font-semibold text-rz-accent-app-text"
                            onClick={() =>
                                setObligations((current) =>
                                    current.filter((_, at) => at !== index),
                                )
                            }
                        >
                            {t('auditor.verify.remove_debt')}
                        </button>
                    </fieldset>
                ))}
                <button
                    type="button"
                    className="text-[12.5px] font-semibold text-rz-accent-app-text"
                    onClick={() =>
                        setObligations((current) => [
                            ...current,
                            {
                                id: '',
                                principal: '',
                                sources: [],
                                service: Object.fromEntries(
                                    months.map((month) => [month, '']),
                                ),
                                scheduled: '',
                                lastPayment: '',
                            },
                        ])
                    }
                >
                    {t('auditor.verify.add_debt')}
                </button>
            </Group>

            <Group title={t('auditor.verify.owner')}>
                <label className="block text-[11.5px] text-rz-secondary">
                    {t('auditor.verify.owner_draw_amount')}
                    <input
                        inputMode="numeric"
                        className={cn(FIELD, 'mt-1')}
                        value={ownerDraw}
                        onChange={(event) => setOwnerDraw(event.target.value)}
                    />
                </label>
                <label className="block text-[11.5px] text-rz-secondary">
                    {t('auditor.verify.owner_draw_reference')}
                    <input
                        className={cn(FIELD, 'mt-1')}
                        value={ownerDrawReference}
                        onChange={(event) =>
                            setOwnerDrawReference(event.target.value)
                        }
                    />
                </label>
                <label className="block text-[11.5px] text-rz-secondary">
                    {t('auditor.verify.findings')}
                    <textarea
                        rows={3}
                        className={cn(FIELD, 'mt-1 h-auto py-2')}
                        value={findings}
                        onChange={(event) => setFindings(event.target.value)}
                    />
                </label>
            </Group>

            {refusal !== null && (
                <p
                    role="alert"
                    className="bg-rz-danger-soft mt-3 rounded-xl px-3.5 py-2.5 text-[12px] leading-[1.5] text-rz-danger-text"
                >
                    {t(`auditor.verify.refused.${refusal}` as MessageCode)}
                </p>
            )}

            {!complete && (
                <p className="mt-3 text-[11.5px] leading-[1.5] text-rz-secondary">
                    {t('auditor.verify.incomplete')}
                </p>
            )}
            <button
                type="submit"
                disabled={!complete || !center.idle}
                aria-busy={center.busy || undefined}
                className={cn(
                    PRIMARY,
                    'mt-3 flex h-[50px] w-full items-center justify-center rounded-2xl text-[15px]',
                )}
            >
                {center.busy
                    ? t('auditor.verify.sending')
                    : t('auditor.verify.submit')}
            </button>
        </form>
    );
}
