import { Link } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';
import type { ReactNode } from 'react';
import { useDisputeUphold } from '@/components/auditor/audit/dispute-uphold';
import { STEP_FORM, useStepForm } from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
import { useReturnControls } from '@/components/auditor/audit/return-controls';
import { ReturnedStatus } from '@/components/auditor/audit/returned-status';
import { useSealFlow } from '@/components/auditor/audit/seal-flow';
import { SealedStatus } from '@/components/auditor/audit/sealed-status';
import { StepBar } from '@/components/auditor/audit/step-bar';
import { StepCheckIn } from '@/components/auditor/audit/step-check-in';
import { StepCount } from '@/components/auditor/audit/step-count';
import { StepLedger } from '@/components/auditor/audit/step-ledger';
import { StepPhotos } from '@/components/auditor/audit/step-photos';
import { StepStatements } from '@/components/auditor/audit/step-statements';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
    useAuditorCommands,
} from '@/components/auditor/commands';
import { ConflictReceiptCard } from '@/components/auditor/conflict-receipt';
import {
    BackButton,
    DetailSheet,
    JobHeader,
} from '@/components/auditor/detail-sheet';
import { FileReview } from '@/components/auditor/file/file-review';
import { useJobCommands } from '@/components/auditor/job-commands';
import { JobsBody, openOffers } from '@/components/auditor/jobs/jobs-body';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatMonthYearLong } from '@/lib/rozine/format';
import type {
    AuditProcedureProps,
    AuditStage,
    SealStage,
} from '@/types/auditor';

/**
 * The fields each step shows its own errors beside; any other field error from a step's command
 * reaches the partner as a banner above the step.
 */
const SHOWN_FIELDS: Partial<Record<AuditStage['step'], string[]>> = {
    ledger: ['observed_stock', 'document', 'replaces'],
    seal: ['note'],
};

/** The primary label per step, as the design words it (L3715, L3801). */
const CONTINUE: Record<
    Exclude<AuditStage['step'], 'seal' | 'sealed' | 'returned' | 'blocked'>,
    MessageCode
> = {
    review: 'auditor.audit.continue',
    check_in: 'auditor.audit.continue',
    photos: 'auditor.audit.continue',
    ledger: 'auditor.audit.to_seal',
    statements: 'auditor.audit.start_count',
    count: 'auditor.audit.to_photos',
};

const subscribe = (callback: () => void) => {
    window.addEventListener('online', callback);
    window.addEventListener('offline', callback);

    return () => {
        window.removeEventListener('online', callback);
        window.removeEventListener('offline', callback);
    };
};

/** Whether the browser is online, so a dropped connection is said out loud (SCR-03-ST-02). */
function useOnline(): boolean {
    return useSyncExternalStore(
        subscribe,
        () => navigator.onLine,
        () => true,
    );
}

/** The review step records only that the partner read the file and moved on. */
function StepReview({
    context,
    children,
}: {
    context: StepContext;
    children: ReactNode;
}) {
    const { submit } = useStepForm(context, {});

    return (
        <form id={STEP_FORM} onSubmit={submit} noValidate>
            {children}
        </form>
    );
}

/** The current step's body; the seal step's body comes from its own flow. */
function StageBody({
    props,
    stage,
    context,
    seal,
    sealed,
    returned,
}: {
    props: AuditProcedureProps;
    stage: AuditStage;
    context: StepContext;
    seal: ReactNode;
    sealed: ReactNode;
    returned: ReactNode;
}) {
    const { audit } = props;

    switch (stage.step) {
        case 'review':
            return (
                <StepReview context={context}>
                    <FileReview
                        business={audit.business}
                        file={stage.file}
                        reassignedFrom={audit.reassigned_from}
                    />
                </StepReview>
            );
        case 'check_in':
            return <StepCheckIn stage={stage} context={context} />;
        case 'photos':
            return (
                <StepPhotos stage={stage} context={context} kind={audit.kind} />
            );
        case 'ledger':
            return <StepLedger stage={stage} context={context} />;
        case 'statements':
            return <StepStatements stage={stage} context={context} />;
        case 'count':
            return <StepCount stage={stage} context={context} />;
        case 'seal':
            return seal;
        case 'sealed':
            return sealed;
        case 'returned':
            return returned;
        case 'blocked':
            return <ConflictReceiptCard receipt={stage.conflict} />;
    }
}

