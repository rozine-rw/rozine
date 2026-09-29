import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { AuditorShell } from '@/components/auditor/auditor-shell';
import { DetailSheet, JobHeader } from '@/components/auditor/detail-sheet';
import { FileReview } from '@/components/auditor/file/file-review';
import { useJobCommands } from '@/components/auditor/job-commands';
import { JobsBody } from '@/components/auditor/jobs/jobs-body';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorFileProps } from '@/types/auditor';

/** The sheet's primary action: 50px, 16px radius (design L1225). */
const SHEET_PRIMARY =
    'flex h-[50px] w-full items-center justify-center rounded-2xl bg-rz-accent-fill text-[15px] font-bold text-white disabled:cursor-wait disabled:opacity-80';

/**
 * The business file (MVP-AUDITOR-SCR-02; the design's "Application preview", L1017–1093). An
 * offered file can be accepted or declined; any file can carry a conflict declaration. A phone
 * gets a full page; a wide screen gets the sheet over the Jobs column it came from.
 */
export default function AuditorFile(props: AuditorFileProps) {
    const { t } = useTranslation();
    const { job, links } = props;
    const [accepting, setAccepting] = useState(false);
    const commands = useJobCommands({
        fileId: job.id,
        business: job.business,
        actions:
            job.state === 'offered'
                ? props.actions
                : { conflict: props.actions.conflict },
    });

    const accept = () =>
        router.post(
            props.actions.accept.url,
            {},
            {
                onStart: () => setAccepting(true),
                onFinish: () => setAccepting(false),
            },
        );

    const sheet = (
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
                />
            }
            footer={
                <>
                    {job.state === 'offered' ? (
                        <button
                            type="button"
                            onClick={accept}
                            disabled={accepting}
                            className={SHEET_PRIMARY}
                        >
                            {t('auditor.jobs.accept', {
                                hours: props.flash_hours,
                            })}
                        </button>
                    ) : (
                        links.procedure !== null && (
                            <Link
                                href={links.procedure}
                                className={SHEET_PRIMARY}
                            >
                                {t('auditor.file.continue')}
                            </Link>
                        )
                    )}
                    {commands.links}
                </>
            }
            nested={commands.sheet}
        >
            <FileReview
                business={job.business}
                file={props.file}
                reassignedFrom={job.reassigned_from}
            />
        </DetailSheet>
    );

    return (
        <AuditorShell
            title={t('auditor.file.head_title', { business: job.business })}
            tab="jobs"
            links={props.jobs.links}
            openJobs={props.jobs.eligible.length}
            showTabBar={false}
        >
            <JobsBody
                {...props.jobs}
                backdrop
                overlay={{ column: 'left', content: sheet }}
            />
        </AuditorShell>
    );
}
