import { AuditorShell } from '@/components/auditor/auditor-shell';
import {
    AuditorCommandNotice,
    AuditorCommandProvider,
    useAuditorCommandCenter,
} from '@/components/auditor/commands';
import { JobsBody } from '@/components/auditor/jobs/jobs-body';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorJobsProps } from '@/types/auditor';

/**
 * Auditor Jobs (MVP-AUDITOR-SCR-01): eligible and assigned work with deadlines and distance, with
 * accept, decline and a conflict declaration within reach wherever the server allows them
 * (auditor-filing-v1). Offers are dispatched to one partner at a time, so each card carries its own
 * commands; the page carries the one command that may be in flight.
 */
export default function AuditorJobs(props: AuditorJobsProps) {
    const { t } = useTranslation();
    const center = useAuditorCommandCenter({
        page: props,
        lookup: props.links.operation,
        preview: props.preview_outcome,
    });

    return (
        <AuditorCommandProvider center={center}>
            <AuditorShell
                title={t('auditor.jobs.head_title')}
                tab="jobs"
                links={props.links}
                openJobs={props.eligible.length}
            >
                <JobsBody
                    {...props}
                    notice={
                        <AuditorCommandNotice
                            placement="page"
                            className="mt-3.5"
                        />
                    }
                />
                <OutcomeModal
                    outcome={center.result?.outcome ?? props.outcome}
                    onDone={center.finish}
                />
            </AuditorShell>
        </AuditorCommandProvider>
    );
}