/** A stand-in seal stage so the seal hook can always be called; only used while it is unused. */
const NO_SEAL: SealStage = {
    step: 'seal',
    summary: [],
    note: { required: false, why: '', value: '', min: 0, max: 0 },
    findings: [],
    evidence: [],
    procedure_version: '',
    findings_version: '',
    evidence_version: '',
    digest: '',
    licence: '',
    mfa: { confirmed: false, settings: { url: '', method: 'get' } },
    reason_options: null,
};

/** The procedure's sheet, inside the page's command center. */
function AuditSheet(props: AuditProcedureProps) {
    const { t, locale } = useTranslation();
    const online = useOnline();
    const center = useAuditorCommands();
    const { audit, links, actions } = props;
    /* A blocking conflict recorded here withdraws the procedure at once, before any reload. */
    const stage: AuditStage =
        center.blocked === null
            ? props.stage
            : { step: 'blocked', conflict: center.blocked };
    const blocked = stage.step === 'blocked';
    const sealed = stage.step === 'sealed';
    const returned = stage.step === 'returned';
    /* A sealed, returned or blocked report is final: nothing on it can be saved or returned. */
    const terminal = sealed || returned || blocked;
    const period =
        audit.month === null ? '' : formatMonthYearLong(audit.month, locale);
    const context: StepContext = {
        rememberKey: `${audit.id}:${stage.step}`,
        step: stage.step,
        auditId: audit.id,
        revision: audit.revision,
        business: audit.business,
        save: actions.save,
        serverTime: props.server_time,
        identityContextRevision: props.identity_context_revision,
    };
    const commands = useJobCommands({
        assignment: props.assignment,
        business: audit.business,
        conflict: actions.conflict,
        decline: null,
        initialSheet: props.preview_conflict_open
            ? { sheet: 'conflict', reason: null }
            : null,
    });
    const seal = useSealFlow({
        stage: stage.step === 'seal' ? stage : NO_SEAL,
        context,
        business: audit.business,
        period,
        canContinue: props.can_continue,
        actions,
        preview: props.preview_outcome,
    });
    const returns = useReturnControls({
        options: terminal
            ? null
            : stage.step === 'seal'
              ? stage.reason_options
              : props.reason_options,
        context,
        business: audit.business,
        actions,
        preview: props.preview_outcome,
    });
    const disputeUphold = useDisputeUphold({
        stage,
        business: audit.business,
        reportRevision: audit.revision,
        route: actions.dispute_uphold,
    });
    const steps = blocked ? null : <StepBar steps={props.steps} />;

    const header =
        audit.kind === 'flash' ? (
            <JobHeader
                eyebrow={t('auditor.audit.eyebrow_flash')}
                close={links.close}
                business={audit.business}
                district={audit.district}
                distanceKm={audit.distance_km}
                serverTime={props.server_time}
                dueAt={audit.deadline.due_at}
                steps={steps}
                clock={!blocked}
            />
        ) : (
            <>
                <div className="flex items-center gap-3">
                    <BackButton
                        href={links.close}
                        label={t('auditor.sheet.back')}
                    />
                    <div className="min-w-0 flex-1">
                        <p className="text-[10px] font-bold tracking-[.08em] text-rz-ink uppercase">
                            {t('auditor.audit.eyebrow_monthly')}
                        </p>
                        <h2 className="truncate text-[17px] font-bold text-rz-ink">
                            {audit.business} · {period}
                        </h2>
                    </div>
                </div>
                {steps}
            </>
        );

    let footer: ReactNode;

    /*
     * The server's link to the step before this one, never a path built here. At the seal it is
     * the way back when a source changed after a preview and must be reviewed again.
     */
    const back = links.back !== null && (
        <Link
            href={links.back}
            className="flex h-12 w-16 shrink-0 items-center justify-center rounded-xl bg-[#eef2f8] text-[13px] font-bold text-rz-slate dark:bg-rz-surface-muted"
        >
            {t('auditor.audit.back')}
        </Link>
    );

    if (stage.step === 'seal') {
        footer = (
            <>
                <div className="flex gap-2.5">
                    {back}
                    <div className="min-w-0 flex-1">{seal.footer}</div>
                </div>
                {returns.buttons}
            </>
        );
    } else if (terminal) {
        footer = (
            <Link
                href={links.close}
                className="flex h-12 w-full items-center justify-center rounded-xl bg-rz-accent-fill text-[14px] font-bold text-white"
            >
                {t('auditor.audit.back_to_jobs')}
            </Link>
        );
    } else {
        footer = (
            <div className="flex gap-2.5">
                {back}
                {center.allowed('audit.save_step') && (
                    <button
                        type="submit"
                        form={STEP_FORM}
                        disabled={!props.can_continue || !center.idle}
                        aria-busy={center.busy || undefined}
                        className="h-12 flex-1 rounded-xl bg-[#0c1830] text-[14px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary dark:bg-rz-accent-fill dark:disabled:bg-rz-disabled"
                    >
                        {t(CONTINUE[stage.step])}
                    </button>
                )}
            </div>
        );
        footer = (
            <>
                {footer}
                {returns.buttons}
            </>
        );
    }

    const amendRoute = actions.amend;
    const amend =
        amendRoute !== null && center.allowed('audit.amend')
            ? {
                  run: () =>
                      center.send({
                          name: 'audit.amend',
                          business: audit.business,
                          route: amendRoute,
                          payload: {
                              audit_id: audit.id,
                              expected_revision: audit.revision,
                          },
                      }),
                  disabled: !center.idle,
              }
            : null;

    return (
        <DetailSheet
            label={t('auditor.audit.label', { business: audit.business })}
            close={links.close}
            dismissible={
                stage.step === 'review' ||
                stage.step === 'statements' ||
                stage.step === 'check_in' ||
                terminal
            }
            header={header}
            footer={
                <>
                    {footer}
                    {props.hint !== null && !blocked && (
                        <p className="text-center text-[10.5px] text-rz-secondary">
                            {props.hint}
                        </p>
                    )}
                </>
            }
            nested={
                blocked
                    ? null
                    : ((stage.step === 'seal' ? seal.nested : null) ??
                      returns.sheet ??
                      disputeUphold.sheet ??
                      commands.sheet)
            }
        >
            {!online && (
                <div
                    role="status"
                    className="mb-3.5 rounded-xl border border-[#f2d69a] bg-rz-surface px-3.5 py-3 text-[11.5px] leading-[1.5] text-[#8a6d2b] dark:border-[rgba(240,160,96,.3)] dark:text-[#e3b56a]"
                >
                    {t('auditor.audit.offline')}
                </div>
            )}
            <AuditorCommandNotice
                placement="page"
                shown={SHOWN_FIELDS[stage.step] ?? []}
            />
            {audit.amends !== null && !blocked && (
                <p
                    role="note"
                    className="mb-3.5 rounded-xl border border-rz-border bg-rz-surface px-3.5 py-3 text-[11.5px] leading-[1.5] text-rz-slate"
                >
                    {t('auditor.audit.amends', {
                        report: audit.amends.report_id,
                    })}{' '}
                    <Link
                        href={audit.amends.link}
                        className="font-bold text-rz-accent-app-text"
                    >
                        {t('auditor.audit.open_original')}
                    </Link>
                </p>
            )}
            <StageBody
                props={props}
                stage={stage}
                context={context}
                seal={seal.body}
                sealed={
                    stage.step === 'sealed' && (
                        <SealedStatus
                            stage={stage}
                            amend={amend}
                            uphold={disputeUphold.uphold}
                        />
                    )
                }
                returned={
                    stage.step === 'returned' && (
                        <ReturnedStatus stage={stage} amend={amend} />
                    )
                }
            />
            {!terminal && commands.conflictButton !== null && (
                <div className="mt-5 flex justify-center">
                    {commands.conflictButton}
                </div>
            )}
        </DetailSheet>
    );
}

/**
 * The audit procedure (MVP-AUDITOR-SCR-03…06; design sheets L1017–1498; auditor-filing-v1): fixed-
 * order steps from the server, evidence captured in the companion app, reconciliation measured by
 * the server, factual findings, the ICPAR seal after a fresh authenticator confirmation, and the
 * co-signature that follows. Every command goes only where `allowed_actions` allows it. A blocking
 * conflict stops the work at once and leaves only the partner's receipt. A Flash Audit opens over
 * the Jobs left column and a monthly report over the right, as the design anchors them.
 */
export default function AuditorAudit(props: AuditProcedureProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.audit.head_title', {
                    business: props.audit.business,
                })}
                tab="jobs"
                links={props.jobs.links}
                openJobs={openOffers(props.jobs)}
                showTabBar={false}
            >
                <JobsBody
                    {...props.jobs}
                    backdrop
                    overlay={{
                        column: props.audit.kind === 'flash' ? 'left' : 'right',
                        content: <AuditSheet {...props} />,
                    }}
                />
                <OutcomeModal
                    outcome={center.result?.outcome ?? props.outcome}
                    onDone={center.finish}
                />
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
