import { AuditorShell } from '@/components/auditor/auditor-shell';
import { JobsBody } from '@/components/auditor/jobs/jobs-body';
import { OutcomeModal } from '@/components/auditor/sheets/outcome-modal';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorJobsProps } from '@/types/auditor';

/**
 * Auditor Jobs (MVP-AUDITOR-SCR-01): eligible and assigned work with deadlines and distance, with
 * accept, decline and a conflict declaration always within reach.
 */
export default function AuditorJobs(props: AuditorJobsProps) {
    const { t } = useTranslation();

    return (
        <AuditorShell
            title={t('auditor.jobs.head_title')}
            tab="jobs"
            links={props.links}
            openJobs={props.eligible.length}
        >
            <JobsBody {...props} />
            <OutcomeModal outcome={props.outcome} />
        </AuditorShell>
    );
}
