import { Link } from '@inertiajs/react';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
    useAuditorCommands,
} from '@/components/auditor/commands';
import { ConflictReceiptCard } from '@/components/auditor/conflict-receipt';
import { DetailSheet, JobHeader } from '@/components/auditor/detail-sheet';
import { FileReview } from '@/components/auditor/file/file-review';
import { useJobCommands } from '@/components/auditor/job-commands';
import { JobsBody } from '@/components/auditor/jobs/jobs-body';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { useTranslation } from '@/hooks/use-translation';
import type {
    AuditorAllowedAction,
    AuditorFileProps,
    BusinessFile,
    ConflictReceipt,
} from '@/types/auditor';

/** The sheet's primary action: 50px, 16px radius (design L1225). */
const SHEET_PRIMARY =
    'flex h-[50px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-bold text-white disabled:cursor-wait disabled:opacity-80';

type FileSheetProps = AuditorFileProps & {
    /** A blocking conflict this page just recorded, which withdraws the file at once. */
    receipt: ConflictReceipt | null;
};

function FileSheet({ receipt, ...props }: FileSheetProps) {
    const { t } = useTranslation();
    const { job, links } = props;
    const allowed = (action: AuditorAllowedAction) =>
        props.allowed_actions.includes(action);
    const preview = props.preview_outcome;
    const center = useAuditorCommands();
    const commands = useJobCommands({
        assignment: { id: job.id, revision: job.revision },
        business: job.business,
        allowed,
        conflict: props.actions.conflict,
        decline:
            job.state === 'offered'
                ? {
                      route: props.actions.decline,
                      options: props.decline_options,
                  }
                : null,
        initialSheet:
            preview?.kind === 'sheet' && preview.sheet === 'decline'
                ? { sheet: 'decline', reason: preview.reason }
                : null,
    });
    const blocked = receipt ?? props.blocked;

    const accept = () =>
        center.send({
            name: 'assignment.accept',
            business: job.business,
            route: props.actions.accept,
            payload: { assignment_id: job.id, expected_revision: job.revision },
        });

    let primary = null;

    if (blocked === null) {
        if (job.state === 'offered') {
            primary = allowed('assignment.accept') && (
                <button
                    type="button"
                    onClick={accept}
                    disabled={!center.idle}
                    aria-busy={center.busy || undefined}
                    className={SHEET_PRIMARY}
                >
                    {t('auditor.jobs.accept', { hours: props.flash_hours })}
                </button>
            );
        } else if (links.procedure !== null) {
            primary = (
                <Link href={links.procedure} className={SHEET_PRIMARY}>
                    {t('auditor.file.continue')}
                </Link>
            );
        }
    } else {
        primary = (
            <Link href={links.close} className={SHEET_PRIMARY}>
                {t('auditor.audit.back_to_jobs')}
            </Link>
        );
    }

    return (
        <DetailSheet
            label={t('auditor.file.label', { business: job.business })}
            close={links.close}
            dismissible
            header={
                <JobHeader
                    eyebrow={
                        job.state === 'offered'
                            ? t('auditor.file.eyebrow_preview')
                            : t('auditor.file.eyebrow_file')
                    }
                    close={links.close}
                    business={job.business}
                    district={job.district}
                    distanceKm={job.distance_km}
                    serverTime={props.server_time}
                    dueAt={job.deadline?.due_at ?? null}
                    hours={props.flash_hours}
                    clock={blocked === null}
                />
            }
            footer={
                <>
                    {primary}
                    {blocked === null && commands.links}
                </>
            }
            nested={blocked === null ? commands.sheet : null}
        >
            <AuditorCommandNotice placement="page" />
            {blocked === null ? (
                <FileReview
                    business={job.business}
                    file={props.file as BusinessFile}
                    reassignedFrom={job.reassigned_from}
                />
            ) : (
                <ConflictReceiptCard
                    business={job.business}
                    receipt={blocked}
                />
            )}
        </DetailSheet>
    );
}

/**
 * The business file (MVP-AUDITOR-SCR-02; the design's "Application preview", L1017–1093). An
 * offered file can be accepted or declined; any file can carry a conflict declaration — each only
 * when the server's `allowed_actions` lists it. A blocking conflict withdraws the file the moment
 * it is recorded, leaving the partner's receipt. A phone gets a full page; a wide screen gets the
 * sheet over the Jobs column it came from.
 */
export default function AuditorFile(props: AuditorFileProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.file.head_title', {
                    business: props.job.business,
                })}
                tab="jobs"
                links={props.jobs.links}
                openJobs={props.jobs.eligible.length}
                showTabBar={false}
            >
                <JobsBody
                    {...props.jobs}
                    backdrop
                    overlay={{
                        column: 'left',
                        content: (
                            <FileSheet {...props} receipt={center.blocked} />
                        ),
                    }}
                />
                <OutcomeModal
                    outcome={center.result?.outcome ?? null}
                    onDone={center.finish}
                />
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
