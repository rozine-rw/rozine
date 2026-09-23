import { AuditorShell } from '@/components/auditor/auditor-shell';
import { HomeBody } from '@/components/auditor/home/home-body';
import { useTranslation } from '@/hooks/use-translation';
import type { AuditorHomeProps } from '@/types/auditor';

/**
 * Auditor Home (design L86–203): who the partner is and their standing, whether dispatch may offer
 * them work, what is nearby, what is on the clock, and what happened recently.
 */
export default function AuditorHome(props: AuditorHomeProps) {
    const { t } = useTranslation();

    return (
        <AuditorShell
            title={t('auditor.home.head_title')}
            tab="home"
            links={props.links}
            openJobs={props.nearby.count}
        >
            <HomeBody {...props} />
        </AuditorShell>
    );
}
