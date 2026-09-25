import { Link, router } from '@inertiajs/react';
import { useEffect, useEffectEvent, useState } from 'react';
import type { ReactNode } from 'react';
import { EvidenceList } from '@/components/auditor/audit/evidence';
import {
    StepEyebrow,
    StepHeading,
    onlyDigits,
} from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { useStepUp } from '@/components/auditor/audit/use-step-up';
import {
    AuditorCommandNotice,
    refusalNeedsFreshFacts,
    useAuditorCommands,
    useRefusalText,
    useSheetPresence,
} from '@/components/auditor/commands';
import {
    NOTE_FIELD,
    ReasonSheet,
} from '@/components/auditor/sheets/reason-sheet';
import { DIVIDER } from '@/components/auditor/ui';
import { ErrorBanner, FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { fallbackCode } from '@/lib/rozine/operation';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type { AuditorPreviewOutcome, SealStage } from '@/types/auditor';

/** A confirmed authenticator's one-time code (auditor-filing-v1 point 2). */
export const CODE_LENGTH = 6;

const PRIMARY =
    'h-[50px] w-full rounded-2xl bg-rz-accent-fill text-[14.5px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary';

const SUMMARY_TONE = {
    ok: 'text-rz-positive',
    flag: 'text-rz-danger-text',
    neutral: 'text-rz-ink',
} as const;

type Nested = 'preview' | 'code' | 'request_changes' | 'reject' | null;

/**
 * Where the authenticator entry stands. A code is never kept after its request: every state
 * after an attempt asks for a new one. Only a 422 naming the code is `wrong_code`; an identity or
 * authorization refusal (403) or a stale report is never presented as one (#96 point 1).
 */
type Entry =
    | { kind: 'ready' }
    | { kind: 'wrong_code'; message: string | null }
    | { kind: 'expired' }
    | { kind: 'throttled'; seconds: number | null }
    | { kind: 'failed'; message: string };

const STEP_UP_REFUSALS = new Set(['STEP_UP_INVALID', 'STEP_UP_EXPIRED']);

const initialEntry = (preview: AuditorPreviewOutcome | undefined): Entry => {
    if (preview?.kind !== 'step_up') {
        return { kind: 'ready' };
    }

    switch (preview.state) {
        case 'wrong_code':
            return { kind: 'wrong_code', message: null };
        case 'expired':
            return { kind: 'expired' };
        case 'throttled':
            return { kind: 'throttled', seconds: preview.retry_after ?? null };
        default:
            return { kind: 'ready' };
    }
};

const initialNested = (preview: AuditorPreviewOutcome | undefined): Nested => {
    if (preview?.kind === 'step_up') {
        return 'code';
    }

    return preview?.kind === 'sheet' && preview.sheet !== 'decline'
        ? preview.sheet
        : null;
};

const formatWait = (seconds: number): string =>
    `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

/** The `Retry-After` wait, counted down from the response; it ends by handing back the entry. */
function Throttled({
    seconds,
    onDone,
}: {
    seconds: number | null;
    onDone: () => void;
}) {
    const { t } = useTranslation();
    const [left, setLeft] = useState(seconds);
    const done = useEffectEvent(onDone);

    useEffect(() => {
        if (left === null) {
            return;
        }

        if (left <= 0) {
            done();

            return;
        }

        const timer = window.setTimeout(() => setLeft(left - 1), 1000);

        return () => window.clearTimeout(timer);
    }, [left]);

    return (
        <p
            role="timer"
            className="mt-3 text-[12px] font-semibold text-rz-danger-text"
        >
            {left === null
                ? t('auditor.seal.code_throttled_later')
                : t('auditor.seal.code_throttled', { wait: formatWait(left) })}
        </p>
    );
}

/** The six-digit entry: one numeric field drawn as six cells. */
function CodeCells({
    value,
    disabled,
    invalid,
    onChange,
}: {
    value: string;
    disabled: boolean;
    invalid: boolean;
    onChange: (value: string) => void;
}) {
    const { t } = useTranslation();

    return (
        <div className="relative mx-auto mt-[18px] w-fit">
            <input
                id="auditor-step-up-code"
                value={value}
                disabled={disabled}
                inputMode="numeric"
                autoComplete="one-time-code"
                maxLength={CODE_LENGTH}
                aria-label={t('auditor.seal.code_label')}
                aria-invalid={invalid || undefined}
                aria-describedby="auditor-step-up-message"
                onChange={(event) =>
                    onChange(
                        onlyDigits(event.target.value).slice(0, CODE_LENGTH),
                    )
                }
                className="peer absolute inset-0 z-10 cursor-text opacity-0 disabled:cursor-not-allowed"
            />
            <div
                aria-hidden
                className="flex gap-2 rounded-2xl peer-focus-visible:outline-2 peer-focus-visible:outline-offset-4 peer-focus-visible:outline-rz-focus-border"
            >
                {Array.from({ length: CODE_LENGTH }, (_, index) => (
                    <span
                        key={index}
                        className={cn(
                            'flex h-[52px] w-[42px] items-center justify-center rounded-xl border-[1.5px] bg-rz-surface text-[22px] font-bold text-rz-ink tabular-nums',
                            invalid
                                ? 'border-[#f4c9c6] dark:border-[rgba(255,107,111,.4)]'
                                : index === value.length
                                  ? 'border-[#0c1830] dark:border-rz-ink'
                                  : 'border-rz-border',
                            disabled && 'bg-rz-page dark:bg-rz-surface-muted',
                        )}
                    >
                        {value[index] ?? ''}
                    </span>
                ))}
            </div>
        </div>
    );
}

/** The seal's nested sheet: registered as open so the page's notice steps aside. */
function SealSheet({ children }: { children: ReactNode }) {
    useSheetPresence();

    return <>{children}</>;
}

type SealFlowOptions = {
    stage: SealStage;
    context: StepContext;
    business: string;
    /** The audited month, or the empty string for a Flash Audit. */
    period: string;
    canContinue: boolean;
    actions: {
        step_up: RouteAction;
        seal: RouteAction;
        request_changes: RouteAction;
        reject: RouteAction;
    };
    preview?: AuditorPreviewOutcome;
};

/**
 * Sign off and seal (design L1405–1496; auditor-filing-v1 points 2, 4 and 6). The partner records
 * an assessment note beside the server's factual findings, previews exactly what will be sealed —
 * findings, evidence, versions and digest — and confirms it is them with a six-digit code from
 * their confirmed authenticator. That code is exchanged for a single-use proof; the seal carries
 * the proof and the pinned versions, and the server signs. A stale digest or version asks for a
 * new preview and a new code; a monthly filing can instead go back to the business or be
 * rejected, each with a coded reason and a factual explanation — never a credit verdict.
 *
 * The note is saved before anything is previewed (#96, S-D): an edited note goes to the server
 * through `audit.save_step` at `step: seal`, and the page is read afresh with the new report
 * revision and a digest that covers it. Only a note that matches the persisted one can be
 * previewed, confirmed and sealed, so no note ever travels beside an older digest; editing it
 * again withdraws any open preview and code entry until it is saved again.
 */
export function useSealFlow({
    stage,
    context,
    business,
    period,
    canContinue,
    actions,
    preview,
}: SealFlowOptions): { body: ReactNode; footer: ReactNode; nested: ReactNode } {
    const { t } = useTranslation();
    const center = useAuditorCommands();
    const refusalText = useRefusalText();
    const stepUp = useStepUp(actions.step_up);
    const [nested, setNested] = useState<Nested>(() => initialNested(preview));
    const [note, setNote] = useState(stage.note.value);
    const [code, setCode] = useState('');
    const [entry, setEntry] = useState<Entry>(() => initialEntry(preview));
    const [stale, setStale] = useState<string | null>(null);
    /*
     * The stage a note save was sent from, and the one it completed on. Either stays current only
     * until fresh props arrive, so "Saving note…" lasts from the request to the fresh read.
     */
    const [sentFrom, setSentFrom] = useState<SealStage | null>(null);
    const [savedFrom, setSavedFrom] = useState<SealStage | null>(null);
    const initialReason = preview?.kind === 'sheet' ? preview.reason : null;

    const close = () => {
        setNested(null);
        setCode('');
    };
    /* The note the server holds, and which the digest covers; only it is ever sealed. */
    const persisted = stage.note.value;
    const unsaved = note.trim() !== persisted.trim();
    const savingNote =
        savedFrom === stage || (center.busy && sentFrom === stage);
    const noteReady =
        !stage.note.required || note.trim().length >= stage.note.min;
    const canSaveNote =
        unsaved &&
        noteReady &&
        !savingNote &&
        center.idle &&
        center.allowed('audit.save_step');
    const ready = canContinue && noteReady && center.idle;
    const canSeal = center.allowed('audit.seal');
    const canRequestChanges =
        stage.reason_options !== null &&
        center.allowed('audit.request_changes');
    const canReject =
        stage.reason_options !== null && center.allowed('audit.reject');
    const throttled = entry.kind === 'throttled';
    const busy = stepUp.checking || center.busy;

    const editNote = (value: string) => {
        setNote(value);

        /*
         * A preview, and any code typed against it, covered the note as it was saved: both are
         * withdrawn, and an earlier attempt's message with them. A throttle still runs its course.
         */
        if (nested === 'preview' || nested === 'code') {
            close();
        }

        setEntry((current) =>
            current.kind === 'throttled' ? current : { kind: 'ready' },
        );
    };

    const saveNote = () => {
        const from = stage;

        setStale(null);
        setSentFrom(from);
        center.send(
            {
                name: 'audit.save_step',
                business,
                route: context.save,
                payload: {
                    audit_id: context.auditId,
                    step: 'seal',
                    expected_revision: context.revision,
                    note: note.trim(),
                },
            },
            { onCompleted: () => setSavedFrom(from) },
        );
    };

    const seal = (proof: string) =>
        center.send(
            {
                name: 'audit.seal',
                business,
                route: actions.seal,
                payload: {
                    audit_id: context.auditId,
                    expected_revision: context.revision,
                    digest: stage.digest,
                    procedure_version: stage.procedure_version,
                    findings_version: stage.findings_version,
                    evidence_version: stage.evidence_version,
                    evidence_ids: stage.evidence.map(
                        (item) => item.evidence_id,
                    ),
                    /* An exact echo of the persisted note the digest covers. */
                    note: persisted,
                    step_up: { proof },
                },
            },
            {
                onCompleted: close,
                onRefused: (refusal, status) => {
                    if (STEP_UP_REFUSALS.has(refusal)) {
                        setEntry(
                            refusal === 'STEP_UP_EXPIRED'
                                ? { kind: 'expired' }
                                : {
                                      kind: 'failed',
                                      message: refusalText(refusal, status),
                                  },
                        );

                        return true;
                    }

                    close();

                    return false;
                },
            },
        );

    const confirm = async () => {
        /* The code leaves the page in this one request and is not kept for any retry. */
        const typed = code;

        setCode('');

        const result = await stepUp.verify({
            audit_id: context.auditId,
            expected_revision: context.revision,
            digest: stage.digest,
            identity_context_revision: context.identityContextRevision,
            request_id: crypto.randomUUID(),
            code: typed,
        });

        switch (result.kind) {
            case 'proof':
                setEntry({ kind: 'ready' });
                seal(result.proof.proof);

                return;
            case 'invalid':
                /* Only a 422 naming the code is a wrong code; any other 422 is not. */
                setEntry(
                    result.message === null
                        ? {
                              kind: 'failed',
                              message: refusalText('REQUEST_FAILED', 422),
                          }
                        : { kind: 'wrong_code', message: result.message },
                );

                return;
            case 'unreachable':
                setEntry({
                    kind: 'failed',
                    message: t('auditor.seal.code_unreachable'),
                });

                return;
        }

        const refusal = result.code ?? fallbackCode(result.status);

        if (result.status === 429) {
            setEntry({ kind: 'throttled', seconds: result.retryAfter });
        } else if (refusalNeedsFreshFacts(refusal, result.status)) {
            setStale(refusal);
            close();
            router.reload();
        } else {
            setEntry({
                kind: 'failed',
                message: refusalText(refusal, result.status),
            });
        }
    };

    const reasonFields = (fields: { reason_code: string; reason: string }) => ({
        audit_id: context.auditId,
        expected_revision: context.revision,
        ...fields,
    });

    const noteStatus = !stage.note.required
        ? t('auditor.seal.note_optional')
        : noteReady
          ? t('auditor.seal.note_done')
          : t('auditor.seal.note_required');

    const body = (
        <>
            {stale !== null && (
                <div className="mb-3.5">
                    <ErrorBanner>{refusalText(stale, 409)}</ErrorBanner>
                </div>
            )}
            <StepHeading
                title={t('auditor.seal.title')}
                lead={t('auditor.seal.lead')}
            />
            <dl className="mt-3.5 overflow-hidden rounded-2xl border border-rz-border bg-rz-surface">
                {stage.summary.map((row) => (
                    <div
                        key={row.label}
                        className={cn(
                            'flex items-center gap-[11px] border-b px-[15px] py-3',
                            DIVIDER,
                        )}
                    >
                        <dt className="flex-1 text-[12.5px] text-rz-slate">
                            {row.label}
                        </dt>
                        <dd
                            className={cn(
                                'text-[12.5px] font-bold',
                                SUMMARY_TONE[row.tone],
                            )}
                        >
                            {row.value}
                        </dd>
                    </div>
                ))}
            </dl>
            <div className="mt-4 flex items-baseline justify-between gap-2.5">
                <StepEyebrow>
                    <label htmlFor="auditor-seal-note">
                        {t('auditor.seal.note')}
                    </label>
                </StepEyebrow>
                <span
                    className={cn(
                        'text-[11px] font-bold',
                        stage.note.required && !noteReady
                            ? 'text-rz-danger-text'
                            : stage.note.required
                              ? 'text-rz-positive'
                              : 'text-rz-secondary',
                    )}
                >
                    {noteStatus}
                </span>
            </div>
            <p className="mt-[5px] text-[11.5px] leading-[1.5] text-rz-secondary">
                {stage.note.why}
            </p>
            <textarea
                id="auditor-seal-note"
                value={note}
                maxLength={stage.note.max}
                readOnly={savingNote}
                onChange={(event) => editNote(event.target.value)}
                placeholder={t('auditor.seal.note_placeholder')}
                aria-invalid={center.errors.note ? true : undefined}
                aria-describedby={
                    unsaved ? 'auditor-seal-note-unsaved' : undefined
                }
                className={cn(NOTE_FIELD, 'mt-2 min-h-[84px]')}
            />
            <p className="mt-1 text-right text-[10.5px] text-rz-secondary">
                {t('auditor.seal.note_count', {
                    count: note.length,
                    max: stage.note.max,
                })}
            </p>
            <FieldError id="auditor-seal-note-error">
                {center.errors.note}
            </FieldError>
            {unsaved && canSeal && (
                <p
                    id="auditor-seal-note-unsaved"
                    className="mt-1.5 text-[11.5px] leading-[1.5] text-rz-secondary"
                >
                    {t('auditor.seal.note_unsaved')}
                </p>
            )}
        </>
    );

    const footer = (
        <>
            {canSeal &&
                (unsaved ? (
                    <button
                        type="button"
                        disabled={!canSaveNote}
                        onClick={saveNote}
                        aria-busy={savingNote || undefined}
                        className={PRIMARY}
                    >
                        {savingNote
                            ? t('auditor.seal.saving_note')
                            : t('auditor.seal.save_note')}
                    </button>
                ) : (
                    <button
                        type="button"
                        disabled={!ready}
                        onClick={() => {
                            setStale(null);
                            setNested('preview');
                        }}
                        className={PRIMARY}
                    >
                        {t('auditor.seal.preview')}
                    </button>
                ))}
            {(canRequestChanges || canReject) && (
                <div className="flex gap-[9px]">
                    {canRequestChanges && (
                        <button
                            type="button"
                            disabled={!center.idle}
                            onClick={() => setNested('request_changes')}
                            className="h-11 flex-1 rounded-xl border border-rz-border bg-rz-surface text-[13px] font-bold text-rz-slate disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {t('auditor.seal.suggest')}
                        </button>
                    )}
                    {canReject && (
                        <button
                            type="button"
                            disabled={!center.idle}
                            onClick={() => setNested('reject')}
                            className="h-11 flex-1 rounded-xl border border-[#f2c4c4] bg-rz-surface text-[13px] font-bold text-[#d0342c] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[rgba(255,107,111,.3)] dark:text-rz-danger-text"
                        >
                            {t('auditor.seal.reject')}
                        </button>
                    )}
                </div>
            )}
        </>
    );

    let overlay: ReactNode = null;
    const reasons = stage.reason_options;

    if (nested === 'request_changes' && canRequestChanges && reasons !== null) {
        overlay = (
            <ReasonSheet
                title={t('auditor.seal.suggest')}
                lead={t('auditor.seal.suggest_lead', { business })}
                placeholder={t('auditor.seal.suggest_placeholder')}
                submitLabel={t('auditor.seal.suggest_submit')}
                options={reasons.request_changes}
                initialReason={initialReason}
                onSubmit={(fields) =>
                    center.send(
                        {
                            name: 'audit.request_changes',
                            business,
                            route: actions.request_changes,
                            payload: reasonFields(fields),
                        },
                        { onCompleted: close },
                    )
                }
                onClose={close}
            />
        );
    } else if (nested === 'reject' && canReject && reasons !== null) {
        overlay = (
            <ReasonSheet
                title={t('auditor.seal.reject')}
                lead={t('auditor.seal.reject_lead', { business })}
                placeholder={t('auditor.seal.reject_placeholder')}
                submitLabel={t('auditor.seal.reject_submit')}
                options={reasons.reject}
                initialReason={initialReason}
                destructive
                onSubmit={(fields) =>
                    center.send(
                        {
                            name: 'audit.reject',
                            business,
                            route: actions.reject,
                            payload: reasonFields(fields),
                        },
                        { onCompleted: close },
                    )
                }
                onClose={close}
            />
        );
    } else if (
        (nested === 'preview' || nested === 'code') &&
        canSeal &&
        !unsaved
    ) {
        const notice = center.notice;
        const ownNotice =
            notice?.kind === 'refused' && STEP_UP_REFUSALS.has(notice.code);
        let message: string | null = null;

        if (entry.kind === 'wrong_code') {
            message = entry.message ?? t('auditor.seal.code_wrong');
        } else if (entry.kind === 'expired') {
            message = t('auditor.seal.code_expired');
        } else if (entry.kind === 'failed') {
            message = entry.message;
        }

        overlay = (
            <SealSheet>
                <div className="fixed inset-0 z-[52] bg-[rgba(8,14,28,.45)] lg:absolute" />
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="auditor-findings-title"
                    className="fixed inset-x-0 top-[9%] bottom-0 z-[53] flex animate-[rz-sheetup_.3s_ease] flex-col overflow-hidden rounded-[20px_22px_0_0] bg-rz-surface lg:absolute"
                >
                    <div className="flex items-center gap-[11px] border-b border-rz-border px-[18px] pt-4 pb-[13px]">
                        <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] border border-[#d4af37] bg-rz-accent-soft">
                            <svg
                                viewBox="0 0 20 20"
                                aria-hidden
                                className="size-4"
                                fill="#d4af37"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M10 1.944A11.954 11.954 0 012.166 5 12.02 12.02 0 0010 18.2 12.02 12.02 0 0017.834 5 11.954 11.954 0 0110 1.944zM13.707 8.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-[10.5px] font-bold tracking-[.08em] text-rz-ink uppercase">
                                {t('auditor.seal.findings_eyebrow', {
                                    version: stage.procedure_version,
                                })}
                            </p>
                            <h3
                                id="auditor-findings-title"
                                className="truncate text-[15.5px] font-bold text-rz-ink"
                            >
                                {period === ''
                                    ? business
                                    : `${business} · ${period}`}
                            </h3>
                        </div>
                        <button
                            type="button"
                            onClick={close}
                            aria-label={t('auditor.sheet.close')}
                            className="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-[#eef2f8] text-[15px] text-rz-ink dark:bg-rz-surface-muted"
                        >
                            ✕
                        </button>
                    </div>
                    <div className="rz-scroll min-h-0 flex-1 overflow-y-auto px-[18px] pt-4 pb-[18px]">
                        {!ownNotice && (
                            <AuditorCommandNotice placement="sheet" />
                        )}
                        {nested === 'preview' ? (
                            <div className="flex flex-col gap-3">
                                {stage.findings.map((finding) => (
                                    <section
                                        key={finding.code}
                                        className="rounded-2xl border border-rz-border bg-[#f8fafc] px-3.5 py-[13px] dark:bg-rz-surface-sunken"
                                    >
                                        <p className="text-[10.5px] font-bold tracking-[.06em] text-rz-ink uppercase">
                                            {finding.no}
                                        </p>
                                        <h4 className="mt-[3px] text-[13px] font-bold text-rz-ink">
                                            {finding.title}
                                        </h4>
                                        <p className="mt-1.5 text-[12px] leading-[1.55] whitespace-pre-line text-rz-slate">
                                            {finding.body}
                                        </p>
                                        {finding.evidence_ids.length > 0 && (
                                            <p className="mt-1.5 text-[10.5px] text-rz-secondary tabular-nums">
                                                {t('auditor.seal.cites', {
                                                    ids: finding.evidence_ids.join(
                                                        ', ',
                                                    ),
                                                })}
                                            </p>
                                        )}
                                    </section>
                                ))}
                                {persisted !== '' && (
                                    <section>
                                        <StepEyebrow>
                                            {t('auditor.seal.note')}
                                        </StepEyebrow>
                                        <p className="mt-2 text-[12px] leading-[1.55] whitespace-pre-line text-rz-slate">
                                            {persisted}
                                        </p>
                                    </section>
                                )}
                                <section>
                                    <StepEyebrow>
                                        {t('auditor.seal.evidence')}
                                    </StepEyebrow>
                                    <EvidenceList
                                        items={stage.evidence}
                                        className="mt-2"
                                    />
                                </section>
                                <div className="rounded-2xl bg-rz-page px-3.5 py-[13px] dark:bg-rz-surface-muted">
                                    <p className="text-[10.5px] font-bold tracking-[.06em] text-[#1e3aff] uppercase dark:text-rz-investor-text">
                                        {t('auditor.seal.digest')}
                                    </p>
                                    <p className="mt-[5px] text-[12px] tracking-[.01em] break-all text-rz-ink tabular-nums">
                                        {stage.digest}
                                    </p>
                                    <p className="mt-1.5 text-[10.5px] leading-[1.5] text-[#1e3aff] dark:text-rz-investor-text">
                                        {t('auditor.seal.versions', {
                                            procedure: stage.procedure_version,
                                            findings: stage.findings_version,
                                            evidence: stage.evidence_version,
                                        })}
                                    </p>
                                    <p className="mt-1.5 text-[10.5px] leading-[1.5] text-[#1e3aff] dark:text-rz-investor-text">
                                        {t('auditor.seal.digest_note')}
                                    </p>
                                </div>
                            </div>
                        ) : stage.mfa.confirmed ? (
                            <div className="pt-1.5 text-center">
                                <h4 className="text-[16px] font-bold text-rz-ink">
                                    {t('auditor.seal.code_title')}
                                </h4>
                                <p className="mt-[5px] text-[12.5px] leading-[1.5] text-rz-secondary">
                                    {t('auditor.seal.code_lead', {
                                        licence: stage.licence,
                                    })}
                                </p>
                                <CodeCells
                                    value={code}
                                    disabled={busy || throttled}
                                    invalid={message !== null}
                                    onChange={setCode}
                                />
                                <div id="auditor-step-up-message">
                                    {throttled ? (
                                        <Throttled
                                            seconds={entry.seconds}
                                            onDone={() =>
                                                setEntry({ kind: 'ready' })
                                            }
                                        />
                                    ) : (
                                        message !== null && (
                                            <p
                                                role="alert"
                                                className="mt-3 text-[12px] font-semibold text-rz-danger-text"
                                            >
                                                {message}
                                            </p>
                                        )
                                    )}
                                </div>
                                <p className="mt-4 text-[11px] leading-[1.5] text-rz-secondary">
                                    {t('auditor.seal.code_scope')}
                                </p>
                            </div>
                        ) : (
                            <div className="pt-1.5 text-center">
                                <h4 className="text-[16px] font-bold text-rz-ink">
                                    {t('auditor.seal.mfa_title')}
                                </h4>
                                <p className="mt-[5px] text-[12.5px] leading-[1.5] text-rz-secondary">
                                    {t('auditor.seal.mfa_body')}
                                </p>
                            </div>
                        )}
                    </div>
                    <div className="border-t border-rz-border px-[18px] pt-3 pb-4">
                        {nested === 'preview' ? (
                            <button
                                type="button"
                                onClick={() => setNested('code')}
                                disabled={!center.idle}
                                className="h-[50px] w-full rounded-2xl bg-rz-page text-[14.5px] font-bold text-rz-ink disabled:cursor-not-allowed disabled:opacity-60 dark:bg-rz-surface-muted"
                            >
                                {t('auditor.seal.apply')}
                            </button>
                        ) : stage.mfa.confirmed ? (
                            <button
                                type="button"
                                onClick={() => void confirm()}
                                disabled={
                                    code.length < CODE_LENGTH ||
                                    busy ||
                                    throttled ||
                                    !center.idle
                                }
                                aria-busy={busy || undefined}
                                className="h-[50px] w-full rounded-2xl bg-[#17795a] text-[14.5px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary"
                            >
                                {busy
                                    ? t('auditor.seal.sealing')
                                    : t('auditor.seal.submit')}
                            </button>
                        ) : (
                            <Link
                                href={stage.mfa.settings}
                                className="flex h-[50px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[14.5px] font-bold text-white"
                            >
                                {t('auditor.seal.mfa_settings')}
                            </Link>
                        )}
                    </div>
                </div>
            </SealSheet>
        );
    }

    return { body, footer, nested: overlay };
}
