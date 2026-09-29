import { Link } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';
import type { ReactNode } from 'react';
import { STEP_FORM, useStepForm } from '@/components/auditor/audit/parts';
import type { StepContext } from '@/components/auditor/audit/parts';
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
    BackButton,
    DetailSheet,
    JobHeader,
} from '@/components/auditor/detail-sheet';
import { FileReview } from '@/components/auditor/file/file-review';
import { useJobCommands } from '@/components/auditor/job-commands';
import { JobsBody } from '@/components/auditor/jobs/jobs-body';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { formatMonthYearLong } from '@/lib/rozine/format';
import type {
    AuditProcedureProps,
    AuditStage,
    SealStage,
} from '@/types/auditor';

/** The primary label per step, as the design words it (L3715, L3801). */
const CONTINUE: Record<
    Exclude<AuditStage['step'], 'seal' | 'sealed'>,
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
    context,
    seal,
}: {
    props: AuditProcedureProps;
    context: StepContext;
    seal: ReactNode;
}) {
    const { stage, audit } = props;

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
            return <SealedStatus stage={stage} />;
    }
}

/** A stand-in seal stage so the seal hook can always be called; only used while it is unused. */
const NO_SEAL: SealStage = {
    step: 'seal',
    summary: [],
    note: { required: false, why: '', value: '', min: 0, max: 0 },
    findings: [],
    procedure_version: '',
    digest: '',
    licence: '',
    seal: { url: '', method: 'post' },
    suggest: null,
    reject: null,
};

/**
 * The audit procedure (MVP-AUDITOR-SCR-03…06; design sheets L1017–1498): fixed-order steps from the
 * server, evidence captured in the companion app, reconciliation measured by the server, factual
 * findings, the ICPAR seal, and the co-signature that follows. A Flash Audit opens over the Jobs
 * left column and a monthly report over the right, as the design anchors them.
 */
export default function AuditorAudit(props: AuditProcedureProps) {
    const { t, locale } = useTranslation();
    const online = useOnline();
    const { audit, stage, links } = props;
    const period =
        audit.month === null ? '' : formatMonthYearLong(audit.month, locale);
    const context: StepContext = {
        rememberKey: `${audit.id}:${stage.step}`,
        step: stage.step,
        revision: audit.revision,
        save: props.actions.save,
        serverTime: props.server_time,
    };
    const commands = useJobCommands({
        fileId: audit.id,
        business: audit.business,
        actions: { conflict: props.actions.conflict },
    });
    const seal = useSealFlow({
        stage: stage.step === 'seal' ? stage : NO_SEAL,
        context,
        business: audit.business,
        period,
        canContinue: props.can_continue,
    });
    const sealed = stage.step === 'sealed';
    const steps = <StepBar steps={props.steps} />;

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

    if (stage.step === 'seal') {
        footer = seal.footer;
    } else if (sealed) {
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
                {links.back !== null && (
                    <Link
                        href={links.back}
                        className="flex h-12 w-16 shrink-0 items-center justify-center rounded-xl bg-[#eef2f8] text-[13px] font-bold text-rz-slate dark:bg-rz-surface-muted"
                    >
                        {t('auditor.audit.back')}
                    </Link>
                )}
                <button
                    type="submit"
                    form={STEP_FORM}
                    disabled={!props.can_continue}
                    className="h-12 flex-1 rounded-xl bg-[#0c1830] text-[14px] font-bold text-white disabled:cursor-not-allowed disabled:bg-rz-disabled disabled:text-rz-secondary dark:bg-rz-accent-fill"
                >
                    {t(CONTINUE[stage.step])}
                </button>
            </div>
        );
    }

    const sheet = (
        <DetailSheet
            label={t('auditor.audit.label', { business: audit.business })}
            close={links.close}
            dismissible={
                stage.step === 'review' ||
                stage.step === 'statements' ||
                stage.step === 'check_in' ||
                sealed
            }
            header={header}
            footer={
                <>
                    {footer}
                    {props.hint !== null && (
                        <p className="text-center text-[10.5px] text-rz-secondary">
                            {props.hint}
                        </p>
                    )}
                </>
            }
            nested={
                stage.step === 'seal'
                    ? (seal.nested ?? commands.sheet)
                    : commands.sheet
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
            <StageBody props={props} context={context} seal={seal.body} />
            {!sealed && (
                <div className="mt-5 flex justify-center">
                    {commands.conflictButton}
                </div>
            )}
        </DetailSheet>
    );

    return (
        <AuditorShell
            title={t('auditor.audit.head_title', { business: audit.business })}
            tab="jobs"
            links={props.jobs.links}
            openJobs={props.jobs.eligible.length}
            showTabBar={false}
        >
            <JobsBody
                {...props.jobs}
                backdrop
                overlay={{
                    column: audit.kind === 'flash' ? 'left' : 'right',
                    content: sheet,
                }}
            />
            <OutcomeModal outcome={props.outcome} />
        </AuditorShell>
    );
}
